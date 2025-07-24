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
            JSON_OBJECT(
                'name', e.title,
                'slug', e.title,
                'location', e.location,
                'start_time', DATE_FORMAT(e.start_time, '%Y/%m/%d %H:%i')
            ) AS `event`,
            JSON_OBJECT(
                'id', u.id,
                'name', CONCAT(u.first_name, ' ', u.last_name),
                'avatar', u.avatar
            ) AS user,
            -- (
            -- SELECT JSON_ARRAYAGG(
            --         JSON_OBJECT('type', pm.media_type, 'url', pm.media_url)
            --     )
            --     FROM post_media pm
            --     WHERE pm.post_id = p.id
            -- ) AS medias,
            (
                SELECT pm.thumbnail_url 
                FROM post_media pm 
                WHERE pm.post_id = p.id 
                ORDER BY pm.id 
                LIMIT 1
            ) AS thumbnail_url,
            p.caption,
            DATE_FORMAT(p.created_at, '%Y/%m/%d %H:%i') AS created_at,
            (
                SELECT COUNT(*) 
                FROM post_likes pl 
                WHERE pl.post_id = p.id
            ) AS like_count,
            (
                SELECT COUNT(*) 
                FROM post_comments pc 
                WHERE pc.post_id = p.id
            ) AS comment_count,
            (
                SELECT JSON_ARRAYAGG(ph.hashtag)
                FROM post_hashtags ph
                WHERE ph.post_id = p.id
            ) AS hashtags,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?) > 0 AS is_liked,
            (SELECT COUNT(*) FROM post_saved ps WHERE ps.post_id = p.id AND ps.user_id = ?) > 0 AS is_saved,
            (SELECT COUNT(*) FROM leader_followers lf WHERE lf.leader_id = u.id AND lf.follower_id = ?) > 0 AS is_following

        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN events e ON p.event_id = e.id
        WHERE p.status = 'published' $single_mempry";

        $memories = $this->getData($sql, isset($params['uuid']) ? [$user_id, $user_id, $user_id, $memory_uuid] : [$user_id, $user_id, $user_id], true);

        if ($memories) {
            foreach ($memories as &$memory) {
                $memory['event'] = isset($memory['event']) ? json_decode($memory['event']) : null;
                $memory['user'] = isset($memory['user']) ? json_decode($memory['user']) : null;
                $memory['medias'] = isset($memory['medias']) ? json_decode($memory['medias']) : null;
                $memory['hashtags'] = isset($memory['hashtags']) ? json_decode($memory['hashtags']) : null;
            }
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
        FROM posts p
        JOIN post_media pm ON p.id = pm.post_id
        WHERE p.uuid = ?
            AND p.status = 'published' 
        ORDER BY pm.id;";

        $memory_medias = $this->getData($sql, [$uuid], true);

        Response::success('رسانه های خاطره دریافت شد', 'memory_medias', $memory_medias);
    }
}