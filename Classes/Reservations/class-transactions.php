<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Database;
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

    public function add_payment($reservation_id, $user_id, $event_id, $amount, Database $db)
    {
        if (!$reservation_id || !$user_id || !$event_id || !isset($amount)) {
            return null;
        }

        $random_string = $this->get_random('mix', rand(5, 15));
        $random_string_pad = str_pad($random_string, 35, '0', STR_PAD_LEFT);
        $authority = $amount > 0 ? "A$random_string_pad" : "F$random_string_pad";
        $current_time = $this->current_time();

        $transaction_id = $db->insertData(
            "INSERT INTO {$db->table['transactions']} (`reservation_id`, `type`, `amount`, `status`, `authority`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $reservation_id,
                'payment',
                $amount,
                'pending',
                $authority,
                $current_time,
                $current_time
            ]
        );

        if (!$transaction_id) {
            return false;
        }

        $payment_url = $_ENV['SITE_URL'];
        $payment_url .= $amount > 0 ? "/events/demo-payment?Authority=$authority" : "/events/payment-check?Authority=$authority&Status=OK";

        return $payment_url;
    }

    public function check_payment_status($params)
    {
        $this->check_params($params, ['authority']);

        $payment_data = $this->getData(
            "SELECT
                    t.id,
                    t.amount,
                    t.reservation_id,
                    t.status,
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

        if ($payment_data['status'] == 'paid') {
            Response::error('تراکنش قبلا بررسی شده است، برای دیدن بلیط به پروفایل مراجعه کنید');
        }

        $ref_id = $this->get_random('int', rand(10, 15), $this->table['transactions'], 'ref_id');

        $db = new Database();
        $db->beginTransaction();

        $update_payment = $db->updateData(
            "UPDATE {$db->table['transactions']} SET `status` = ?, `ref_id` = ?, `paid_at` = ? WHERE authority = ?",
            [
                'paid',
                $ref_id,
                $this->current_time(),
                $params['authority']
            ]
        );

        if (!$update_payment) {
            Response::error('خطا در ثبت تراکنش، صفحه را رفرش کنید و در صورت بروز مجدد این خطا، با پشتیبانی تماس بگیرید');
        }

        $update_reservation = $this->reservation_paid($payment_data['reservation_id'], $db);

        if (!$update_reservation) {
            Response::error('خطا در ثبت رزرو، صفحه را رفرش کنید و در صورت بروز مجدد این خطا، با پشتیبانی تماس بگیرید');
        }

        $db->commit();

        $payment_data['ref_id'] = $ref_id;
        $payment_data['event'] = json_decode($payment_data['event'], true);
        $payment_data['event']['image'] = $this->get_full_image_url($payment_data['event']['image']);

        Response::success('پرداخت انجام شد', 'paymentStatus', $payment_data);
    }
}