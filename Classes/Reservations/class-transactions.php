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

    public function create_payment_link($user_id, $event_id) {
        return 'http://localhost:3000/events/demo-payment';
    }

    public function check_payment_status($params) {
        $this->check_params($params, ['authority']);

        Response::success('پرداخت انجام شد', 'paymentStatus', [
            'success' => false,
            'amount' => 150000,
            'refId' => 456498648158,
            'event' => [
                'title' => 'مدرسه گربه ها',
                'start_time' => '2025/09/01 15:15',
                'location'=>' میدان اقدسیه- ابتدای بزرگراه ارتش- سه راه ازگل- بلوار شهید مژدی- بلوار محک- موسسه خیریه و بیمارستان فوق تخصصی سرطان کودکان محک',
                'image' => 'http://localhost:80/EventAPI/Uploads/01987a7a-bdd7-73de-b653-4e6cf073fad2.jpeg'
            ]
        ]);
    }
}