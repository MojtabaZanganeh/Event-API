<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Reservations\Reservations;
use Classes\Base\Zarinpal;
use GrahamCampbell\ResultType\Success;

class Transactions extends Reservations
{
    use Base, Sanitizer;

    public function get_user_transactions()
    {
        $user = $this->check_role();

        $sql = "SELECT
                    JSON_OBJECT(
                        'title', e.title,
                        'slug', e.slug,
                        'start_time', e.start_time
                    ) AS `event`,
                    t.type,
                    t.amount,
                    t.status,
                    t.authority,
                    t.card_hash,
                    t.card_pan,
                    t.ref_id,
                    t.paid_at,
                    t.created_at,
                    t.updated_at
                FROM {$this->table['transactions']} t
                LEFT JOIN  {$this->table['reservations']} r ON t.reservation_id = r.id
                LEFT JOIN  {$this->table['events']} e ON r.event_id = e.id
            WHERE r.user_id = ?";

        $transactions = $this->getData($sql, [$user['id']], true);

        if (!$transactions) {
            Response::success('تراکنشی یافت نشد');
        }

        foreach ($transactions as &$transaction) {
            $transaction['event'] = json_decode($transaction['event'], true);
        }

        Response::success('تراکنش های شما دریافت شد', 'userTransactions', $transactions);
    }

    public function add_payment($reservation_id, $user_id, $event_id, $amount)
    {
        if (!$reservation_id || !$user_id || !$event_id || !isset($amount)) {
            return null;
        }

        $current_time = $this->current_time();

        $transaction_id = $this->insertData(
            "INSERT INTO {$this->table['transactions']} (`reservation_id`, `type`, `amount`, `status`, `authority`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $reservation_id,
                'payment',
                $amount,
                'pending',
                'A0000000000000000000000000000wwOGYpd',
                $current_time,
                $current_time
            ]
        );

        if (!$transaction_id) {
            return false;
        }

        return 'http://localhost:3000/events/demo-payment';
    }

    public function check_payment_status($params)
    {
        $this->check_params($params, ['authority']);

        $payment_data = $this->getData(
            "SELECT
                    t.id,
                    t.amount,
                    t.ref_id,
                    t.reservation_id,
                    JSON_OBJECT(
                        'title', e.title,
                        'start_time', DATE_FORMAT(e.start_time, '%Y/%m/%d %H:%i'),
                        'location', e.location,
                        'image', em.url
                    ) AS `event`
                FROM {$this->table['transactions']} t
                LEFT JOIN {$this->table['reservations']} r ON t.reservation_id = r.id
                LEFT JOIN {$this->table['events']} e ON r.event_id = e.id
                LEFT JOIN {$this->table['event_medias']} em ON e.thumbnail_id = em.id
                WHERE t.authority = ?
             ",
            [$params['authority']]
        );

        if (!$payment_data) {
            Response::error('تراکنش یافت نشد, در صورتی که مبلغی از حساب شما کم شده باشد ظرف 72 ساعت آینده به حساب شما باز می گردد');
        }

        Response::success('پرداخت انجام شد', 'paymentStatus', $payment_data);
    }
}