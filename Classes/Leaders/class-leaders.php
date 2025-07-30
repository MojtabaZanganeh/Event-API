<?php
namespace Classes\Leaders;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Leaders extends Users
{
    use Base, Sanitizer;

    public function get_leaders()
    {
        $sql =
            "SELECT
            l.*,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            u.avatar,
            COALESCE(e.event_count, 0) AS event_count,
            COALESCE(f.follower_count, 0) AS follower_count,
            COALESCE(rating_stats.average_score, 0) AS rating_avg,
            COALESCE(rating_stats.total_ratings, 0) AS rating_count,
            JSON_ARRAYAGG(ec.name) AS categories
        FROM {$this->table['leaders']} l
        LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
        LEFT JOIN JSON_TABLE(
            l.categories_id,
            '$[*]' COLUMNS (category_id VARCHAR(255) PATH '$')
        ) AS jt ON TRUE
        LEFT JOIN {$this->table['event_categories']} ec ON jt.category_id = ec.id
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
            SELECT 
                to_user_id,
                AVG(score) AS average_score,
                COUNT(*) AS total_ratings
            FROM 
                ratings
            GROUP BY 
                to_user_id
        ) rating_stats ON u.id = rating_stats.to_user_id
        GROUP BY l.id, u.id;";

        $leaders = $this->getData($sql, [], true);

        if (!$leaders) {
            Response::success('لیدری یافت نشد');
        }

        foreach ($leaders as &$leader) {
            $leader['categories_id'] = json_decode($leader['categories_id']);
            $leader['categories'] = json_decode($leader['categories']);
            $leader['rating_avg'] = number_format($leader['rating_avg'], 2);
        }
        Response::success('لیدرها دریافت شد', 'allLeaders', $leaders);
    }

    public function get_leader_profile_data()
    {
        $user = $this->check_role(['leader']);

        $sql = "SELECT 
                    COALESCE(rating_stats.average_score, 0) AS rating_avg,
                    COALESCE(rating_stats.total_ratings, 0) AS rating_count,
                    COALESCE(hosted_events.total_hosted, 0) AS events_hosted,
                    COALESCE(followers.followers_count, 0) AS followers_count,
                    COALESCE(earnings.total_earnings, 0) AS total_earnings,
                    COALESCE(categories.category_names, '[]') AS categories
                FROM 
                    leaders l
                INNER JOIN users u ON l.user_id = u.id
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
                        creator_id,
                        COUNT(*) AS total_hosted
                    FROM 
                        events
                    WHERE 
                        start_time < NOW()
                    GROUP BY 
                        creator_id
                ) hosted_events ON u.id = hosted_events.creator_id
                LEFT JOIN (
                    SELECT 
                        leader_id,
                        COUNT(*) AS followers_count
                    FROM 
                        leader_followers
                    GROUP BY 
                        leader_id
                ) followers ON l.id = followers.leader_id
                LEFT JOIN (
                    SELECT 
                        e.creator_id,
                        SUM(t.amount) AS total_earnings
                    FROM 
                        transactions t
                    INNER JOIN 
                        reservations r ON t.reservation_id = r.id
                    INNER JOIN 
                        events e ON r.event_id = e.id
                    WHERE 
                        t.status = 'paid'
                    GROUP BY 
                        e.creator_id
                ) earnings ON u.id = earnings.creator_id
                LEFT JOIN (
                    SELECT 
                        l.id AS leader_id,
                        JSON_ARRAYAGG(ec.name) AS category_names
                    FROM 
                        leaders l
                    LEFT JOIN 
                        event_categories ec ON JSON_CONTAINS(l.categories_id, CAST(ec.id AS CHAR))
                    GROUP BY 
                        l.id
                ) categories ON l.id = categories.leader_id
                WHERE 
                    u.id = ?;";

        $leader_data = $this->getData($sql, [$user['id']]);

        if (!$leader_data) {
            Response::error('اطلاعاتی دریافت نشد');
        }

        $leader_data['rating_avg'] = number_format($leader_data['rating_avg'], 2);

        $leader_data['categories'] = $leader_data['categories'] ? json_decode($leader_data['categories']) : [];
        Response::success('اطلاعات پروفایل دریافت شد', 'leaderData', $leader_data);
    }
}