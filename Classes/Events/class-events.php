<?php
namespace Classes\Evetns;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Evetns extends Users
{
    use Base, Sanitizer;

    public function get_events()
    {
        $events = [
            [
                "id" => "e1",
                "title" => "پیاده‌روی شنبه در کوه‌های البرز",
                "date" => "۳ آبان ۱۴۰۴",
                "time" => "۱۰:۰۰ صبح",
                "location" => "مسیر کوهنوردی توچال، تهران",
                "category" => "ورزشی",
                "capacity" => [
                    "total" => 12,
                    "filled" => 8,
                ],
                "image" => "e1-1.jpeg",
                "price" => "120000",
                "leader" => [
                    "name" => "سارا چن",
                    "avatar" => "/u1.jpg",
                    "rating" => 4.8,
                ],
            ],
            [
                "id" => "e2",
                "title" => "شب موسیقی جاز",
                "date" => "۵ آبان ۱۴۰۴",
                "time" => "۸:۳۰ شب",
                "location" => "کافه موسیقی نت، تهران",
                "category" => "موسیقی",
                "capacity" => [
                    "total" => 20,
                    "filled" => 14,
                ],
                "image" => "e2-1.jpeg",
                "price" => "120000",
                "leader" => [
                    "name" => "مارکوس جانسون",
                    "avatar" => "/u2.jpg",
                    "rating" => 4.5,
                ],
            ],
            [
                "id" => "e3",
                "title" => "کارگاه سفالگری برای مبتدیان",
                "date" => "۷ آبان ۱۴۰۴",
                "time" => "۶:۰۰ عصر",
                "location" => "استودیو هنرهای خلاق، تهران",
                "category" => "هنر و فرهنگ",
                "capacity" => [
                    "total" => 8,
                    "filled" => 6,
                ],
                "image" => "e3-1.jpeg",
                "price" => "120000",
                "leader" => [
                    "name" => "اما دیویس",
                    "avatar" => "/u3.jpg",
                    "rating" => 4.9,
                ],
            ],
            [
                "id" => "e4",
                "title" => "دورهمی استارتاپی",
                "date" => "۱۲ آبان ۱۴۰۴",
                "time" => "۷:۰۰ شب",
                "location" => "مرکز نوآوری امید، تهران",
                "category" => "تکنولوژی",
                "capacity" => [
                    "total" => 40,
                    "filled" => 32,
                ],
                "image" => "e4-1.jpeg",
                "price" => "120000",
                "leader" => [
                    "name" => "دانیال وانگ",
                    "avatar" => "/u4.jpg",
                    "rating" => 4.6,
                ],
            ],
            [
                "id" => "e5",
                "title" => "یوگا در ساحل",
                "date" => "۱۵ آبان ۱۴۰۴",
                "time" => "۷:۳۰ صبح",
                "location" => "ساحل محمودآباد، مازندران",
                "category" => "ورزشی",
                "capacity" => [
                    "total" => 15,
                    "filled" => 9,
                ],
                "image" => "e5-1.jpeg",
                "price" => "120000",
                "leader" => [
                    "name" => "زهرا مارتینز",
                    "avatar" => "/u5.jpg",
                    "rating" => 4.7,
                ],
            ],
            [
                "id" => "e6",
                "title" => "عکاسی شهری",
                "date" => "۱۸ آبان ۱۴۰۴",
                "time" => "۴:۰۰ عصر",
                "location" => "محله هنری تهران",
                "category" => "هنر و فرهنگ",
                "capacity" => [
                    "total" => 10,
                    "filled" => 5,
                ],
                "image" => "e6-1.jpeg",
                "price" => "120000",
                "leader" => [
                    "name" => "طاها بروکس",
                    "avatar" => "/u6.jpg",
                    "rating" => 4.4,
                ],
            ],
        ];

        Response::success('رویدادها با موفقیت دریافت شد', 'events', $events);
    }
}