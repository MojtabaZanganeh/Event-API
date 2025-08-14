<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Sanitizer;
use Classes\Events\Events;
use Classes\Base\Response;
use Classes\Base\Error;
use DateTime;

class Discounts extends Events
{
    use Base, Sanitizer;

    private function calculate_discount_amount(array $discount_data): float
    {
        if (!empty($discount_data['discount_percent'])) {
            $event_price = $discount_data['event_price'] ?? 0;
            $discount_percent = $discount_data['discount_percent'];

            $calculated_amount = $event_price * ($discount_percent / 100);

            if (!empty($discount_data['discount_max'])) {
                return min($calculated_amount, $discount_data['discount_max']);
            }

            return $calculated_amount;
        }

        return $discount_data['discount_constant'] ?? 0;
    }

    public function check_discount_code($params)
    {
        $this->check_params($params, ['discount_code', 'event_id']);

        $discount_data = $this->getData(
            "SELECT
                    dc.id,
                    dc.code,
                    dc.expires_on,
                    dc.event_id,
                    dc.category_id,
                    dc.leader_id,
                    dc.capacity,
                    COALESCE(SUM(r.group_members), 0) AS filled,
                    dc.discount_percent,
                    dc.discount_max,
                    dc.discount_constant,
                    e.category_id AS event_category_id,
                    e.leader_id AS event_leader_id,
                    e.price AS event_price
                FROM {$this->table['discount_codes']} dc
                LEFT JOIN {$this->table['reservations']} r ON dc.id = r.discount_code_id AND r.status != 'canceled'
                LEFT JOIN {$this->table['events']} e ON dc.event_id = e.id
                WHERE dc.code = ?
                GROUP BY dc.id, dc.code, dc.expires_on, dc.event_id, dc.category_id, dc.leader_id, dc.capacity,
                dc.discount_percent, dc.discount_max, dc.discount_constant, e.category_id, e.leader_id, e.price",
            [$params['discount_code']]
        );

        if (!$discount_data) {
            Response::error('کد تخفیف یافت نشد');
        }

        $discount_datetime = new DateTime($discount_data['expires_on']);
        $now_datetime = new DateTime();
        if ($discount_datetime < $now_datetime) {
            Response::error('مهلت استفاده از کد تخفیف تمام شده است');
        }

        if (
            ($discount_data['event_id'] && $discount_data['event_id'] != $params['event_id']) ||
            ($discount_data['category_id'] && $discount_data['category_id'] != $discount_data['event_category_id']) ||
            ($discount_data['leader_id'] && $discount_data['leader_id'] != $params['event_leader_id'])
        ) {
            Response::error('کد تخفیف شامل این رویداد نمی شود');
        }

        if ($discount_data['capacity'] > 0 && $discount_data['filled'] >= $discount_data['capacity']) {
            Response::error('ظرفیت استفاده از کد تخفیف تمام شده است');
        }

        $discount_amount = $this->calculate_discount_amount($discount_data);

        if (!empty($params['return'])) {
            return [
                'id' => $discount_data['id'],
                'amount' => $discount_amount
            ];
        } else {
            Response::success('کد معتبر است', 'discountAmount', $discount_amount);
        }
    }
}