<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Database;
use Classes\Events\Events;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Base\Error;
use DateTime;

class Reservations extends Events
{
    use Base, Sanitizer;

    public function get_user_reservations()
    {
        $user = $this->check_role();

        $sql = "SELECT 
                    r.id,
                    JSON_OBJECT(
                        'title', e.title,
                        'slug', e.slug,
                        'start_time', DATE_FORMAT(e.start_time, '%Y/%m/%d %H:%i'),
                        'price', e.price
                    ) AS `event`,
                    JSON_OBJECT(
                        'name', CONCAT(u.first_name, ' ', u.last_name)
                    ) AS user,
                    r.code,
                    r.price,
                    r.status,
                    DATE_FORMAT(r.created_at, '%Y/%m/%d %H:%i') AS created_at
                FROM {$this->table['reservations']} r
                JOIN {$this->table['events']} e ON r.event_id = e.id
                JOIN {$this->table['users']} u ON r.user_id = u.id
            WHERE r.user_id = ?
                ORDER BY r.created_at DESC
        ";

        $reservations = $this->getData($sql, [$user['id']], true);

        if (!$reservations) {
            Response::success('رزروی یافت نشد');
        }

        Response::success('رزروهای شما دریافت شد', 'allReservations', $reservations);
    }

    public function add_reservation($params)
    {
        $user = $this->check_role();

        $this->check_params($params, ['event_id', 'is_group', 'find_buddy']);

        $group_members_count = $params['is_group'] ? count($params['group_members']) + 1 : 1;

        $event_id = $params['event_id'];

        $event = $this->getData(
            "SELECT 
                    e.id,
                    e.start_time,
                    e.capacity,
                    e.price,
                    e.grouping,
                    COALESCE(SUM(r.group_members), 0) AS filled
                FROM {$this->table['events']} e
                LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status != 'canceled'
                WHERE e.id = ?
                GROUP BY e.id, e.start_time, e.capacity, e.price, e.grouping",
            [$event_id]
        );
        if (!$event) {
            Response::error('رویداد یافت نشد');
        }

        $event_start_time = $event['start_time'];
        $current_datetime = new DateTime();
        $event_start_datetime = new DateTime($event_start_time);
        if ($current_datetime > $event_start_datetime) {
            Response::error('زمان ثبت نام در رویداد گذشته است');
        }

        $event_capacity = $event['capacity'];
        $event_filled = $event['filled'];
        if ($event_filled >= $event_capacity) {
            Response::error('ظرفیت ثبت نام در رویداد تکمیل شده است');
        }

        $reservation_code = 'RES_';
        $reservation_code .= $this->get_random('int', 6, $this->table['reservations'], 'code');

        $discount_obj = new Discounts();
        $event_price = $event['price'];
        $discount = !empty($params['discount_code']) ? $discount_obj->check_discount_code(['discount_code' => $params['discount_code'], 'event_id' => $event_id, 'return' => true]) : ['id' => 0, 'amount' => 0];
        $final_price = max(0, $event_price - $discount['amount']);

        $find_buddy = $params['find_buddy'] && $event['grouping'] === 0 ? true : false;

        $current_time = $this->current_time();

        $db = new Database();
        $db->beginTransaction();

        $reservation_id = $db->insertData(
            "INSERT INTO {$db->table['reservations']} (`code`, `event_id`, `user_id`,  `discount_code_id`, `price`, `find_buddy`, `group_members`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $reservation_code,
                $event_id,
                $user['id'],
                $discount['id'],
                $final_price,
                $find_buddy,
                $group_members_count,
                'pending-pay',
                $current_time,
                $current_time
            ]
        );

        if (!$reservation_id) {
            Response::error('خطا در ثبت رزرو');
        }

