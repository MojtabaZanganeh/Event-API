<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Events\Events;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Base\Error;

class Reservations extends Events
{
    use Base, Sanitizer;

    public function get_user_reservations()
    {
        $user = $this->check_role();

        $sql = "SELECT 
                    JSON_OBJECT(
                        'id', r.id,
                        'event', JSON_OBJECT(
                            'title', e.title,
                            'slug', e.slug,
                            'start_time', DATE_FORMAT(e.start_time, '%Y/%m/%d %H:%i'),
                            'price', e.price
                        ),
                        'user', JSON_OBJECT(
                            'name', CONCAT(u.first_name, ' ', u.last_name)
                        ),
                        'code', r.code,
                        'price', r.price,
                        'status', r.status,
                        'created_at', DATE_FORMAT(r.created_at, '%Y/%m/%d %H:%i')
                    ) AS reservations_data
                FROM {$this->table['reservations']} r
                JOIN {$this->table['events']} e ON r.event_id = e.id
                JOIN {$this->table['users']} u ON r.user_id = u.id
            WHERE r.user_id = ?
                ORDER BY r.created_at DESC
        ";

        $reservations_json = $this->getData($sql, [$user['id']], true);

        if (!$reservations_json) {
            Response::success('رزروی یافت نشد');
        }

        $reservations = array_column($reservations_json, 'reservations_data');

        foreach ($reservations as &$reservation) {
            $reservation = json_decode($reservation, true);
        }

        Response::success('رزروهای شما دریافت شد', 'allReservations', $reservations);
    }

    public function add_reservation($params) {
        $this->check_params($params, ['event_id', 'is_group', 'find_buddy']);
        
        if ($params['is_group']) {
            $this->check_params($params, ['group_members']);
        }

        $transactions_obj = new Transactions();
        $payment_url = $transactions_obj->create_payment_link('1', '6');

        Response::success('رزرو انجام شد', 'paymentURL', $payment_url);
    }
}