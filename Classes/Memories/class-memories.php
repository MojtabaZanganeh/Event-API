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

        $single_mempry = $memory_uuid ? 'AND m.uuid = ?' : '';

        $sql = "SELECT 
            m.id AS id,
            m.uuid AS uuid,
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
            --     IFNULL(GROUP_CONCAT(DISTINCT CONCAT('{\"type\":\"', mm.type, '\",\"url\":\"', mm.url, '\"}') SEPARATOR ','), ''),
            --     ']'
            -- ) AS medias,
            m.caption,
            mm.url as thumbnail,
            DATE_FORMAT(m.created_at, '%Y/%m/%d %H:%i') AS created_at,
            (
                SELECT COUNT(*) 
                FROM {$this->table['memory_likes']} ml 
                WHERE ml.memory_id = m.id
            ) AS like_count,
            (
                SELECT COUNT(*) 
                FROM {$this->table['memory_comments']} mc 
                WHERE mc.memory_id = m.id
            ) AS comment_count,
            CONCAT(
                '[',
                IFNULL(GROUP_CONCAT(DISTINCT CONCAT('\"', mh.hashtag, '\"') SEPARATOR ','), ''),
                ']'
            ) AS hashtags,
            (SELECT COUNT(*) FROM {$this->table['memory_likes']} ml WHERE ml.memory_id = m.id AND ml.user_id = ?) > 0 AS is_liked,
            (SELECT COUNT(*) FROM {$this->table['memories_saved']} ms WHERE ms.memory_id = m.id AND ms.user_id = ?) > 0 AS is_saved,
            (SELECT COUNT(*) FROM {$this->table['leader_followers']} lf WHERE lf.leader_id = u.id AND lf.follower_id = ?) > 0 AS is_following

        FROM {$this->table['memories']} m
        JOIN {$this->table['users']} u ON m.user_id = u.id
        LEFT JOIN {$this->table['events']} e ON m.event_id = e.id
        LEFT JOIN {$this->table['memory_medias']} mm ON mm.id = m.thumbnail_id
        LEFT JOIN {$this->table['memory_hashtags']} mh ON mh.memory_id = m.id
        WHERE m.status = 'published' $single_mempry
        GROUP BY m.id
        ORDER BY m.created_at DESC";

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
            $memory['thumbnail'] = $this->get_full_image_url($memory['thumbnail']);
        }

        Response::success('خاطرات دریافت شد', 'allMemories', $memories);
    }


    public function get_memoriy_medias($params)
    {
        $this->check_params($params, ['uuid']);

        $uuid = $params['uuid'];

        $sql = "SELECT 
            mm.type,
            mm.url
        FROM {$this->table['memories']} m
        JOIN {$this->table['memory_medias']} mm ON m.id = mm.memory_id
        WHERE m.uuid = ?
            AND m.status = 'published' 
        ORDER BY mm.id;";

        $memory_medias = $this->getData($sql, [$uuid], true);

        foreach ($memory_medias as &$memory_media) {
            $memory_media['url'] = $this->get_full_image_url($memory_media['url']);
        }

        if (!$memory_medias) {
            Response::success('رسانه ای یافت نشد');
        }

        Response::success('رسانه های خاطره دریافت شد', 'memory_medias', $memory_medias);
    }

    public function get_stories()
    {
        $sql = "SELECT 
                    m.id, 
                    m.caption,
                    m.created_at,
                    e.category_id,
                    ec.name AS category,
                    JSON_OBJECT(
                        'id', u.id,
                        'name', CONCAT(u.first_name, ' ', u.last_name),
                        'avatar', u.avatar
                    ) AS user
                FROM {$this->table['memories']} m
                LEFT JOIN {$this->table['events']} e ON m.event_id = e.id
                LEFT JOIN {$this->table['event_categories']} ec ON e.category_id = ec.id
                LEFT JOIN {$this->table['users']} u ON m.user_id = u.id
                
                WHERE m.status = 'published'
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