<?php
namespace Classes\Memories;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Memories extends Users
{
    use Base, Sanitizer;

    public function get_memories($params)
    {
        $memory_uuid = $params['uuid'] ?? null;
        $user_id = 1;

        $single_mempry = $memory_uuid ? 'AND p.uuid = ?' : '';

        $sql = "SELECT 
            p.id AS id,
            p.uuid AS uuid,
            CONCAT(
                '{',
                '\"name\":\"', IFNULL(e.title,''), '\",',
                '\"slug\":\"', IFNULL(e.title,''), '\",',
                '\"location\":\"', IFNULL(e.location,''), '\",',
                '\"start_time\":\"', IFNULL(DATE_FORMAT(e.start_time, '%Y/%m/%d %H:%i'),''), '\"',
                '}'
            ) AS event,
            CONCAT(
                '{',
                '\"id\":\"', u.id, '\",',
                '\"name\":\"', CONCAT(u.first_name,' ',u.last_name), '\",',
                '\"avatar\":\"', IFNULL(u.avatar,''), '\"',
                '}'
            ) AS user,
            -- CONCAT(
            --     '[',
            --     IFNULL(GROUP_CONCAT(DISTINCT CONCAT('{\"type\":\"', pm.media_type, '\",\"url\":\"', pm.media_url, '\"}') SEPARATOR ','), ''),
            --     ']'
            -- ) AS medias,
            (
                SELECT pm.thumbnail_url 
                FROM {$this->table['post_media']} pm 
                WHERE pm.post_id = p.id 
                ORDER BY pm.id 
                LIMIT 1
            ) AS thumbnail_url,
            p.caption,
            DATE_FORMAT(p.created_at, '%Y/%m/%d %H:%i') AS created_at,
            (
                SELECT COUNT(*) 
                FROM {$this->table['post_likes']} pl 
                WHERE pl.post_id = p.id
            ) AS like_count,
            (
                SELECT COUNT(*) 
                FROM {$this->table['post_comments']} pc 
                WHERE pc.post_id = p.id
            ) AS comment_count,
            CONCAT(
                '[',
                IFNULL(GROUP_CONCAT(DISTINCT CONCAT('\"', ph.hashtag, '\"') SEPARATOR ','), ''),
                ']'
            ) AS hashtags,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?) > 0 AS is_liked,
            (SELECT COUNT(*) FROM post_saved ps WHERE ps.post_id = p.id AND ps.user_id = ?) > 0 AS is_saved,
            (SELECT COUNT(*) FROM leader_followers lf WHERE lf.leader_id = u.id AND lf.follower_id = ?) > 0 AS is_following

        FROM {$this->table['posts']} p
        JOIN {$this->table['users']} u ON p.user_id = u.id
        LEFT JOIN {$this->table['events']} e ON p.event_id = e.id
        LEFT JOIN {$this->table['post_media']} pm ON pm.post_id = p.id
        LEFT JOIN {$this->table['post_hashtags']} ph ON ph.post_id = p.id
        WHERE p.status = 'published' $single_mempry
        GROUP BY p.id
        ORDER BY p.created_at DESC";

        $params_bind = isset($params['uuid']) ? [$user_id, $user_id, $user_id, $memory_uuid] : [$user_id, $user_id, $user_id];

        $memories = $this->getData($sql, $params_bind, true);

        if (!$memories) {
            Response::success('خاطره ای یافت نشد');
        }

        foreach ($memories as &$memory) {
            $memory['event'] = $memory['event'] ? json_decode($memory['event']) : null;
            $memory['user'] = $memory['user'] ? json_decode($memory['user']) : null;
            // $memory['medias'] = $memory['medias'] ? json_decode($memory['medias']) : [];
            $memory['hashtags'] = $memory['hashtags'] ? json_decode($memory['hashtags']) : [];
        }

        Response::success('خاطرات دریافت شد', 'allMemories', $memories);
    }


    public function get_memoriy_medias($params)
    {
        $this->check_params($params, ['uuid']);

        $uuid = $params['uuid'];

        $sql = "SELECT 
            pm.media_type AS type,
            pm.media_url AS url
        FROM {$this->table['posts']} p
        JOIN {$this->table['post_media']} pm ON p.id = pm.post_id
        WHERE p.uuid = ?
            AND p.status = 'published' 
        ORDER BY pm.id;";

        $memory_medias = $this->getData($sql, [$uuid], true);

        if (!$memory_medias) {
            Response::success('رسانه ای یافت نشد');
        }

        Response::success('رسانه های خاطره دریافت شد', 'memory_medias', $memory_medias);
    }

    public function get_stories()
    {
        $sql = "SELECT 
                    p.id, 
                    p.caption,
                    p.created_at,
                    e.category_id,
                    ec.name AS category,
                    JSON_OBJECT(
                        'id', u.id,
                        'name', CONCAT(u.first_name, ' ', u.last_name),
                        'avatar', u.avatar
                    ) AS user
                FROM {$this->table['posts']} p
                LEFT JOIN {$this->table['events']} e ON p.event_id = e.id
                LEFT JOIN {$this->table['event_categories']} ec ON e.category_id = ec.id
                LEFT JOIN {$this->table['users']} u ON p.user_id = u.id
                
                WHERE p.status = 'published'
                ORDER BY created_at DESC";

        $stories = $this->getData($sql, [], true);

        if (!$stories) {
            Response::error('خطا در دریافت داستان ها');
        }

        foreach ($stories as &$story) {
            $story['user'] = json_decode($story['user']);
        }

        Response::success('داستان ها با موفقیت دریافت شد', 'allStories', $stories);
    }
}