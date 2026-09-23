CREATE TABLE IF NOT EXISTS `17_users` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    suspended_until DATETIME NULL,
    suspended_permanent TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `17_tasks` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    due_date DATE NULL,
    answer_image VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_17_tasks_user FOREIGN KEY (created_by) REFERENCES `17_users`(id) ON DELETE CASCADE,
    INDEX idx_17_tasks_due_date (due_date)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `17_comments` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_17_comments_task FOREIGN KEY (task_id) REFERENCES `17_tasks`(id) ON DELETE CASCADE,
    CONSTRAINT fk_17_comments_user FOREIGN KEY (user_id) REFERENCES `17_users`(id) ON DELETE CASCADE,
    CONSTRAINT fk_17_comments_parent FOREIGN KEY (parent_id) REFERENCES `17_comments`(id) ON DELETE CASCADE,
    INDEX idx_17_comments_task (task_id),
    INDEX idx_17_comments_parent (parent_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `17_reactions` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comment_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reaction VARCHAR(16) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_17_reactions_comment FOREIGN KEY (comment_id) REFERENCES `17_comments`(id) ON DELETE CASCADE,
    CONSTRAINT fk_17_reactions_user FOREIGN KEY (user_id) REFERENCES `17_users`(id) ON DELETE CASCADE,
    UNIQUE KEY uq_17_reactions_user_comment (comment_id, user_id),
    INDEX idx_17_reactions_comment (comment_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `17_notifications` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NULL,
    comment_id INT UNSIGNED NULL,
    type ENUM('reply', 'reaction') NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_17_notifications_user FOREIGN KEY (user_id) REFERENCES `17_users`(id) ON DELETE CASCADE,
    CONSTRAINT fk_17_notifications_actor FOREIGN KEY (actor_id) REFERENCES `17_users`(id) ON DELETE SET NULL,
    CONSTRAINT fk_17_notifications_comment FOREIGN KEY (comment_id) REFERENCES `17_comments`(id) ON DELETE CASCADE,
    INDEX idx_17_notifications_user_read (user_id, is_read, created_at)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
