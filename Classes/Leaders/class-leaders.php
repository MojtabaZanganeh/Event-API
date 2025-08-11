<?php
namespace Classes\Leaders;

use Classes\Base\Base;
use Classes\Base\Error;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Events\Categories;
use Classes\Users\Users;

class Leaders extends Users
{
    use Base, Sanitizer;

    public function get_leader_id_by_user_id($user_id)
    {
        $sql = "SELECT id FROM {$this->table['leaders']} WHERE user_id = ?";
        return $this->getData($sql, [$user_id])['id'] ?? null;
    }

    public function get_leaders()
    {
        $sql = "SELECT
                    l.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.avatar,
                    COALESCE(e.event_count, 0) AS events_hosted,
                    COALESCE(f.follower_count, 0) AS followers_count,
                    COALESCE(rating_stats.average_score, 0) AS rating_avg,
                    COALESCE(rating_stats.total_ratings, 0) AS rating_count,
                    l.categories_id
                FROM {$this->table['leaders']} l
                LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
                LEFT JOIN (
                    SELECT leader_id, COUNT(*) AS event_count
                    FROM {$this->table['events']}
                    GROUP BY leader_id
                ) e ON l.id = e.leader_id
                LEFT JOIN (
                    SELECT leader_id, COUNT(*) AS follower_count
                    FROM {$this->table['leader_followers']}
                    GROUP BY leader_id
                ) f ON l.id = f.leader_id
                LEFT JOIN (
                    SELECT to_user_id, AVG(score) AS average_score, COUNT(*) AS total_ratings
                    FROM {$this->table['ratings']}
                    GROUP BY to_user_id
                ) rating_stats ON u.id = rating_stats.to_user_id
                GROUP BY l.id, u.id
        ";

        $leaders = $this->getData($sql, [], true);

        if (!$leaders) {
            Response::success('لیدری یافت نشد');
        }

        foreach ($leaders as &$leader) {

            if ($leader['categories_id']) {
                $categories_id = json_decode($leader['categories_id']);

                $category_obj = new Categories();
                $categories = $category_obj->get_categories_by_id($categories_id);

                if ($categories) {
                    $leader['categories'] = $categories ?? null;
                }
            }

            $leader['rating_avg'] = number_format($leader['rating_avg'], 2);
        }

        Response::success('لیدرها دریافت شد', 'allLeaders', $leaders);
    }

    public function get_leader_profile_data()
    {
        $user = $this->check_role(['leader']);

        $sql = "SELECT
                    l.bio,
                    COALESCE(rating_stats.average_score, 0) AS rating_avg,
                    COALESCE(rating_stats.total_ratings, 0) AS rating_count,
                    COALESCE(hosted_events.total_hosted, 0) AS events_hosted,
                    COALESCE(followers.followers_count, 0) AS followers_count,
                    COALESCE(earnings.total_earnings, 0) AS total_earnings,
                    l.categories_id
                FROM {$this->table['leaders']} l
                INNER JOIN {$this->table['users']} u ON l.user_id = u.id
                LEFT JOIN (
                    SELECT to_user_id, AVG(score) AS average_score, COUNT(*) AS total_ratings
                    FROM {$this->table['ratings']}
                    GROUP BY to_user_id
                ) rating_stats ON u.id = rating_stats.to_user_id
                LEFT JOIN (
                    SELECT leader_id, COUNT(*) AS total_hosted
                    FROM {$this->table['events']}
                    WHERE start_time < NOW()
                    GROUP BY leader_id
                ) hosted_events ON l.id = hosted_events.leader_id
                LEFT JOIN (
                    SELECT leader_id, COUNT(*) AS followers_count
                    FROM {$this->table['leader_followers']}
                    GROUP BY leader_id
                ) followers ON l.id = followers.leader_id
                LEFT JOIN (
                    SELECT e.creator_id, SUM(t.amount) AS total_earnings
                    FROM {$this->table['transactions']} t
                    INNER JOIN {$this->table['reservations']} r ON t.reservation_id = r.id
                    INNER JOIN {$this->table['events']} e ON r.event_id = e.id
                    WHERE t.status = 'paid'
                    GROUP BY e.creator_id
                ) earnings ON u.id = earnings.creator_id
                WHERE u.id = ?
                GROUP BY l.id
        ";

        $leader_data = $this->getData($sql, [$user['id']]);

        if (!$leader_data) {
            Response::error('اطلاعاتی دریافت نشد');
        }

        if ($leader_data['categories_id']) {
            $categories_id = json_decode($leader_data['categories_id']);

            $category_obj = new Categories();
            $categories = $category_obj->get_categories_by_id($categories_id);

            if ($categories) {
                $leader_data['categories'] = $categories ?? null;
            }
        }

        $leader_data['rating_avg'] = number_format($leader_data['rating_avg'], 2);

        Response::success('اطلاعات پروفایل دریافت شد', 'leaderData', $leader_data);
    }
    public function update_leader_profile($leader_data)
    {
        $user = $this->check_role(['leader']);

        $categories_id = array_column($leader_data['categories'], 'id');

        $update_leader_profile = $this->updateData(
            "UPDATE {$this->table['leaders']} SET `bio` = ?, `categories_id` = ? WHERE `user_id` = ?",
            [$leader_data['bio'] ?? null, json_encode($categories_id) ?? null, $user['id']]
        );

        if (!$update_leader_profile) {
            Response::error('خطا در بروزرسانی پروفایل');
        }

        return true;
    }
}
