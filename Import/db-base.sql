-- کاربران
CREATE TABLE
    users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(12) UNIQUE NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        national_id VARCHAR(10) UNIQUE NOT NULL,
        role ENUM ('user', 'leader', 'admin') DEFAULT 'user',
        is_active BOOLEAN DEFAULT TRUE,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

-- نوع رویداد
CREATE TABLE
    event_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL
    ) ENGINE = InnoDB;

-- رویدادها
CREATE TABLE
    events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        event_type_id INT,
        location VARCHAR(255),
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        capacity INT DEFAULT 50,
        creator_id INT,
        image_url VARCHAR(255),
        is_public BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_type_id) REFERENCES event_types (id),
        FOREIGN KEY (creator_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- گفتگوها
CREATE TABLE
    conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        is_group BOOLEAN DEFAULT FALSE,
        name VARCHAR(255),
        event_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events (id)
    ) ENGINE = InnoDB;

-- اعضای گفتگو
CREATE TABLE
    conversation_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- پیام ها
CREATE TABLE
    messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- لیدرها
CREATE TABLE
    leaders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bio TEXT,
        rating_avg FLOAT DEFAULT 0,
        rating_count INT DEFAULT 0,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;

-- دنبال کنندگان لیدرها
CREATE TABLE
    leader_followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        leader_id INT NOT NULL,
        follower_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (leader_id) REFERENCES users (id),
        FOREIGN KEY (follower_id) REFERENCES users (id),
        UNIQUE (leader_id, follower_id)
    ) ENGINE = InnoDB;

-- نمره دهی
CREATE TABLE
    ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        to_user_id INT NOT NULL,
        group_id INT NOT NULL,
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
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT NOT NULL,
        reported_user_id INT,
        reported_group_id INT,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reporter_id) REFERENCES users (id),
        FOREIGN KEY (reported_user_id) REFERENCES users (id),
        FOREIGN KEY (reported_group_id) REFERENCES `groups` (id)
    ) ENGINE = InnoDB;

-- جدول پست ها
CREATE TABLE
    posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_id INT,
        caption TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id),
        FOREIGN KEY (event_id) REFERENCES events (id)
    ) ENGINE = InnoDB;

-- جدول رسانه های پست
CREATE TABLE
    post_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        media_type ENUM ('image', 'video') NOT NULL,
        media_url VARCHAR(255) NOT NULL,
        thumbnail_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id)
    ) ENGINE = InnoDB;

-- جدول لایک های پست
CREATE TABLE
    post_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id),
        FOREIGN KEY (user_id) REFERENCES users (id),
        UNIQUE (post_id, user_id)
    ) ENGINE = InnoDB;

-- جدول کامنت های پست
CREATE TABLE
    post_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE = InnoDB;