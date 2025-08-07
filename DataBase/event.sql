-- کاربران
CREATE TABLE
    users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(32) UNIQUE NOT NULL,
        first_name VARCHAR(25) NOT NULL,
        last_name VARCHAR(25) NOT NULL,
        gender ENUM ('male', 'female'),
        phone VARCHAR(12) UNIQUE NOT NULL,
        password TEXT NOT NULL,
        national_id VARCHAR(10) UNIQUE,
        birth_date DATE,
        role ENUM ('user', 'leader', 'admin') DEFAULT 'user' NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
    ) ENGINE = InnoDB;

-- جدول OTP برای ورود با شماره موبایل
CREATE TABLE
    otps (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(12) NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at INT UNSIGNED NOT NULL,
        is_used BOOLEAN DEFAULT FALSE NOT NULL,
        page VARCHAR(15) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
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
        category_id INT UNSIGNED NOT NULL,
        start_time TIMESTAMP NOT NULL,
        end_time TIMESTAMP,
        location VARCHAR(100) NOT NULL,
        address VARCHAR(150) NOT NULL,
        coordinates JSON NOT NULL,
        price BIGINT UNSIGNED NOT NULL,
        capacity INT UNSIGNED NOT NULL,
        grouping INT UNSIGNED DEFAULT 0 NOT NULL,
        creator_id INT UNSIGNED NOT NULL,
        leader_id INT UNSIGNED NOT NULL,
        thumbnail_id INT UNSIGNED NOT NULL,
        views INT UNSIGNED NOT NULL,
        status ENUM ('pending','verified','deleted','reported') DEFAULT 'pending' NOT NULL,
        is_private BOOLEAN DEFAULT FALSE NOT NULL,
        is_approval BOOLEAN DEFAULT FALSE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (event_type_id) REFERENCES event_types (id),
        FOREIGN KEY (thumbnail_id) REFERENCES event_medias (id),
        FOREIGN KEY (creator_id) REFERENCES users (id)
        FOREIGN KEY (leader_id) REFERENCES leaders (id)
    ) ENGINE = InnoDB;

-- جدول رسانه های رویداد
CREATE TABLE
    event_medias (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        uuid VARCHAR(36) NOT NULL,
        event_id INT UNSIGNED,
        type ENUM ('image', 'video') NOT NULL,
        url VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events (id)
    ) ENGINE = InnoDB;

-- لیدرها
CREATE TABLE
    leaders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        bio TEXT,
        categories_id JSON NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ) ENGINE = InnoDB;

CREATE TABLE
    leader_categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        leader_id UNSIGNED INT NOT NULL,
        category_id UNSIGNED INT NOT NULL,
        FOREIGN KEY (leader_id) REFERENCES leaders (id),
        FOREIGN KEY (category_id) REFERENCES event_categories (id)
    ) ENGINE = InnoDB;

-- رزروها
CREATE TABLE
    reservations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        code VARCHAR(10) NOT NULL,
        price BIGINT NOT NULL,
        status ENUM ('pending-pay', 'paid', 'canceled') DEFAULT 'pending-pay' NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- پرداخت ها
CREATE TABLE
    transactions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT UNSIGNED NOT NULL,
        amount BIGINT UNSIGNED NOT NULL,
        status ENUM ("pending", "paid", "failed", "canceled") DEFAULT 'pending' NOT NULL,
        authority VARCHAR(36),
        card_hash VARCHAR(64),
        card_pan VARCHAR(16),
        ref_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        paid_at TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES reservations (id),
    ) ENGINE = InnoDB;

-- تیکت های پشتیبانی
CREATE TABLE
    support_tickets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id INT UNSIGNED,
        user_id INT UNSIGNED NOT NULL,
        subject VARCHAR(50) NOT NULL,
        s code VARCHAR(10) NOT NULL,
        status ENUM (
            'pending',
            ' answered',
            'user-response',
            'finished'
        ) DEFAULT 'pending' NOT NULL,
        priority ENUM ('low', 'medium', 'high') DEFAULT 'medium' NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- پیام های تیکت پشتیبانی
