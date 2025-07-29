<?php
namespace Classes\Events;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Events extends Users
{
    use Base, Sanitizer;

    public function get_events()
    {
        $sql = "SELECT
                e.id,
                e.title,
                e.description,
                e.slug,
                e.start_time,
                e.end_time,
                e.location,
                ec.name AS category,
                e.thumbnail_url,
                e.price,
                JSON_OBJECT(
                    'total', e.capacity,
                    'filled', COUNT(r.id),
                    'left', (e.capacity - COUNT(r.id))
                ) AS capacity,
                JSON_OBJECT(
                    'name', CONCAT(u.first_name, ' ', u.last_name),
                    'avatar', u.avatar,
                    'registered_at', u.registered_at
                ) AS creator,
                JSON_OBJECT(
                    'name', CONCAT(u.first_name, ' ', u.last_name),
                    'avatar', u.avatar,
                    'bio', l.bio,
                    'rating_avg', COALESCE(rating_stats.average_score, 0),
                    'rating_count', COALESCE(rating_stats.total_ratings, 0),
                    'registered_at', u.registered_at
                ) AS leader
            FROM {$this->table['events']} e
            LEFT JOIN {$this->table['event_categories']} ec ON e.event_category_id = ec.id
            LEFT JOIN {$this->table['leaders']} l ON e.leader_id = l.id
            LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
            LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status = 'paid'
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
            WHERE e.is_public = 1
            GROUP BY e.id, e.title, e.description, e.slug, e.start_time, e.end_time, 
                e.location, ec.name, e.capacity, e.thumbnail_url, e.price,
                u.first_name, u.last_name, u.avatar, u.registered_at,
                l.bio
            ORDER BY e.start_time ASC
        ";
        $events = $this->getData($sql, [], true);

        if (!$events) {
            Response::success('رویدادی یافت نشد');
        }

        foreach ($events as &$event) {
            $event['capacity'] = json_decode($event['capacity'], true);
            $event['leader'] = json_decode($event['leader'], true);
        }

        Response::success('رویدادها دریافت شد', 'allEvents', $events);
    }

    public function get_event_by_slug($params)
    {
        $this->check_params($params, ['slug']);

        $slug = $params['slug'];

        $sql = "SELECT
                e.id,
                e.title,
                e.description,
                e.slug,
                e.start_time,
                e.end_time,
                e.location,
                ec.name AS category,
                e.thumbnail_url,
                e.price,
                JSON_OBJECT(
                    'total', e.capacity,
                    'filled', COUNT(r.id),
                    'left', (e.capacity - COUNT(r.id))
                ) AS capacity,
                JSON_OBJECT(
                    'name', CONCAT(u.first_name, ' ', u.last_name),
                    'avatar', u.avatar,
                    'registered_at', u.registered_at
                ) AS creator,
                JSON_OBJECT(
                    'name', CONCAT(u.first_name, ' ', u.last_name),
                    'avatar', u.avatar,
                    'bio', l.bio,
                    'rating_avg', COALESCE(rating_stats.average_score, 0),
                    'rating_count', COALESCE(rating_stats.total_ratings, 0),
                    'registered_at', u.registered_at
                ) AS leader,
                (
                SELECT JSON_ARRAYAGG(
                        JSON_OBJECT('type', em.media_type, 'url', em.media_url, 'thumbnail', em.thumbnail_url)
                    )
                    FROM {$this->table['event_medias']} em
                    WHERE em.event_id = e.id
                ) AS medias
            FROM {$this->table['events']} e
            LEFT JOIN {$this->table['event_categories']} ec ON e.event_category_id = ec.id
            LEFT JOIN {$this->table['leaders']} l ON e.leader_id = l.id
            LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
            LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status = 'paid'
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
            WHERE e.is_public = 1 AND slug = ?
            GROUP BY e.id, e.title, e.description, e.slug, e.start_time, e.end_time, 
                e.location, ec.name, e.capacity, e.thumbnail_url, e.price,
                u.first_name, u.last_name, u.avatar, u.registered_at,
                l.bio
            ORDER BY e.start_time ASC
        ";

        $event = $this->getData($sql, [$slug]);

        if (!$event) {
            Response::success('رویدادی یافت نشد');
        }

        $event['capacity'] = $event['capacity'] ? json_decode($event['capacity'], true) : [];
        $event['creator'] = $event['creator'] ? json_decode($event['creator'], true) : [];
        $event['leader'] = $event['leader'] ? json_decode($event['leader'], true) : [];
        $event['medias'] = $event['medias'] ? json_decode($event['medias'], true) : [];

        Response::success('رویداد دریافت شد', 'event', $event);
    }

    public function get_similar_events($params)
    {
        $this->check_params($params, ['event_id']);

        $event_id = $params['event_id'];

        $sql =
            "SELECT 
            e2.slug,
            e2.title, 
            e2.start_time, 
            e2.thumbnail_url
        FROM 
            events e1
        INNER JOIN 
            events e2 ON e1.event_category_id = e2.event_category_id
        WHERE 
            e1.id = ?
            AND e2.id != ?
        ORDER BY 
            e2.created_at DESC
        LIMIT 3;";

        $similar_events = $this->getData($sql, [$event_id, $event_id], true);

        Response::success('رویدادهای مشابه دریافت شد', 'similarEvents', [
            [
                'slug' => 'کارگاه-سفال-گری',
                'title' => 'کارگاه نقاشی انگشتی',
                'start_time' => '2025/08/25 15:20',
                'thumbnail_url' => '/e1-1.jpeg',
            ],
            [
                'slug' => 'کارگاه-سفال-گری',
                'title' => 'طبیعت گردی روز جمعه',
                'start_time' => '2025/08/08 07:50',

                'thumbnail_url' => '/e1-2.jpeg',
            ],
            [
                'slug' => 'کارگاه-سفال-گری',
                'title' => 'کوهنوردی توچال',
                'start_time' => '2025/08/10 20:00',
                'thumbnail_url' => '/e1-3.jpeg',
            ],
        ]);

        if (!$similar_events) {
            Response::success('رویداد مشابه یافت نشد');
        }

        Response::success('رویدادهای مشابه دریافت شد', 'similarEvents', $similar_events);
    }
}