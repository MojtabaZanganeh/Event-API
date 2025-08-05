<?php
namespace Classes\Events;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Events extends Users
{
    use Base, Sanitizer;

    public function get_events($params)
    {
        $location = $params['location'] ?? null;
        $date = $params['date'] ?? null;
        $categories = $params['categories'] ?? null;
        $group = $params['group'] ?? null;
        $times = $params['times'] ?? null;

        $sql = "SELECT
            e.id,
            e.title,
            e.description,
            e.slug,
            e.start_time,
            e.end_time,
            e.location,
            ec.name AS category,
            em.url as thumbnail,
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
        LEFT JOIN {$this->table['event_categories']} ec ON e.category_id = ec.id
        LEFT JOIN {$this->table['leaders']} l ON e.leader_id = l.id
        LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
        LEFT JOIN {$this->table['event_medias']} em ON e.thumbnail_id = em.id
        LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status = 'paid'
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
        WHERE e.is_public = 1";

        $bindParams = [];

        if ($location) {
            $sql .= " AND e.location LIKE ?";
            $bindParams[] = "%{$location}%";
        }

        if ($date) {
            $sql .= " AND DATE(e.start_time) = ?";
            $bindParams[] = $this->convert_jalali_to_miladi($date);
        }

        if ($categories) {
            $categoryIds = explode(',', $categories);
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            $sql .= " AND e.category_id IN ({$placeholders})";
            $bindParams = array_merge($bindParams, $categoryIds);
        }

        // if ($group) {
        //     $sql .= " AND e.leader_id = ?";
        //     $bindParams[] = $group;
        // }

        if ($times) {
            $timeRanges = explode(',', $times);
            $timeConditions = [];

            foreach ($timeRanges as $range) {
                $range = trim($range);
                if (preg_match('/(\d+)\s*-\s*(\d+)/', $range, $matches)) {
                    $startHour = $matches[1];
                    $endHour = $matches[2];
                    $timeConditions[] = "(HOUR(e.start_time) >= ? AND HOUR(e.start_time) < ?)";
                    $bindParams[] = $startHour;
                    $bindParams[] = $endHour;
                }
            }

            if (!empty($timeConditions)) {
                $sql .= " AND (" . implode(' OR ', $timeConditions) . ")";
            }
        }

        $sql .= " GROUP BY e.id, e.title, e.description, e.slug, e.start_time, e.end_time, 
                e.location, ec.name, e.capacity, e.thumbnail_id, e.price,
                u.first_name, u.last_name, u.avatar, u.registered_at,
                l.bio, rating_stats.average_score, rating_stats.total_ratings
                ORDER BY 
                    CASE 
                        WHEN e.start_time > NOW() THEN 1
                        WHEN e.start_time <= NOW() AND (e.end_time IS NULL OR e.end_time >= NOW()) THEN 2
                        ELSE 3
                    END,
                    CASE 
                        WHEN e.start_time > NOW() THEN e.start_time
                        ELSE e.start_time
                    END DESC";

        $events = $this->getData($sql, $bindParams, true);

        if (!$events) {
            Response::success('رویدادی یافت نشد');
        }

        foreach ($events as &$event) {
            $event['capacity'] = json_decode($event['capacity'], true);
            $event['leader'] = json_decode($event['leader'], true);
            $event['thumbnail'] = $this->get_full_image_url($event['thumbnail']);
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
                e.address,
                ec.name AS category,
                e.thumbnail_id,
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
                    SELECT 
                        CONCAT(
                            '[',
                            GROUP_CONCAT(
                                CONCAT(
                                    '{\"type\":\"', em.type, '\",\"url\":\"', em.url, '\"}'
                                )
                                SEPARATOR ','
                            ),
                            ']'
                        )
                    FROM {$this->table['event_medias']} em
                    WHERE em.event_id = e.id
                ) AS medias
            FROM {$this->table['events']} e
            LEFT JOIN {$this->table['event_categories']} ec ON e.category_id = ec.id
            LEFT JOIN {$this->table['leaders']} l ON e.leader_id = l.id
            LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
            LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status = 'paid'
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
            WHERE e.is_public = 1 AND slug = ?
            GROUP BY e.id, e.title, e.description, e.slug, e.start_time, e.end_time, 
                e.location, ec.name, e.capacity, e.thumbnail_id, e.price,
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

        foreach ($event['medias'] as &$media) {
            $media['url'] = $this->get_full_image_url($media['url']);
        }

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
            e2.thumbnail_id
        FROM 
            {$this->table['events']} e1
        INNER JOIN 
            {$this->table['events']} e2 ON e1.category_id = e2.category_id
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
                'thumbnail' => '/e1-1.jpeg',
            ],
            [
                'slug' => 'کارگاه-سفال-گری',
                'title' => 'طبیعت گردی روز جمعه',
                'start_time' => '2025/08/08 07:50',

                'thumbnail' => '/e1-2.jpeg',
            ],
            [
                'slug' => 'کارگاه-سفال-گری',
                'title' => 'کوهنوردی توچال',
                'start_time' => '2025/08/10 20:00',
                'thumbnail' => '/e1-3.jpeg',
            ],
        ]);

        if (!$similar_events) {
            Response::success('رویداد مشابه یافت نشد');
        }

        Response::success('رویدادهای مشابه دریافت شد', 'similarEvents', $similar_events);
    }

    public function get_featured_events()
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
                em.url as thumbnail,
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
            LEFT JOIN {$this->table['event_categories']} ec ON e.category_id = ec.id
            LEFT JOIN {$this->table['leaders']} l ON e.leader_id = l.id
            LEFT JOIN {$this->table['users']} u ON l.user_id = u.id
            LEFT JOIN {$this->table['event_medias']} em ON e.thumbnail_id = em.id
            LEFT JOIN {$this->table['reservations']} r ON e.id = r.event_id AND r.status = 'paid'
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
            WHERE e.is_public = 1
            GROUP BY e.id, e.title, e.description, e.slug, e.start_time, e.end_time, 
                e.location, ec.name, e.capacity, e.thumbnail_id, e.price,
                u.first_name, u.last_name, u.avatar, u.registered_at,
                l.bio
            ORDER BY e.views DESC, e.start_time ASC
            LIMIT 3
        ";
        $events = $this->getData($sql, [], true);

        if (!$events) {
            Response::success('رویدادی یافت نشد');
        }

        foreach ($events as &$event) {
            $event['capacity'] = json_decode($event['capacity'], true);
            $event['leader'] = json_decode($event['leader'], true);
            $event['thumbnail'] = $this->get_full_image_url($event['thumbnail']);
        }

        Response::success('رویدادهای ویژه دریافت شد', 'featuredEvents', $events);
    }

    public function new_event($params)
    {
        $this->check_params(
            $params,
            [
                'title',
                'category',
                'start_date',
                'start_time',
                'end_date',
                'end_time',
                'location_name',
                'address',
                'coordinate_lat',
                'coordinate_lng',
                'description',
                'media_ids',
                'capacity',
                'price',
            ]
        );

        $categories_list_array = $this->getData("SELECT `name` FROM {$this->table['event_categories']}", [], true);

        $title = $this->check_input_length($params['title'], 'موضوع', 5, 150);

        $category = $params['category'];
        $categories_list = array_column($categories_list_array, 'name');
        if (!in_array($category, $categories_list)) {
            Response::error('دسته بندی وجود ندارد!');
        }

        $start_date = $this->check_input($params['start_date'], 'YYYY/MM/DD', 'تاریخ شروع');
        $start_time = $this->check_input($params['start_time'], 'HH:II', 'ساعت شروع');

        $end_date = $this->check_input($params['end_date'], 'YYYY/MM/DD', 'تاریخ پایان');
        $end_time = $this->check_input($params['end_time'], 'HH:II', 'ساعت پایان');

        $location_name = $this->check_input($params['location_name'], null, 'نام مکان', '/^[آ-ی۰-۹0-9\s]{3,100}$/u');
        $address = $this->check_input($params['address'], null, 'آدرس دقیق', '/^[آ-ی۰-۹0-9\s]{10,150}$/u');

        $coordinate_lat = $this->check_input($params['coordinate_lat'], null, 'عرض جغرافیایی', '/^(\+|-)?(?:90(?:\.0{1,6})?|(?:[0-9]|[1-8][0-9])(?:\.[0-9]{1,6})?)$/');
        $coordinate_lng = $this->check_input($params['coordinate_lng'], null, 'طول جغرافیایی', '/^(\+|-)?(?:90(?:\.0{1,6})?|(?:[0-9]|[1-8][0-9])(?:\.[0-9]{1,6})?)$/');

        $description = $this->check_input_length($params['description'], 'توضیحات', 20, 2000);
        
        $capacity = $this->check_input($params['capacity'], 'ظرفیت', 'non-zero_positive_int');
        $price = $this->check_input($params['price'], 'قیمت', 'positive_int');

        // // Insert the new event into the database
        // $sql = "INSERT INTO {$this->table['events']} (title, category, start_date, start_time, end_date, end_time, location_name, address, coordinate_lat, coordinate_lng, description, media_ids, capacity, price)
        //         VALUES (:title, :category, :start_date, :start_time, :end_date, :end_time, :location_name, :address, :coordinate_lat, :coordinate_lng, :description, :media_ids, :capacity, :price)";
        // $params = [
        //     'title' => $title,
        //     'category' => $category,
        //     'start_date' => $start_date,
        //     'start_time' => $start_time,
        //     'end_date' => $end_date,
        //     'end_time' => $end_time,
        //     'location_name' => $location_name,
        //     'address' => $address,
        //     'coordinate_lat' => $coordinate_lat,
        //     'coordinate_lng' => $coordinate_lng,
        //     'description' => $description,
        //     'media_ids' => json_encode($params['media_ids']),
        //     'capacity' => $capacity,
        //     'price' => $price,
        // ];
        // $this->execute($sql, $params);
    }
}