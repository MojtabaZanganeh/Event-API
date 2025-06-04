<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Timeslots\Timeslots;
use Classes\Users\Authentication;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Trips\Cities;
use Classes\Trips\Trips;

class Reservations extends Timeslots
{
    use Base, Sanitizer;

    private function update_capacity_left(int $slot_id, string $type = 'sub'): bool
    {
        $time_slot = $this->getData("SELECT capacity_left FROM {$this->table['time_slots']} WHERE `id` = ?", [$slot_id]);
        if (!$time_slot) {
            return false;
        }
        $capacity_left = $time_slot['capacity_left'];
        $capacity_left = ($type === 'sub') ? $capacity_left - 1 : $capacity_left + 1;
        $update_capacity_left = $this->updateData("UPDATE {$this->table['time_slots']} SET `capacity_left` = ? WHERE `id` = ?", [$capacity_left, $slot_id]);
        return $update_capacity_left;
    }
    public function add_reservation($params)
    {
        $user = $this->check_role();

        $this->check_params($params, ['slot_id']);

        $slot_id = $params['slot_id'];

        $now = $this->current_time();

        $this->beginTransaction();

        $sql = "INSERT INTO {$this->table['reservations']}(`user_id`, `slot_id`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?)";
        $reservation_id = $this->insertData($sql, [$user->user_id, $slot_id, $now]);

        if ($reservation_id) {
            $update_capacity_left = $this->update_capacity_left($slot_id);
            if ($update_capacity_left) {
                $this->commit();
                Response::success('نوبت با موفقیت رزرو شد');
            }
        }
        $this->rollback();
        Response::error('نوبت رزرو نشد');
    }

    public function reservations_info()
    {
        $admin = $this->check_role('admin-dormitory');

        $is_general_admin = ($admin->role === 'admin');
        $is_dormitory_admin = !$is_general_admin;

        $dormitory = null;
        if ($is_dormitory_admin) {
            $dormitory = str_replace('admin-', '', $admin->role);
        }

        $reservation_query = "SELECT COUNT(*) as count FROM {$this->table['reservations']} r";
        $reservation_params = [];
        if ($is_dormitory_admin) {
            $reservation_query .= " JOIN {$this->table['users']} u ON r.user_id = u.id WHERE u.dormitory = ?";
            $reservation_params[] = $dormitory;
        }
        $get_reservations_info = $this->getData($reservation_query, $reservation_params);
        $total_reservations = $get_reservations_info['count'];

        $user_query = "SELECT COUNT(*) as count FROM {$this->table['users']}";
        $user_params = [];
        if ($is_dormitory_admin) {
            $user_query .= " WHERE dormitory = ?";
            $user_params[] = $dormitory;
        }
        $get_users_info = $this->getData($user_query, $user_params);
        $total_users = $get_users_info['count'];

        $dormitory_counts = [
            'dormitory-1' => 0,
            'dormitory-2' => 0,
        ];
        if ($is_general_admin) {
            $get_dormitory_info = $this->getData(
                "SELECT u.dormitory, COUNT(*) as count 
             FROM {$this->table['reservations']} r 
             JOIN {$this->table['users']} u ON r.user_id = u.id 
             GROUP BY u.dormitory",
                [],
                true
            );
            if ($get_dormitory_info) {
                foreach ($get_dormitory_info as $dormitory_info) {
                    $dormitory_counts[$dormitory_info['dormitory']] = $dormitory_info['count'];
                }
            }
        }

        $status_counts = [
            'pending' => 0,
            'washing' => 0,
            'ready' => 0,
            'finished' => 0,
            'cancelled' => 0
        ];
        $status_query = "SELECT r.status, COUNT(*) AS count 
                     FROM {$this->table['reservations']} r";
        $status_params = [];
        if ($is_dormitory_admin) {
            $status_query .= " JOIN {$this->table['users']} u ON r.user_id = u.id WHERE u.dormitory = ?";
            $status_params[] = $dormitory;
        }
        $status_query .= " GROUP BY r.status";
        $get_status_info = $this->getData($status_query, $status_params, true);
        if ($get_status_info) {
            foreach ($get_status_info as $status_info) {
                $status_counts[$status_info['status']] = $status_info['count'];
            }
        }

        $weekly_reservations = [
            ['day' => 'شنبه', 'count' => 0],
            ['day' => 'یکشنبه', 'count' => 0],
            ['day' => 'دوشنبه', 'count' => 0],
            ['day' => 'سه شنبه', 'count' => 0],
            ['day' => 'چهارشنبه', 'count' => 0],
            ['day' => 'پنجشنبه', 'count' => 0],
            ['day' => 'جمعه', 'count' => 0]
        ];
        $weekly_query = "SELECT ts.day AS day, COUNT(*) AS count 
                     FROM {$this->table['reservations']} r 
                     JOIN {$this->table['time_slots']} ts ON r.slot_id = ts.id";
        $weekly_params = [];
        if ($is_dormitory_admin) {
            $weekly_query .= " JOIN {$this->table['users']} u ON r.user_id = u.id";
        }
        $weekly_query .= " WHERE ts.date BETWEEN CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY 
                       AND CURDATE() + INTERVAL (6 - WEEKDAY(CURDATE())) DAY";
        if ($is_dormitory_admin) {
            $weekly_query .= " AND u.dormitory = ?";
            $weekly_params[] = $dormitory;
        }
        $weekly_query .= " GROUP BY ts.day";
        $get_weekly_reservations_info = $this->getData($weekly_query, $weekly_params, true) ?? [];

        foreach ($weekly_reservations as &$day_info) {
            foreach ($get_weekly_reservations_info as $weekly_reservations_info) {
                if ($weekly_reservations_info['day'] === $day_info['day']) {
                    $day_info['count'] = (int) $weekly_reservations_info['count'];
                    break;
                }
            }
        }
        unset($day_info);

        $reservations_info = [
            'totalReservations' => $total_reservations,
            'totalUsers' => $total_users,
            'statusCounts' => $status_counts,
            'weeklyReservations' => $weekly_reservations,
            'dormitoryCounts' => $dormitory_counts
        ];

        Response::success('آمار اطلاعات با موفقیت دریافت شد', 'info', $reservations_info);
    }


