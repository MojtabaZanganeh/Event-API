<?php
namespace Classes\Reservations;
use Classes\Base\Base;
use Classes\Base\Sanitizer;
use Classes\Events\Events;
use Classes\Base\Response;
use Classes\Base\Error;

class Discounts extends Events
{
    use Base, Sanitizer;

    public function check_discount_code($params)
    {
        $this->check_params($params, ['discount_code', 'event_id']);

        Response::success('کد معتبر است', 'discountAmount', 15000);
    }
}