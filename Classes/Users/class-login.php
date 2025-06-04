<?php
namespace Classes\Users;
use Classes\Base\Base;
use Classes\Base\Sanitizer;
use Classes\Base\Response;
use Classes\Users\Users;
use Classes\Users\Authentication;

/**
 * Class Login
 *
 * Manages user registration, login, and user status checks.
 * It handles the logic for checking if a user is registered, registering a new user, 
 * and logging the user in by generating a JWT token.
 *
 * @package Classes\User
 */
class Login extends Users
{
    use Base, Sanitizer;

    /**
     * Registers a new user.
     *
     * This method registers a new user by inserting the user's details into the database.
     * It also generates a random password and assigns it to the new user.
     * If successful, it returns a success response with the user ID.
     *
     * @param array $params Array of input parameters, including:
     *                      - string $params['phone'] The phone number of the user (required)
     *                      - string $params['type'] The type of user (required)
     *                      - string $params['fname'] The first name of the user (required)
     *                      - string $params['category'] The category of the user (required)
     *                      - string $params['bname'] The business name (optional)
     * @return void
     */
    public function user_register($params)
    {
        $this->check_params($params, ['first_name', 'last_name', 'student_id', 'phone', 'dormitory']);
        
        $first_name = $this->check_input($params['first_name'], 'fa_name');
        $last_name = $this->check_input($params['last_name'], 'fa_name');
        $student_id = $this->check_input($params['student_id'], 'student_id');
        $phone = $this->check_input($params['phone'], 'phone');
        $dormitory = $this->check_input($params['dormitory'], 'dormitory');

        $auth_obj = new Authentication();
        $phone_validate = $auth_obj->verify_phone($phone);

        if (!$phone_validate) {
            Response::error('شماره اعتبار سنجی نشده است.');
        }

        $user = $this->get_user_by_phone($phone);
        if ($user) {
            Response::error('شماره قبلاً ثبت شده است.');
        }

        $now = $this->current_time();

        $sql = "INSERT INTO {$this->table['users']} (`first_name`, `last_name`, `student_id`, `phone`, `dormitory`, `created_at`) VALUES (?, ?, ?, ?, ?, ?)";
        $execute = [
            $first_name,
            $last_name,
            $student_id,
            $phone,
            $dormitory,
            $now
        ];

        $user_id = $this->insertData($sql, $execute);

        if ($user_id) {
            $jwt_obj = new Authentication();
            $jwt_token = $jwt_obj->generate_token([
                'user_id' => $user_id,
                'phone' => $phone,
                'dormitory' => $dormitory,
                'role' => 'user'
            ]);

            $user = [
                'id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'student_id' => $student_id,
                'dormitory' => $dormitory,
                'role' => 'user',
                'token' => $jwt_token,
            ];

            Response::success('ثبت نام کاربر با موفقیت انجام شد', 'user', $user);
        } else {
            Response::error('ثبت نام کاربر انجام نشد');
        }
    }


    /**
     * Logs the user in and generates a JWT token.
     *
     * This method creates a JWT token for the user upon successful login. 
     * It also decides whether to set a short or long expiration time based on the "remember" option.
     *
     * @param array $params Array of input parameters, including:
     *                      - int $params['user_id'] The user ID to be logged in (required)
     *                      - bool $params['remember'] If set to true, the session expiration time will be extended (optional)
     * @return void
     */
    public function user_login($params)
    {
        $this->check_params($params, ['phone']);
        
        $phone = $this->check_input($params['phone'], 'phone');
        $password = $params['password'] ?? null;
        $code = $params['code'] ?? null;

        if (!$password && !$code) {
            Response::error('خطا در دریافت اطلاعات اعتبارسنجی');
        }

        $user = $this->get_user_by_phone($phone);
        if (!$user) {
            Response::error('کاربری با این شماره موبایل یافت نشد');
        }

        $auth_obj = new Authentication();

        if ($code) {
            $auth_obj->verify_code(
                [
                    'phone' => $phone,
                    'code' => $code,
                    'user' => true
                ]
            );
        } else {

            if ($this->check_password($phone, $password) === false) {
                Response::error('رمز عبور اشتباه است');
                return;
            }

            $jwt_token = $auth_obj->generate_token([
                'user_id' => $user['id'],
                'phone' => $user['phone'],
                'dormitory' => $user['dormitory'],
                'role' => $user['role']
            ]);
            $user['token'] = $jwt_token;

            Response::success('ورود با موفقیت انجام شد', 'user', $user);
        }

    }

    public function user_validate($params)
    {
        $this->check_params($params, ['token']);

        $token = $params['token'];

        $token_obj = new Authentication();
        $token_decoded = $token_obj->check_token($token);

        $user = $this->get_user_by_phone($token_decoded->phone);

        if ($user) {
            if ($user['role'] == $token_decoded->role) {
                $user['token'] = $token;
                Response::success('نشست معتبر است', 'user', $user);
            }
        }

        Response::error('نشست معتبر نیست');

    }
}