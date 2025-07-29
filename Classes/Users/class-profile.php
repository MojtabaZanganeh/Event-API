<?php
namespace Classes\Users;
use Classes\Base\Sanitizer;
use Classes\Base\Response;

/**
 * Class User
 *
 * Manages user-related operations such as retrieving user details by phone number and username.
 *
 * @package Classes\User
 */
class Profile extends Users
{
    use Sanitizer;

    public function get_profile($params)
    {
        $user = $this->check_role();

        $sql = "SELECT 
                    COALESCE(rating_stats.average_score, 0) AS rating_avg,
                    COALESCE(rating_stats.total_ratings, 0) AS rating_count,
                    COALESCE(past_reservations.total_past_reservations, 0) AS events_joined
                FROM 
                    users u
                LEFT JOIN (
                    SELECT 
                        to_user_id,
                        AVG(score) AS average_score,
                        COUNT(*) AS total_ratings
                    FROM 
                        ratings
                    GROUP BY 
                        to_user_id
                ) rating_stats ON u.id = rating_stats.to_user_id
                LEFT JOIN (
                    SELECT 
                        r.user_id,
                        COUNT(*) AS total_past_reservations
                    FROM 
                        reservations r
                    INNER JOIN 
                        events e ON r.event_id = e.id
                    WHERE 
                        e.start_time < NOW()
                    GROUP BY 
                        r.user_id
                ) past_reservations ON u.id = past_reservations.user_id
                WHERE 
                    u.id = ?;";

        $other_data = $this->getData($sql, [$user['id']]);

        if (!$other_data) {
            Response::error('اطلاعات دریافت نشد');
        }

        $user['rating_avg'] = $other_data['rating_avg'];
        $user['rating_count'] = $other_data['rating_count'];
        $user['events_joined'] = $other_data['events_joined'];

        Response::success('اطلاعات پروفایل دریافت شد', 'profileData', $user);
    }

    public function update_profile($params)
    {
        $user = $this->check_role();

        $this->check_params($params, [['birth_date', 'gender']]);
        $birth_date = $params['birth_date'] ? $this->convert_jalali_to_miladi($params['birth_date']) : null;
        $gender = $params['gender'] ?? null;

        $update_profile = $this->updateData(
            "UPDATE {$this->table['users']} SET `birth_date` = ?, `gender` = ? WHERE `id` = ?",
            [$birth_date, $gender, $user['id']]
        );

        if ($update_profile) {
            Response::success('پروفایل بروزرسانی شد');
        }

        Response::error('خطا در بروزرسانی پروفایل');
    }
}