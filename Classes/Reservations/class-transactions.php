<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Reservations\Reservations;
use Classes\Base\Zarinpal;

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
            Response::error('تراکنشی یافت نشد');
        }

        foreach ($transactions as &$transaction) {
            $transaction['event'] = json_decode($transaction['event'], true);
        }

        Response::success('تراکنش های شما دریافت شد', 'userTransactions', $transactions);
    }
}