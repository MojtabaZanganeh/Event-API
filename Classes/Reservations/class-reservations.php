<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Database;
use Classes\Conversations\Conversations;
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

        $event_id = $params['event_id'];

        $event = $this->getData(
            "SELECT 
                    e.id,
                    e.start_time,
                    e.capacity,
                    e.price,
                    e.grouping,
                    COALESCE(SUM(r.group_members), 0) AS filled,
                    (e.capacity - COALESCE(SUM(r.group_members), 0)) AS `left`
                FROM {$this->table['events']} e
                LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status != 'canceled'
                WHERE e.id = ?
                GROUP BY e.id, e.start_time, e.capacity, e.price, e.grouping",
            [$event_id]
        );
        if (!$event) {
            Response::error('رویداد یافت نشد');
        }

        if ($event['left'] <= 0) {
            Response::error('ظرفیت ثبت نام در رویداد تکمیل شده است');
        }

        $group_members_count = $params['is_group'] ? count($params['group_members']) + 1 : 1;

        if ($group_members_count > $event['left'] || ($event['grouping'] > 2 && $group_members_count > $event['grouping'])) {
            Response::error('تعداد اعضای گروه از حد مجاز بالاتر است');
        }

        $event_start_time = $event['start_time'];
        $current_datetime = new DateTime();
        $event_start_datetime = new DateTime($event_start_time);
        if ($current_datetime > $event_start_datetime) {
            Response::error('زمان ثبت نام در رویداد گذشته است');
        }

        $reservation_code = 'RES_';
        $reservation_code .= $this->get_random('int', 6, $this->table['reservations'], 'code');

        $discount_obj = new Discounts();
        $event_price = $event['price'];
        $discount = !empty($params['discount_code']) ? $discount_obj->check_discount_code(['discount_code' => $params['discount_code'], 'event_id' => $event_id, 'return' => true]) : ['id' => 0, 'amount' => 0];
        $final_price = max(0, $event_price - $discount['amount']);

        $find_buddy = $params['find_buddy'] && $event['grouping'] < 2 ? true : false;

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
                    r.group_members,
                    e.start_time AS event_start_time,
                    e.end_time AS event_end_time,
                    e.grouping,
                    e.title,
                    e.is_approval
                FROM {$db->table['reservations']} r
                LEFT JOIN {$db->table['events']} e ON r.event_id = e.id
                WHERE r.id = ?
                GROUP BY r.id, r.event_id, r.user_id, r.find_buddy, e.start_time",
            [$reservation_id]
        );

        if (!$reservation_data) {
            return false;
        }

        $event_id = $reservation_data['event_id'];
        $user_id = $reservation_data['user_id'];
        $event_grouping = $reservation_data['grouping'];
        $event_title = $reservation_data['title'];
        $event_approval_user = $reservation_data['is_approval'];
        $group_members = $reservation_data['group_members'];

        $calculation_expires_date = new DateTime($reservation_data['event_end_time'] ?? $reservation_data['event_start_time']);
        $calculation_expires_date->modify('+1 week');
        $conversation_expires_date = $calculation_expires_date->format('Y-m-d H:i:s');

        $current_time = $this->current_time();

        $conversations_obj = new Conversations();

        if ($reservation_data['find_buddy']) {
            $buddy_conversation = $db->getData(
                "SELECT id FROM {$db->table['conversations']} WHERE `event_id` = ? AND `is_group` = 0 AND `status` = 'pending'",
                [$event_id]
            );

            if ($buddy_conversation) {

                $buddy_conversation_id = $buddy_conversation['id'];

                $add_to_conversation = $conversations_obj->add_user_to_conversation($db, $buddy_conversation_id, $user_id, $reservation_id, 1);

                if (!$add_to_conversation) {
                    return false;
                }

            } else {
                $conversation_id = $conversations_obj->create_conversation(
                    null,
                    false,
                    $event_id,
                    2,
                    $conversation_expires_date,
                    $db
                );

                if (!$conversation_id) {
                    return false;
                }

                $add_to_conversation = $conversations_obj->add_user_to_conversation($db, $conversation_id, $user_id, $reservation_id, 1);


                if (!$add_to_conversation) {
                    return false;
                }
            }
        }

        if ($event_grouping >= 2) {
            $group_conversation = $db->getData(
                "SELECT 
                        c.id,
                        c.event_id,
                        SUM(r.group_members) AS conversation_members
                    FROM {$db->table['conversations']} c
                    LEFT JOIN {$db->table['conversation_participants']} cp ON c.id = cp.conversation_id
                    LEFT JOIN {$db->table['reservations']} r ON cp.reservation_id = r.id
                    WHERE c.event_id = ? AND c.is_group = 1 AND c.status = 'pending'",
                [$event_id]
            );

            if ($group_conversation) {

                $group_conversation_id = $group_conversation['id'];
                $conversation_members = $group_conversation['conversation_members'];

                if ($event_grouping - $conversation_members >= $group_members) {

                    $add_to_conversation = $conversations_obj->add_user_to_conversation($db, $group_conversation_id, $user_id, $reservation_id, $group_members);

                    if (!$add_to_conversation) {
                        return false;
                    }

                } else {
                    $conversation_id = $conversations_obj->create_conversation($event_title, true, $event_id, $event_grouping, $conversation_expires_date, $db);

                    if (!$conversation_id) {
                        return false;
                    }

                    $add_to_conversation = $conversations_obj->add_user_to_conversation($db, $conversation_id, $user_id, $reservation_id, $group_members);

                    if (!$add_to_conversation) {
                        return false;
                    }
                }
            }
        }

        $reservation_status = $event_approval_user ? 'need-approval' : 'paid';
        $update_reservation = $db->updateData(
            "UPDATE {$db->table['reservations']} SET `status` = ?, `updated_at` = ? WHERE id = ?",
            [
                $reservation_status,
                $current_time,
                $reservation_id
            ]
        );

        if (!$update_reservation) {
            return false;
        }

        return true;
    }
}