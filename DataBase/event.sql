-- کاربران
CREATE TABLE
    users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(32) UNIQUE NOT NULL,
        first_name VARCHAR(25),
        last_name VARCHAR(25),
        gender ENUM ('male', 'woman'),
        phone VARCHAR(12) UNIQUE NOT NULL,
        password TEXT NOT NULL,
        national_id VARCHAR(10) UNIQUE,
        birth_date DATETIME,
        role ENUM ('user', 'leader', 'admin') DEFAULT 'user',
        rating_avg INT UNSIGNED NOT NULL DEFAULT '0',
        rating_count INT UNSIGNED NOT NULL DEFAULT '0',
        is_active BOOLEAN DEFAULT TRUE,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

-- جدول OTP برای ورود با شماره موبایل
CREATE TABLE
    otps (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(12) NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at INT UNSIGNED NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        page VARCHAR(15) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

-- نوع رویداد
CREATE TABLE
    event_categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL
    ) ENGINE = InnoDB;

-- رویدادها
CREATE TABLE
    events (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        event_type_id INT UNSIGNED,
        location VARCHAR(255),
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        capacity INT DEFAULT 50,
        creator_id INT UNSIGNED,
        image_url VARCHAR(255),
        is_public BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_type_id) REFERENCES event_types (id),
        FOREIGN KEY (creator_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- گفتگوها
CREATE TABLE
    conversations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        is_group BOOLEAN DEFAULT FALSE,
        name VARCHAR(255),
        event_id INT UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events (id)
    ) ENGINE = InnoDB;

-- اعضای گفتگو
CREATE TABLE
    conversation_participants (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- پیام ها
CREATE TABLE
    messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        text TEXT,
        reply_to INT UNSIGNED DEFAULT NULL,
        read tinyint (1) NOT NULL DEFAULT '0',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE FOREIGN KEY (reply_to) REFERENCES messages (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- لیدرها
CREATE TABLE
    leaders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        bio TEXT,
        categories_id JSON NOT NULL,
        rating_avg FLOAT DEFAULT 0,
        rating_count INT DEFAULT 0,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- دنبال کنندگان لیدرها
CREATE TABLE
    leader_followers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        leader_id INT UNSIGNED NOT NULL,
        follower_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (leader_id) REFERENCES users (id),
        FOREIGN KEY (follower_id) REFERENCES users (id),
        UNIQUE (leader_id, follower_id)
    ) ENGINE = InnoDB;

-- نمره دهی
CREATE TABLE
    ratings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT UNSIGNED NOT NULL,
        to_user_id INT UNSIGNED NOT NULL,
        group_id INT UNSIGNED NOT NULL,
        score INT CHECK (score BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users (id),
        FOREIGN KEY (to_user_id) REFERENCES users (id),
        FOREIGN KEY (group_id) REFERENCES `groups` (id)
    ) ENGINE = InnoDB;

-- گزارش ها
CREATE TABLE
    reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT UNSIGNED NOT NULL,
        reported_user_id INT UNSIGNED,
        reported_group_id INT UNSIGNED,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reporter_id) REFERENCES users (id),
        FOREIGN KEY (reported_user_id) REFERENCES users (id),
        FOREIGN KEY (reported_group_id) REFERENCES `groups` (id)
    ) ENGINE = InnoDB;

-- جدول پست ها
CREATE TABLE
    posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        uuid VARCHAR(36) NOT NULL user_id INT UNSIGNED NOT NULL,
        event_id INT UNSIGNED,
        caption TEXT,
        status ENUM ('pending', 'published', 'deleted') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id),
        FOREIGN KEY (event_id) REFERENCES events (id)
    ) ENGINE = InnoDB;

-- جدول رسانه های پست
CREATE TABLE
    post_media (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        media_type ENUM ('image', 'video') NOT NULL,
        media_url VARCHAR(255) NOT NULL,
        thumbnail_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id)
    ) ENGINE = InnoDB;

-- جدول هشتگ های پست
CREATE TABLE
    post_hashtags (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        hashtag VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id)
    ) ENGINE = InnoDB;

-- جدول لایک های پست
CREATE TABLE
    post_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id),
        FOREIGN KEY (user_id) REFERENCES users (id),
        UNIQUE (post_id, user_id)
    ) ENGINE = InnoDB;

-- جدول کامنت های پست
CREATE TABLE
    post_comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- جدول ذخیره های پست
CREATE TABLE
    post_saved (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;