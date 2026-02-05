<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ensure_notifications_table(): void {
    db_query(
        "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            title VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(40) DEFAULT NULL,
            action_url VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (notification_id),
            KEY idx_notifications_user (user_id),
            KEY idx_notifications_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $col = db_fetch("SHOW COLUMNS FROM notifications LIKE 'action_url'");
    if (!$col) {
        db_query("ALTER TABLE notifications ADD COLUMN action_url VARCHAR(255) DEFAULT NULL");
    }
}

function add_notification(int $userId, string $title, string $message, ?string $type = null, ?string $actionUrl = null): void {
    if ($userId <= 0) {
        return;
    }
    db_execute(
        "INSERT INTO notifications (user_id, title, message, type, action_url, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$userId, $title, $message, $type, $actionUrl]
    );
}

function fetch_notifications(int $userId, int $limit = 10): array {
    if ($userId <= 0) return [];
    return db_fetch_all(
        "SELECT notification_id, title, message, type, action_url, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT {$limit}",
        [$userId]
    );
}
