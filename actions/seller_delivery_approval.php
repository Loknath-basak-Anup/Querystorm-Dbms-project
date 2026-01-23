<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

require_role('seller');
ensure_notifications_table();

$sellerId = get_user_id() ?? 0;
$orderId = (int)($_POST['order_id'] ?? 0);
$courierRaw = trim($_POST['courier'] ?? '');

if ($sellerId <= 0 || $orderId <= 0 || $courierRaw === '' || strpos($courierRaw, '|') === false) {
    header('Location: ../seller_dashboard/approve_delivery.php?err=invalid');
    exit;
}

[$courierName, $courierAddress] = array_map('trim', explode('|', $courierRaw, 2));

db_query(
    "CREATE TABLE IF NOT EXISTS order_delivery_approvals (
        approval_id INT(11) NOT NULL AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        seller_id INT(11) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        courier_name VARCHAR(120) DEFAULT NULL,
        courier_address VARCHAR(160) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME DEFAULT NULL,
        PRIMARY KEY (approval_id),
        UNIQUE KEY uniq_order_seller (order_id, seller_id),
        KEY idx_order_status (order_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);
db_query(
    "CREATE TABLE IF NOT EXISTS order_delivery (
        order_id INT(11) NOT NULL,
        courier_name VARCHAR(120) NOT NULL,
        courier_address VARCHAR(160) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);
db_query(
    "CREATE TABLE IF NOT EXISTS order_delivery_steps (
        order_id INT(11) NOT NULL,
        step VARCHAR(40) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (order_id, step)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);

$approval = db_fetch(
    "SELECT oda.approval_id, oda.status, o.buyer_id
     FROM order_delivery_approvals oda
     INNER JOIN orders o ON o.order_id = oda.order_id
     WHERE oda.order_id = ? AND oda.seller_id = ?",
    [$orderId, $sellerId]
);

if (!$approval || $approval['status'] !== 'pending') {
    header('Location: ../seller_dashboard/approve_delivery.php?err=not_found');
    exit;
}

db_execute(
    "UPDATE order_delivery_approvals
     SET status = 'approved', courier_name = ?, courier_address = ?, approved_at = NOW()
     WHERE order_id = ? AND seller_id = ?",
    [$courierName, $courierAddress, $orderId, $sellerId]
);

db_execute(
    "INSERT INTO order_delivery (order_id, courier_name, courier_address, created_at)
     VALUES (?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE courier_name = VALUES(courier_name), courier_address = VALUES(courier_address)",
    [$orderId, $courierName, $courierAddress]
);

db_execute(
    "INSERT IGNORE INTO order_delivery_steps (order_id, step, created_at)
     VALUES (?, 'packing', NOW()), (?, 'courier_assigned', NOW())",
    [$orderId, $orderId]
);

db_execute("UPDATE orders SET status = 'in_transit' WHERE order_id = ?", [$orderId]);

$buyerId = (int)($approval['buyer_id'] ?? 0);
$baseUrl = defined('BASE_URL') ? BASE_URL : '/QuickMart';
$trackUrl = $baseUrl . "/buyer_dashboard/track_product.php?order_id=" . (int)$orderId;
add_notification($buyerId, 'Delivery approved', "Seller approved order #{$orderId}. Sent to {$courierName}.", 'delivery', $trackUrl);

header('Location: ../seller_dashboard/approve_delivery.php?msg=approved');
exit;
