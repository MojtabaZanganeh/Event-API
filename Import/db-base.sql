-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 19, 2025 at 06:11 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `is_group` tinyint(1) DEFAULT '0',
  `event_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `name`, `is_group`, `event_id`, `created_at`) VALUES
(1, NULL, 0, 1, '2025-07-11 17:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
CREATE TABLE IF NOT EXISTS `conversation_participants` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `joined_at`) VALUES
(1, 1, 1, '2025-07-11 17:40:18'),
(2, 1, 3, '2025-07-11 17:40:18');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_bin NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `event_category_id` int UNSIGNED NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `price` varchar(10) COLLATE utf8mb4_bin NOT NULL,
  `capacity` int NOT NULL,
  `capacity_left` int NOT NULL,
  `creator_id` int UNSIGNED NOT NULL,
  `leader_id` int UNSIGNED NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `leader_id` (`leader_id`),
  KEY `event_type_id` (`event_category_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `slug`, `event_category_id`, `location`, `start_time`, `end_time`, `price`, `capacity`, `capacity_left`, `creator_id`, `leader_id`, `image_url`, `is_public`, `created_at`) VALUES
(1, 'کارگاه سفالگری برای مبتدیان', 'کارگاه سفالگری برای مبتدیان | مرداد ۱۴۰۴\n\nاگر دوست دارید با هنر زیبای سفالگری آشنا شوید و دست خودتان را در ساخت قطعات منحصر به فرد بکشید، کارگاه ما دقیقاً چیزی است که به دنبال آن می‌گردید!\n\n📅 زمان برگزاری:\nمرداد ماه ۱۴۰۴\n⏰ ساعت شروع: ۶:۰۰ عصر\n⏰ ساعت پایان: ۹:۰۰ شب\n\n📍 مکان:\nاستودیو هنرهای خلاق – تهران\n\n🎨 دسته‌بندی:\nهنر و فرهنگ\n\n👥 ظرفیت:\n۱۵ نفر (برای تضمین کیفیت آموزش و توجه شخصی)\n\n📝 توضیحات کارگاه:\nدر این کارگاه عملی و خلاقانه، شما به عنوان یک مبتدی کامل با مراحل مختلف ساخت سفال آشنا می‌شوید. از طراحی اولیه گرفته تا قالب‌گیری، شکل دادن به دست، پخت و در نهایت جلوه‌دهی به قطعه‌ی خود، همه چیز را گام به گام و به صورت کاربردی یاد خواهید گرفت.\n\n🎯 سر فصل‌های اصلی:\n\nمعرفی ابزارها و مواد اولیه\nآموزش شکل دادن سفال به روش دستی\nتکنیک‌های ایجاد بافت و زیبایی در سطح سفال\nنحوه خشک کردن و پخت سفال\nرنگ‌آمیزی و نهایی کردن محصول\n🎁 محصول نهایی:\nهر شرکت کننده یک قطعه سفالی دست‌ساز به همراه دفترچه آموزشی به عنوان یادگاری از کارگاه به همراه می‌برد.\n\n🖌️ این کارگاه فرصتی عالی برای کسانی است که به دنبال آرامش، خلاقیت و یادگیری یک هنر قدیمی و زیبا هستند. هیچ تجربه‌ای لازم نیست — فقط انگیزه و تمایل به خلق کردن!\n\nثبت نام محدود است، پس زودتر رزرو کنید تا جایی در کلاس پیدا کنید!', 'کارگاه-سفالگری-برای-مبتدیان', 15, 'تهران، استودیو هنرهای خلاق', '2025-07-17 16:00:00', '2025-07-17 20:00:00', '250000', 15, 12, 3, 1, '/e1-1.jpeg', 1, '2025-07-03 19:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

DROP TABLE IF EXISTS `event_categories`;
CREATE TABLE IF NOT EXISTS `event_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `event_categories`
--

INSERT INTO `event_categories` (`id`, `name`) VALUES
(1, 'هنر و فرهنگ'),
(2, 'آموزشی'),
(3, 'تفریحی'),
(4, 'ورزشی'),
(5, 'فناوری'),
(6, 'محیط زیست'),
(7, 'کسب و کار'),
(8, 'آشپزی'),
(9, 'سلامت و تندرستی'),
(10, 'موسیقی'),
(11, 'علمی'),
(12, 'سیاسی'),
(13, 'اجتماعی'),
(14, 'مذهبی'),
(15, 'سفر و گردشگری');

-- --------------------------------------------------------

--
-- Table structure for table `leaders`
--

DROP TABLE IF EXISTS `leaders`;
CREATE TABLE IF NOT EXISTS `leaders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `bio` text COLLATE utf8mb4_bin,
  `categories_id` json NOT NULL,
  `rating_avg` float(10,2) DEFAULT NULL,
  `rating_count` int DEFAULT '0',
  `registered_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `leaders`
--

INSERT INTO `leaders` (`id`, `user_id`, `bio`, `categories_id`, `rating_avg`, `rating_count`, `registered_at`) VALUES
(1, 3, 'خلاق', '[1, 2, 3]', 4.60, 50, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `leader_followers`
--

DROP TABLE IF EXISTS `leader_followers`;
CREATE TABLE IF NOT EXISTS `leader_followers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `leader_id` int UNSIGNED NOT NULL,
  `follower_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leader_id` (`leader_id`,`follower_id`),
  KEY `follower_id` (`follower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int UNSIGNED NOT NULL,
  `sender_id` int UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_bin,
  `reply_to` int UNSIGNED DEFAULT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`),
  KEY `reply_to` (`reply_to`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `text`, `reply_to`, `read`, `created_at`) VALUES
(1, 1, 1, 'سلام', NULL, 0, '2025-07-11 17:41:40'),
(2, 1, 3, 'سلام داداش', NULL, 0, '2025-07-11 17:41:40'),
(3, 1, 1, 'خوبی؟', NULL, 0, '2025-07-18 19:39:46'),
(5, 1, 1, 'پاکرم', NULL, 0, '2025-07-19 16:20:53'),
(6, 1, 1, 'چه خبرا داوشی', NULL, 0, '2025-07-19 16:21:11'),
(7, 1, 1, 'ممد آقا', NULL, 0, '2025-07-19 16:22:04'),
(8, 1, 1, 'چه خبرا', NULL, 0, '2025-07-19 16:22:08'),
(9, 1, 3, 'قربانت داش', NULL, 0, '2025-07-19 16:29:14'),
(10, 1, 3, 'بریم بیرون؟', NULL, 0, '2025-07-19 16:32:44'),
(11, 1, 1, 'بریم', NULL, 0, '2025-07-19 16:42:02'),
(12, 1, 3, 'ساعت چند؟', NULL, 0, '2025-07-19 16:42:35'),
(13, 1, 1, 'هر ساعتی تو بگی', NULL, 0, '2025-07-19 16:42:44'),
(14, 1, 3, 'سرعت ارسال پیام رو زیاد کردم:)', NULL, 0, '2025-07-19 16:43:01'),
(15, 1, 1, 'ایول', NULL, 0, '2025-07-19 16:43:05'),
(16, 1, 3, 'خیلی حال میده:))))))))', NULL, 0, '2025-07-19 16:43:14'),
(17, 1, 1, ':))))))))))', NULL, 0, '2025-07-19 16:43:19'),
(18, 1, 1, 'اشتب شد من سرعت رو زیاد کردم', NULL, 0, '2025-07-19 16:43:51'),
(19, 1, 3, 'آره منظورم همون بود', NULL, 0, '2025-07-19 16:43:56');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
CREATE TABLE IF NOT EXISTS `otps` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` varchar(12) COLLATE utf8mb4_bin NOT NULL,
  `code` varchar(6) COLLATE utf8mb4_bin NOT NULL,
  `expires_at` int UNSIGNED NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `page` varchar(15) COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`id`, `phone`, `code`, `expires_at`, `is_used`, `page`, `created_at`) VALUES
(1, '09033890745', '11111', 1752513395, 1, 'register', '2025-07-14 17:14:35'),
(2, '09033890745', '11111', 1752514057, 1, 'register', '2025-07-14 17:25:37'),
(3, '09033890745', '11111', 1752613766, 1, 'register', '2025-07-15 21:07:26'),
(4, '09033890745', '11111', 1752614638, 1, 'register', '2025-07-15 21:21:58'),
(5, '09033890745', '11111', 1752679924, 1, 'register', '2025-07-16 15:30:04'),
(6, '09033890745', '11111', 1752680046, 1, 'register', '2025-07-16 15:32:06'),
(7, '09033890745', '11111', 1752680148, 1, 'register', '2025-07-16 15:33:48'),
(8, '09033890745', '11111', 1752680663, 1, 'register', '2025-07-16 15:42:23'),
(9, '09154521497', '11111', 1752680821, 1, 'register', '2025-07-16 15:45:01'),
(10, '09154521497', '11111', 1752681009, 1, 'resend', '2025-07-16 15:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `event_id` int UNSIGNED DEFAULT NULL,
  `caption` text COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

DROP TABLE IF EXISTS `post_comments`;
CREATE TABLE IF NOT EXISTS `post_comments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `comment` text COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

DROP TABLE IF EXISTS `post_likes`;
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_id` (`post_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `post_media`
--

DROP TABLE IF EXISTS `post_media`;
CREATE TABLE IF NOT EXISTS `post_media` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int UNSIGNED NOT NULL,
  `media_type` enum('image','video') COLLATE utf8mb4_bin NOT NULL,
  `media_url` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_user_id` int UNSIGNED NOT NULL,
  `to_user_id` int UNSIGNED NOT NULL,
  `group_id` int UNSIGNED NOT NULL,
  `score` int DEFAULT NULL,
  `comment` text COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_user_id` (`from_user_id`),
  KEY `to_user_id` (`to_user_id`),
  KEY `ratings_ibfk_3` (`group_id`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` int UNSIGNED NOT NULL,
  `reported_user_id` int UNSIGNED DEFAULT NULL,
  `reported_group_id` int UNSIGNED DEFAULT NULL,
  `reason` text COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `reported_user_id` (`reported_user_id`),
  KEY `reports_ibfk_3` (`reported_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `gender` enum('male','woman') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `phone` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `national_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `birth_date` datetime DEFAULT NULL,
  `role` enum('user','leader','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'user',
  `avatar` text COLLATE utf8mb4_bin,
  `is_active` tinyint(1) DEFAULT '1',
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `national_id` (`national_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `gender`, `phone`, `password`, `national_id`, `birth_date`, `role`, `avatar`, `is_active`, `registered_at`) VALUES
(1, 'user_09033890745', 'مجتبی', 'زنگنه', '', '09033890745', '$2y$10$FAEjBVs/flD/NHRmA9ytD.TJKNNcSyVT1HdZo9Xc5F8KtJX7sD3Ou', '0691001561', NULL, 'admin', NULL, 1, '2025-07-03 13:46:40'),
(2, 'user_09154521499', 'علی', 'محمدی', '', '09154521499', '$2y$10$FAEjBVs/flD/NHRmA9ytD.TJKNNcSyVT1HdZo9Xc5F8KtJX7sD3Ou', '0691243215', NULL, 'user', NULL, 1, '2025-07-03 13:47:08'),
(3, 'user_09154521498', 'محمد', 'علیایی', '', '09154521498', '$2y$10$FAEjBVs/flD/NHRmA9ytD.TJKNNcSyVT1HdZo9Xc5F8KtJX7sD3Ou', '0691441521', NULL, 'leader', '/u2.jpg', 1, '2025-07-03 13:47:53'),
(4, 'user_09154521497', 'احمد', 'رضایی', '', '09154521497', '$2y$10$FAEjBVs/flD/NHRmA9ytD.TJKNNcSyVT1HdZo9Xc5F8KtJX7sD3Ou', NULL, NULL, 'user', NULL, 1, '2025-07-16 15:48:32');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_category_id`) REFERENCES `event_categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`leader_id`) REFERENCES `leaders` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `leaders`
--
ALTER TABLE `leaders`
  ADD CONSTRAINT `leaders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `leader_followers`
--
ALTER TABLE `leader_followers`
  ADD CONSTRAINT `leader_followers_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leader_followers_ibfk_2` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`reply_to`) REFERENCES `messages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_media`
--
ALTER TABLE `post_media`
  ADD CONSTRAINT `post_media_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `conversations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_group_id`) REFERENCES `conversations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
