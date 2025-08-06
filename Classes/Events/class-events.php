<?php
namespace Classes\Events;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Events extends Users
{
    use Base, Sanitizer;

    private function generate_slug($input)
    {
        $output = preg_replace('/[^a-zA-Z0-9\s\-_\x{0600}-\x{06FF}]/u', '', $input);
        $output = preg_replace('/\s+/', '-', $output);
        $output = trim($output, '-');
        return $output;
    }

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
        WHERE e.status = 'verified' AND e.is_private = 0";

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
            WHERE e.status = 'verified' AND e.is_private = 0 AND e.slug = ?
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
            WHERE e.status = 'verified' AND e.is_private = 0
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
        $creator = $this->check_role(['leader', 'admin']);
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
            ]
        );

        $title = $this->check_input_length($params['title'], 'موضوع', 5, 150);
        $slug = $this->generate_slug($title);

        $category_search = $this->getData("SELECT id FROM {$this->table['event_categories']} WHERE `id` = ?", [$params['category']]);
        if (!$category_search) {
            Response::error('دسته بندی وجود ندارد!');
        }
        $category_id = $params['category'];

        $start_date = $this->check_input($params['start_date'], 'YYYY/MM/DD', 'تاریخ شروع');
        $start_date_miladi = $this->convert_jalali_to_miladi($start_date);
        $start_time = $this->check_input($params['start_time'], 'HH:MM', 'ساعت شروع');
        $start_date_time = str_replace('/', '-', "$start_date_miladi $start_time:00");
        
        $end_date = $this->check_input($params['end_date'], 'YYYY/MM/DD', 'تاریخ پایان');
        $end_date_miladi = $this->convert_jalali_to_miladi($end_date);
        $end_time = $this->check_input($params['end_time'], 'HH:MM', 'ساعت پایان');
        $end_date_time = str_replace('/', '-', "$end_date_miladi $end_time:00");

        $location_name = $this->check_input($params['location_name'], null, 'نام مکان', '/^[آ-ی۰-۹0-9\s\-_,،\.]{3,100}$/u');
        $address = $this->check_input($params['address'], null, 'آدرس دقیق', '/^[آ-ی۰-۹0-9\s\-_,،\.]{10,150}$/u');

        $coordinate_lat = $this->check_input($params['coordinate_lat'], null, 'عرض جغرافیایی', '/^(\+|-)?(?:90(?:\.0{1,6})?|(?:[0-9]|[1-8][0-9])(?:\.[0-9]{1,6})?)$/');
        $coordinate_lng = $this->check_input($params['coordinate_lng'], null, 'طول جغرافیایی', '/^(\+|-)?(?:90(?:\.0{1,6})?|(?:[0-9]|[1-8][0-9])(?:\.[0-9]{1,6})?)$/');
        $coordinate_address = $this->check_input($params['coordinate_address'], null, 'آدرس مختصات انتخابی', '/^[آ-ی۰-۹0-9\s\-_,،\.]{10,150}$/u');
        $coordinates = json_encode(['lat' => $coordinate_lat, 'lng' => $coordinate_lng, 'address' => $coordinate_address]);

        $description = $this->check_input_length($params['description'], 'توضیحات', 20, 2000);

        $media_ids = explode(',', $params['media_ids']);
        $thumbnail_id = null;
        foreach ($media_ids as $media_id) {
            $media = $this->getData("SELECT id, `type` FROM {$this->table['event_medias']} WHERE uuid = ?", [$media_id]);
            if ($media['type'] === 'image') {
                $thumbnail_id = $media['id'];
                break;
            }
        }
        if (!$thumbnail_id) {
            Response::error('حداقل یک عکس آپلود کنید');
        }

        $capacity = $this->check_input($params['capacity'], 'non-zero_positive_int', 'ظرفیت');
        $price = $params['price'] ? $this->check_input(preg_replace('/\D/', '', $params['price']), 'positive_int', 'قیمت') : 0;

        $is_private = isset($params['private']) ? true : false;
        $is_approval = isset($params['approval']) ? true : false;

        $leader_search = isset($params['leader']) && $creator['role'] === 'admin' ? $this->getData("SELECT id FROM {$this->table['leaders']} WHERE `id` = ?", [$params['leader']]) : null;
        $leader_id = isset($leader_search['id']) ? $leader_search['id'] : $this->getData("SELECT id FROM {$this->table['leaders']} WHERE user_id = ?", [$creator['id']])['id'];

        $sql = "INSERT INTO {$this->table['events']} (`title`, `description`, `slug`, `category_id`, `start_time`, `end_time`, `location`, `address`, `coordinates`, `price`, `capacity`, `creator_id`, `leader_id`, `thumbnail_id`, `status`, `is_private`, `is_approval`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $title,
            $description,
            $slug,
            $category_id,
            $start_date_time,
            $end_date_time,
            $location_name,
            $address,
            $coordinates,
            $price,
            $capacity,
            $creator['id'],
            $leader_id,
            $thumbnail_id,
            'pending',
            $is_private,
            $is_approval,
            $this->current_time()
        ];
        $event_id = $this->insertData($sql, $params);
        if (!$event_id) {
            Response::error('خطا در ثبت رویداد');
        }

        Response::success('رویداد ثبت شد و پس از بازبینی منتشر خواهد شد');
    }
}