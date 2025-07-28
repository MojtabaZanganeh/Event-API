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
        GROUP BY l.id, u.id;";

        $leaders = $this->getData($sql, [], true);

        if (!$leaders) {
            Response::error('لیدری یافت نشد');
        }

        foreach ($leaders as &$leader) {
            $leader['categories_id'] = json_decode($leader['categories_id']);
            $leader['categories'] = json_decode($leader['categories']);
        }
        Response::success('لیدرها دریافت شد', 'allLeaders', $leaders);
    }
}