        if ($params['is_group']) {
            $this->check_params($params, ['group_members']);

            $group_members = $params['group_members'];

            foreach ($group_members as $group_member) {
                $this->check_params($group_member, ['first_name', 'last_name', 'birth_date', 'national_id']);

                $add_group_members[] = $db->insertData(
                    "INSERT INTO {$db->table['reservation_group_members']} (`reservation_id`, `first_name`, `last_name`, `birth_date`, `national_id`, `registered_at`) VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $reservation_id,
                        $group_member['first_name'],
                        $group_member['last_name'],
                        $this->convert_jalali_to_miladi($group_member['birth_date']),
                        $group_member['national_id'],
                        $current_time
                    ]
                );
            }

            if (in_array(null, $add_group_members)) {
                Response::error('خطا در ثبت اعضای گروه');
            }
        }

        $transactions_obj = new Transactions();
        $payment_url = $transactions_obj->add_payment(
            $reservation_id,
            $user['id'],
            $event_id,
            $final_price,
            $db
        );

        if (!$payment_url) {
            Response::error('خطا در ساخت لینک پرداخت');
        }

        $db->commit();

        Response::success('رزرو انجام شد', 'paymentURL', $payment_url);
    }

    public function reservation_paid($reservation_id, Database $db)
    {
        if (!$reservation_id) {
            return false;
        }

        $reservation_data = $db->getData(
            "SELECT 
                    r.id,
                    r.event_id,
                    r.user_id,
                    r.find_buddy,
                    e.start_time AS event_start_time,
                    e.end_time AS event_end_time
                FROM {$db->table['reservations']} r
                LEFT JOIN {$db->table['events']} e ON r.event_id = e.id
                WHERE r.id = ?
                GROUP BY r.id, r.event_id, r.user_id, r.find_buddy, e.start_time",
            [$reservation_id]
        );

        if (!$reservation_data) {
            return false;
        }

        $current_time = $this->current_time();

        if ($reservation_data['find_buddy']) {
            $buddy_conversation = $db->getData("SELECT id FROM {$db->table['conversations']} WHERE `event_id` = ? AND `is_group` = 0 AND `status` = 'pending-user'", [$reservation_data['event_id']]);

            if ($buddy_conversation) {
                $add_to_conversation = $db->insertData(
                    "INSERT INTO {$db->table['conversation_participants']} (`conversation_id`, `user_id`, `joined_at`) VALUES (?, ?, ?)",
                    [
                        $buddy_conversation['id'],
                        $reservation_data['user_id'],
                        $current_time
                    ]
                );

                if (!$add_to_conversation) {
                    return false;
                }

                $update_conversation = $db->updateData(
                    "UPDATE {$db->table['conversations']} SET `status` = 'completed' WHERE id = ?",
                    [
                        $buddy_conversation['id']
                    ]
                );

                if (!$update_conversation) {
                    return false;
                }

            } else {
                $conversation_expires_date = new DateTime($reservation_data['event_end_time'] ?? $reservation_data['event_start_time']);
                $conversation_expires_date->modify('+1 week');

                $conversation_id = $db->insertData(
                    "INSERT INTO {$db->table['conversations']} (`is_group`, `event_id`, `status`, `expires_on`, `created_at`) VALUES (?, ?, ?, ?, ?)",
                    [
                        false,
                        $reservation_data['event_id'],
                        'pending-user',
                        $conversation_expires_date,
                        $current_time
                    ]
                );

                if (!$conversation_id) {
                    return false;
                }

                $add_to_conversation = $db->insertData(
                    "INSERT INTO {$db->table['conversation_participants']} (`conversation_id`, `user_id`, `joined_at`) VALUES (?, ?, ?)",
                    [
                        $conversation_id,
                        $reservation_data['user_id'],
                        $current_time
                    ]
                );

                if (!$add_to_conversation) {
                    return false;
                }
            }
        }

        $update_reservation = $db->updateData(
            "UPDATE {$db->table['reservations']} SET `status` = ?, `updated_at` = ? WHERE id = ?",
            [
                $reservation_id,
                'paid',
                $current_time
            ]
        );

        if (!$update_reservation) {
            return false;
        }

        return true;
    }
}