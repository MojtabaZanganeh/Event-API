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
                e.image_url,
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
                    'rating_avg', l.rating_avg,
                    'rating_count', l.rating_count,
                    'registered_at', u.registered_at
                ) AS leader
            FROM {$this->table['events']} e
            LEFT JOIN {$this->table['event_categories']} ec ON e.event_category_id = ec.id
            LEFT JOIN {$this->table['leaders']} l ON e.leader_id = l.id
            LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
            LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status = 'paid'
            WHERE e.is_public = 1
            GROUP BY e.id, e.title, e.description, e.slug, e.start_time, e.end_time, 
                e.location, ec.name, e.capacity, e.image_url, e.price,
                u.first_name, u.last_name, u.avatar, u.registered_at,
                l.bio, l.rating_avg, l.rating_count
            ORDER BY e.start_time ASC
        ";
        $events = $this->getData($sql, [], true);

        if (!$events) {
            Response::error('رویدادی یافت نشد');
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

        $event = $this->getData("SELECT * FROM {$this->table['events']} WHERE slug = ?", [$slug]);

        if (!$event) {
            Response::error('رویدادی یافت نشد');
        }

        Response::success('رویداد دریافت شد', 'event', $event);
    }
}