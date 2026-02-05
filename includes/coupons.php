<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ensure_coupon_tables(): void {
    db_query(
        "CREATE TABLE IF NOT EXISTS coupons (
            coupon_id INT(11) NOT NULL AUTO_INCREMENT,
            code VARCHAR(40) NOT NULL,
            title VARCHAR(120) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(10,2) NOT NULL,
            min_purchase DECIMAL(10,2) DEFAULT 0.00,
            max_discount DECIMAL(10,2) DEFAULT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            usage_limit INT(11) NOT NULL DEFAULT 1,
            starts_at DATETIME DEFAULT NULL,
            ends_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_published TINYINT(1) NOT NULL DEFAULT 0,
            seller_id INT(11) DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (coupon_id),
            UNIQUE KEY uq_coupon_code (code),
            KEY idx_coupon_active (is_active),
            KEY idx_coupon_seller (seller_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $columns = [
        'title' => "ALTER TABLE coupons ADD COLUMN title VARCHAR(120) DEFAULT NULL",
        'description' => "ALTER TABLE coupons ADD COLUMN description TEXT DEFAULT NULL",
        'price' => "ALTER TABLE coupons ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'is_published' => "ALTER TABLE coupons ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 0",
        'created_by' => "ALTER TABLE coupons ADD COLUMN created_by INT(11) DEFAULT NULL",
        'usage_limit' => "ALTER TABLE coupons ADD COLUMN usage_limit INT(11) NOT NULL DEFAULT 1"
    ];
    foreach ($columns as $column => $sql) {
        $exists = db_fetch(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'coupons' AND COLUMN_NAME = ?",
            [$column]
        );
        if (!$exists) {
            db_query($sql);
        }
    }
    db_query("UPDATE coupons SET usage_limit = 1 WHERE usage_limit IS NULL");

    db_query(
        "CREATE TABLE IF NOT EXISTS coupon_seller_requests (
            request_id INT(11) NOT NULL AUTO_INCREMENT,
            coupon_id INT(11) NOT NULL,
            seller_id INT(11) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            response_note VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            responded_at DATETIME NULL,
            PRIMARY KEY (request_id),
            UNIQUE KEY uq_coupon_seller (coupon_id, seller_id),
            KEY idx_coupon_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    db_query(
        "CREATE TABLE IF NOT EXISTS coupon_product_allow (
            allow_id INT(11) NOT NULL AUTO_INCREMENT,
            coupon_id INT(11) NOT NULL,
            seller_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (allow_id),
            UNIQUE KEY uq_coupon_product (coupon_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    db_query(
        "CREATE TABLE IF NOT EXISTS coupon_purchases (
            purchase_id INT(11) NOT NULL AUTO_INCREMENT,
            coupon_id INT(11) NOT NULL,
            buyer_id INT(11) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'paid',
            invoice_path VARCHAR(255) DEFAULT NULL,
            download_token VARCHAR(64) DEFAULT NULL,
            downloaded_at DATETIME NULL,
            used_at DATETIME NULL,
            uses_left INT(11) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (purchase_id),
            KEY idx_coupon_buyer (buyer_id),
            KEY idx_coupon_purchase (coupon_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $purchaseColumns = [
        'used_at' => "ALTER TABLE coupon_purchases ADD COLUMN used_at DATETIME NULL",
        'uses_left' => "ALTER TABLE coupon_purchases ADD COLUMN uses_left INT(11) NOT NULL DEFAULT 1"
    ];
    foreach ($purchaseColumns as $column => $sql) {
        $exists = db_fetch(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'coupon_purchases' AND COLUMN_NAME = ?",
            [$column]
        );
        if (!$exists) {
            db_query($sql);
        }
    }
    db_query("UPDATE coupon_purchases SET uses_left = 1 WHERE uses_left IS NULL");

    db_query(
        "CREATE TABLE IF NOT EXISTS cart_coupons (
            cart_id INT(11) NOT NULL,
            coupon_purchase_id INT(11) NOT NULL,
            coupon_id INT(11) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (cart_id),
            KEY idx_cart_coupon (coupon_purchase_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    db_query(
        "CREATE TABLE IF NOT EXISTS order_coupons (
            order_id INT(11) NOT NULL,
            coupon_id INT(11) NOT NULL,
            coupon_purchase_id INT(11) NOT NULL,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function get_admin_user_id(): ?int {
    $row = db_fetch(
        "SELECT u.user_id
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE r.role_name = 'admin'
         ORDER BY u.user_id ASC
         LIMIT 1"
    );
    return $row ? (int)$row['user_id'] : null;
}
