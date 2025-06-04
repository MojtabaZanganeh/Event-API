<?php
namespace Classes\Reservations;
use Classes\Users\Authentication;
use Classes\Base\Base;
use Classes\Base\Database;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Reservations\Reservations;
use Classes\Trips\Cities;
use Classes\Base\Zarinpal;
use Classes\Trips\Trips;

class Payment extends Zarinpal
{
    use Base, Sanitizer;

    public function pay_link($params)
    {
        $this->check_params($params, ['reserveId', 'token']);

        $reserve_uuid = $params['reserveId'];
        $token = $params['token'];

        $token_obj = new Authentication();
        $token_decoded = $token_obj->check_token($token);

        $reserve_obj = new Reservations();
        $reserve_id = $reserve_obj->get_reserveid_by_uuid($reserve_uuid);
        $reserve = $reserve_obj->get_reserve_by_id($reserve_id);

        if ($reserve) {
            $reserve_id = $reserve['id'];
            $amount = $reserve['amount'];

            // $result = $this->request($amount, 'https://torbattrip.ir/checkpay', 'رزرو سفر', '', $token_decoded->phone, false, false);

            $db = new Database();
            // FOR TEST
            $result['Status'] = 100;
            $result['Authority'] = 'A0000000000000000000000000000' . $this->get_random('mix', 7, $db->table['payments'], 'authority');
            $result['StartPay'] = 'https://a9z.ir/';
            //

            if (isset($result["Status"]) && $result["Status"] == 100) {

                $sql = "INSERT INTO {$db->table['payments']} (reservation_id, amount, status, authority) VALUES (?, ?, ?, ?)";
                $execute = [
                    $reserve_id,
                    $amount,
                    'pending',
                    $result['Authority']
                ];
                $stmt = $db->executeStatement($sql, $execute);

                if ($stmt->affected_rows == 1) {
                    Response::success('تراکنش ایجاد شد', 'link', $result['StartPay']);
                }
            }
        }

        Response::error('خطا در ایجاد تراکنش', ['code' => $result['Status'], 'message' => $result['Message']]);
    }

    public function pay_verify($params)
    {
        $this->check_params($params, ['authority', 'status', 'reserveId', 'token']);

        $authority = $params['authority'];
        $status = $params['status'];
        $reserve_uuid = $params['reserveId'];
        $token = $params['token'];

        $token_obj = new Authentication();
        $token_decoded = $token_obj->check_token($token);

        $db = new Database();

        $reserve_obj = new Reservations();
        $reserve_id = $reserve_obj->get_reserveid_by_uuid($reserve_uuid);
        $reserve = $reserve_obj->get_reserve_by_id($reserve_id, 'uuid, amount, payment_status, reserve_status, trip_id, passenger_phone, seats_reserved');

        if ($reserve) {
            $amount = $reserve['amount'];

            if ($reserve['payment_status'] == 'paid' && $reserve['reserve_status'] == 'reserved') {
                Response::error('این تراکنش قبلا بررسی شده است');
            }
        }

        // $result_pay = $this->verify($authority, $amount, false, false);

        // FOR TEST
        $result_pay['Status'] = 100;
        $result_pay['RefID'] = '56154133165';
        //

        if (isset($result_pay["Status"]) && $result_pay["Status"] == 100) {
            $sql = "UPDATE {$db->table['payments']} SET status = 'success', ref_id = ? WHERE authority = ?";
            $stmt = $db->executeStatement($sql, [$result_pay['RefID'], $authority]);

            if ($stmt->affected_rows != 1) {
                error_log('Failed to update payment status: AUTHORITY: ' . $authority . 'REFID:' . $result_pay['RefID']);
            }

            $update_status = $reserve_obj->update_status('payment_status = ?, reserve_status = ?', ['paid', 'reserved'], $reserve_id);

            if ($update_status) {
                $trip_obj = new Trips();
                $trip = $trip_obj->get_trip_by_id($reserve['trip_id']);

                if ($trip) {

                    $city_obj = new Cities();
                    $origin = $city_obj->get_city_by_id($trip['origin_id']);
                    $destination = $city_obj->get_city_by_id($trip['destination_id']);

                    $qr_code_data = 'http://localhost:5173/mytickets/' . $reserve['uuid'];
                    $qr_code_url = $this->generate_qr_code($reserve['uuid'], $qr_code_data);

                    Response::success('سفر با موفقیت رزرو شد', 'ticket', [
                        'refID' => $result_pay['RefID'],
                        'amount' => $amount,
                        'phoneNumber' => $reserve['passenger_phone'],
                        'tripID' => $reserve['trip_id'],
                        'tripDate' => $trip['departure_time'],
                        'reserveID' => $reserve['uuid'],
                        'origin' => $origin['city'],
                        'originStation' => $origin['station'],
                        'destination' => $destination['city'],
                        'destinationStation' => $destination['station'],
                        'passengerCount' => $reserve['seats_reserved'],
                        'qrCode' => $qr_code_url,
                        'errorMessage' => '',
                        'isSuccess' => true
                    ]);
                }
                Response::error('خطا در دریافت اطلاعات سفر');
            } else {
                Response::error('خطا در بروزرسانی اطلاعات رزرو');
            }
        } else {
            Response::error('پرداخت ناموفق بوده است');
        }

        Response::error('خطا در تایید پرداخت');
    }
}