    public function recent_reservations($params)
    {
        $user = $this->check_role();

        $conditions = [];
        $values = [];

        if ($user->role === 'user') {
            $conditions[] = 'r.user_id = ?';
            $values[] = $user->user_id;
        } elseif (str_contains($user->role, 'admin-dormitory')) {
            $conditions[] = 'ts.dormitory = ?';
            $values[] = str_replace('admin-', '', $user->role);
        } elseif ($user->role === 'admin' && isset($params['dormitory'])) {
            $conditions[] = 'ts.dormitory = ?';
            $values[] = $params['dormitory'];
        }

        if (!empty($params['status'])) {
            $conditions[] = 'r.status = ?';
            $values[] = $params['status'];
        }

        if (!empty($params['search'])) {
            $conditions[] = 'u.last_name LIKE ?';
            $values[] = '%' . $params['search'] . '%';
        }

        if (!empty($params['id'])) {
            $conditions[] = 'r.id = ?';
            $values[] = $params['id'];
        }

        $page = isset($params['page']) ? (int) $params['page'] : 1;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * 20;

        $where_clause = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $count_query = "
            SELECT COUNT(*) as count
            FROM {$this->table['reservations']} r
            JOIN {$this->table['users']} u ON r.user_id = u.id
            JOIN {$this->table['time_slots']} ts ON r.slot_id = ts.id
            $where_clause
        ";
        $get_reservations_info = $this->getData($count_query, $values);
        $total_reservations = $get_reservations_info['count'];

        $pages = ceil($total_reservations / 20);

        $recent_reservations = [];

        $query = "
            SELECT 
                r.id,
                r.user_id,
                u.first_name,
                u.last_name,
                r.status,
                r.created_at,
                r.updated_at,

                ts.id AS slot_id,
                ts.date,
                TIME_FORMAT(ts.start_time, '%H:%i') AS start_time,
                TIME_FORMAT(ts.end_time, '%H:%i') AS end_time,
                ts.dormitory

            FROM {$this->table['reservations']} r
            JOIN {$this->table['users']} u ON r.user_id = u.id
            JOIN {$this->table['time_slots']} ts ON r.slot_id = ts.id
            $where_clause
            ORDER BY r.created_at DESC
            LIMIT 20 OFFSET $offset
        ";

        $get_recent_reservations = $this->getData($query, $values, true);

        if ($get_recent_reservations) {

            foreach ($get_recent_reservations as $reservation) {
                $recent_reservations[] = [
                    'id' => $reservation['id'],
                    'user_id' => $reservation['user_id'],
                    'user_first_name' => $reservation['first_name'],
                    'user_last_name' => $reservation['last_name'],
                    'timeSlots' => [
                        'id' => $reservation['slot_id'],
                        'date' => $reservation['date'],
                        'start_time' => $reservation['start_time'],
                        'end_time' => $reservation['end_time'],
                        'dormitory' => $reservation['dormitory'],
                    ],
                    'status' => $reservation['status'],
                    'created_at' => $reservation['created_at'],
                    'updated_at' => $reservation['updated_at'],
                    'paymentStatus' => 'pending',
                ];
            }
        }

        $reservations_list = [
            'pages' => $pages,
            'list' => $recent_reservations
        ];

        Response::success('رزروهای اخیر با موفقیت دریافت شد', 'recent', $reservations_list);
    }


