<?php
namespace Classes\Users;
use Classes\Base\Database;
use Classes\Base\Sanitizer;
use Classes\Base\Response;
use Exception;

/**
 * Class User
 *
 * Manages user-related operations such as retrieving user details by phone number and username.
 *
 * @package Classes\User
 */
class Users extends Database
{

    use Sanitizer;
    private $user_id;

    private const USER_COLUMNS = 'id, first_name, last_name, student_id, phone, dormitory, role, created_at';

    public function __construct($user_id = null)
    {
        parent::__construct();
        $this->user_id = $user_id;
    }

    /**
     * Retrieves the user ID by phone number.
     *
     * This method queries the database to find a user by their phone number.
     * If a user with the provided phone number exists, it returns the user's ID.
     * If no user is found, it returns an error message.
     *
     * @param string $phone_number The phone number of the user to search for (required)
     * @return int|null The user ID if the user exists, or null if the user is not found
     */
    public function get_id_by_phone($phone): int|null
    {
        $sql = "SELECT id FROM {$this->table['users']} WHERE phone = ?";
        $user = $this->getData($sql, [$phone]);

        return $user ? $user['id'] : null;
    }

    public function get_user_by_id($user_id, $columns = self::USER_COLUMNS): array|null
    {
        $sql = "SELECT {$columns} FROM {$this->table['users']} WHERE id = ?";
        $user = $this->getData($sql, [$user_id]);
        return $user ?: null;
    }

    public function get_user_by_phone($phone, $columns = self::USER_COLUMNS): array|null
    {
        $user_id = $this->get_id_by_phone($phone);
        $user = $this->get_user_by_id($user_id, $columns);

        return $user ?: null;
    }

    public function check_password($phone, $password): bool
    {
        $user_id = $this->get_id_by_phone($phone);
        $user = $this->get_user_by_id($user_id, 'password');
        return password_verify($password, $user['password']);
    }

    public function check_role($role = 'user', $token = null)
    {
        try {
            $token = is_null($token) ? getallheaders()['Authorization'] : $token;
            if (!$token) {
                throw new Exception();
            }

            $auth_obj = new Authentication();
            $token_decoded = $auth_obj->check_token($token);

            $user = $this->get_user_by_id($token_decoded->user_id, 'dormitory, role');
            if (!$user || $user['dormitory'] != $token_decoded->dormitory || $user['role'] != $token_decoded->role) {
                throw new Exception();
            }

            $hasAccess = match ($role) {
                'user' => $token_decoded &&
                $token_decoded->exp > time() &&
                isset($token_decoded->role, $token_decoded->dormitory) &&
                ($token_decoded->role === 'user' || str_contains($token_decoded->role, 'admin')),

                'admin' => $token_decoded &&
                $token_decoded->exp > time() &&
                isset($token_decoded->role, $token_decoded->dormitory) &&
                $token_decoded->role === 'admin',

                'admin-dormitory' => $token_decoded &&
                $token_decoded->exp > time() &&
                isset($token_decoded->role, $token_decoded->dormitory) &&
                ($token_decoded->role === 'admin' || $token_decoded->role === 'admin-' . $token_decoded->dormitory),

                default => false,
            };

            if (!$hasAccess) {
                throw new Exception();
            }

            return $token_decoded;
        } catch (Exception $e) {
            Response::error('شما دسترسی لازم را ندارید');
        }
    }

    public function edit_profile($params)
    {
        $this->check_role('user');

        $this->check_params($params, ['user_id', 'values']);
        $user_id = $params['user_id'];
        $profile_data = $params['values'];
        $this->check_params($profile_data, ['first_name', 'last_name', 'student_id']);
        $first_name = $profile_data['first_name'];
        $last_name = $profile_data['last_name'];
        $student_id = $profile_data['student_id'];

        $update_profile = $this->updateData(
            "UPDATE {$this->table['users']} SET `first_name` = ?, `last_name` = ?, `student_id` = ? WHERE `id` = ?",
            [$first_name, $last_name, $student_id, $user_id]
        );

        if ($update_profile) {
            Response::success('پروفایل با موفقیت ویرایش شد');
        }

        Response::error('خطا در آپدیت پروفایل');
    }
}