CREATE TABLE
    support_ticket_messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT UNSIGNED,
        user_id INT UNSIGNED NOT NULL,
        read BOOLEAN DEFAULT FALSE NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (ticket) REFERENCES support_tickets (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- اعلان ها
CREATE TABLE
    notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id INT UNSIGNED,
        reservation_id INT UNSIGNED,
        pay_id INT UNSIGNED,
        ticket_id INT UNSIGNED,
        user_id INT UNSIGNED NOT NULL,
        title VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        type ENUM (
            'warning',
            'stern-warning',
            'ads',
            'offer',
            'notice',
            'update'
        ) DEFAULT 'notice' NOT NULL,
        urgent BOOLEAN DEFAULT FALSE NOT NULL,
        read BOOLEAN DEFAULT FALSE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events (id),
        FOREIGN KEY (reservation_id) REFERENCES reservations (id),
        FOREIGN KEY (pay_id) REFERENCES payments (id),
        FOREIGN KEY (ticket_id) REFERENCES support_tickets (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- گفتگوها
CREATE TABLE
    conversations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        is_group BOOLEAN DEFAULT FALSE NOT NULL,
        name VARCHAR(255),
        event_id INT UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events (id)
    ) ENGINE = InnoDB;

-- اعضای گفتگو
CREATE TABLE
    conversation_participants (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
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
        read tinyint (1) DEFAULT '0' NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE FOREIGN KEY (reply_to) REFERENCES messages (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- دنبال کنندگان لیدرها
CREATE TABLE
    leader_followers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        leader_id INT UNSIGNED NOT NULL,
        follower_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (leader_id) REFERENCES users (id),
        FOREIGN KEY (follower_id) REFERENCES users (id),
        UNIQUE (leader_id, follower_id)
    ) ENGINE = InnoDB;

-- امتیاز دهی
CREATE TABLE
    ratings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT UNSIGNED NOT NULL,
        to_user_id INT UNSIGNED NOT NULL,
        conversation_id INT UNSIGNED NOT NULL,
        score INT CHECK (score BETWEEN 1 AND 5) NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (from_user_id) REFERENCES users (id),
        FOREIGN KEY (to_user_id) REFERENCES users (id),
        FOREIGN KEY (conversation_id) REFERENCES `groups` (id)
    ) ENGINE = InnoDB;

-- گزارش ها
CREATE TABLE
    reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT UNSIGNED NOT NULL,
        reported_user_id INT UNSIGNED,
        reported_conversation_id INT UNSIGNED,
        reported_message_id INT UNSIGNED,
        reported_event_id INT UNSIGNED,
        reported_leader_id INT UNSIGNED,
        reported_memory_id INT UNSIGNED,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (reporter_id) REFERENCES users (id),
        FOREIGN KEY (reported_user_id) REFERENCES users (id),
        FOREIGN KEY (reported_conversation_id) REFERENCES `conversations` (id) FOREIGN KEY (reported_message_id) REFERENCES `messages` (id) FOREIGN KEY (reported_event_id) REFERENCES `events` (id) FOREIGN KEY (reported_leader_id) REFERENCES `leaders` (id) FOREIGN KEY (reported_memory_id) REFERENCES `memories` (id)
    ) ENGINE = InnoDB;

-- جدول پست ها
CREATE TABLE
    memories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        uuid VARCHAR(36) NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        event_id INT UNSIGNED NOT NULL,
        thumbnail_id INT UNSIGNED NOT NULL,
        caption TEXT,
        status ENUM ('pending', 'published', 'deleted') DEFAULT 'pending' NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users (id),
        FOREIGN KEY (event_id) REFERENCES events (id)
        FOREIGN KEY (thumbnail_id) REFERENCES memory_medias (id)
    ) ENGINE = InnoDB;

-- جدول رسانه های پست
CREATE TABLE
    memory_medias (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        memory_id INT UNSIGNED NOT NULL,
        type ENUM ('image', 'video') NOT NULL,
        url VARCHAR(100) NOT NULL,
        thumbnail_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (memory_id) REFERENCES memories (id)
    ) ENGINE = InnoDB;

-- جدول هشتگ های پست
CREATE TABLE
    memory_hashtags (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        memory_id INT UNSIGNED NOT NULL,
        hashtag VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (memory_id) REFERENCES memories (id)
    ) ENGINE = InnoDB;

-- جدول لایک های پست
CREATE TABLE
    memory_likes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        memory_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (memory_id) REFERENCES memories (id),
        FOREIGN KEY (user_id) REFERENCES users (id),
        UNIQUE (memory_id, user_id)
    ) ENGINE = InnoDB;

-- جدول کامنت های پست
CREATE TABLE
    memory_comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        memory_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (memory_id) REFERENCES memories (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- جدول ذخیره های پست
CREATE TABLE
    memories_saved (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        memory_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        FOREIGN KEY (memory_id) REFERENCES memories (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;