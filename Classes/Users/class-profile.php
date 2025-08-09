<?php
namespace Classes\Users;
use Classes\Base\Sanitizer;
use Classes\Base\Response;
use Classes\Leaders\Leaders;

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
                        {$this->table['ratings']}
                    GROUP BY 
                        to_user_id
                ) rating_stats ON u.id = rating_stats.to_user_id
                LEFT JOIN (
                    SELECT 
                        r.user_id,
                        COUNT(*) AS total_past_reservations
                    FROM 
                        {$this->table['reservations']} r
                    INNER JOIN 
                        {$this->table['events']} e ON r.event_id = e.id
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

        $user['rating_avg'] = number_format($other_data['rating_avg'], 2);
        $user['rating_count'] = $other_data['rating_count'];
        $user['events_joined'] = $other_data['events_joined'];
        $user['birth_date'] = $user['birth_date'] ? $this->convert_miladi_to_jalali($user['birth_date']) : null;

        unset($user['id']);
        unset($user['password']);

        Response::success('اطلاعات پروفایل دریافت شد', 'profileData', $user);
    }

    public function update_user_profile($params)
    {
        $user = $this->check_role();

        $this->check_params($params, ['profileData']);

        $profile_data = $params['profileData'];
        $birth_date = $profile_data['birth_date'] ? $this->convert_jalali_to_miladi($profile_data['birth_date']) : null;
        $gender = $profile_data['gender'] ?? null;

        $update_profile = $this->updateData(
            "UPDATE {$this->table['users']} SET `birth_date` = ?, `gender` = ? WHERE `id` = ?",
            [$birth_date, $gender, $user['id']]
        );

        if ($update_profile) {

            if (isset($params['leaderData']) && $user['role'] === 'leader') {
                $leader_obj = new Leaders();
                $leader_obj->update_leader_profile($params['leaderData']);
            }

            Response::success('پروفایل بروزرسانی شد');
        }

        Response::error('خطا در بروزرسانی پروفایل');
    }
}