    public function manage_reservations($params)
    {
        $admin = $this->check_role('admin-dormitory');

        $this->check_params($params, ['reservation_id', 'status']);

        $reservation_id = $params['reservation_id'];
        $status = $params['status'];

        $now = $this->current_time();

        $this->beginTransaction();

        $get_reservation_info = $this->getData("SELECT `status`, `slot_id` FROM {$this->table['reservations']} WHERE id = ?", [$reservation_id]);
        $old_status = $get_reservation_info['status'];
        $slot_id = $get_reservation_info['slot_id'];

        $update_status = $this->updateData("UPDATE {$this->table['reservations']} SET `status` = ?, `updated_at` = ? WHERE id = ?", [$status, $now, $reservation_id]);
        if ($update_status) {
            if ($status === 'cancelled') {
                $update_capacity = $this->update_capacity_left($slot_id, 'add');
            } elseif ($old_status === 'cancelled') {
                $update_capacity = $this->update_capacity_left($slot_id, 'sub');
            }
            if (($status !== 'cancelled' && $old_status !== 'cancelled') || $update_capacity) {
                $this->commit();
                Response::success('وضعیت رزرو با موفقیت تغییر کرد');
            }
        }

        $this->rollback();

        Response::error('خطا در تغییر وضعیت');
    }

    public function cancel_reservation($params)
    {
        $user = $this->check_role('user');

        $this->check_params($params, ['reservation_id']);

        $reservation_id = $params['reservation_id'];

        $now = $this->current_time();

        $this->beginTransaction();

        $cancel_reservation = $this->updateData("UPDATE {$this->table['reservations']} SET `status` = ?, `updated_at` = ? WHERE id = ? and user_id = ?", ['cancelled', $now, $reservation_id, $user->user_id]);

        if ($cancel_reservation) {
            $get_reservation_info = $this->getData("SELECT slot_id FROM {$this->table['reservations']} WHERE id = ? and user_id = ?", [$reservation_id, $user->user_id]);
            $slot_id = $get_reservation_info['slot_id'];

            $update_capacity = $this->update_capacity_left($slot_id, 'add');

            if ($update_capacity) {
                $this->commit();
                Response::success('رزرو لغو شد');
            }
        }

        $this->rollback();
        Response::error('خطا در لغو رزرو');
    }
}