<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/notifications.php';

require_role('buyer');
ensure_notifications_table();

$buyerId = get_user_id() ?? 0;
$orderId = (int)($_POST['order_id'] ?? 0);
$step = trim($_POST['step'] ?? '');

db_query(
    "CREATE TABLE IF NOT EXISTS order_delivery_steps (
        order_id INT(11) NOT NULL,
        step VARCHAR(40) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (order_id, step)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);

if ($buyerId <= 0 || $orderId <= 0 || $step === '') {
    json_out(['success' => false, 'message' => 'Invalid request.'], 400);
}

$order = db_fetch("SELECT order_id, buyer_id FROM orders WHERE order_id = ?", [$orderId]);
if (!$order || (int)$order['buyer_id'] !== $buyerId) {
    json_out(['success' => false, 'message' => 'Order not found.'], 404);
}

$delivery = db_fetch("SELECT courier_name, courier_address FROM order_delivery WHERE order_id = ?", [$orderId]) ?: [];
$courierName = $delivery['courier_name'] ?? 'Courier Service';
$courierAddress = $delivery['courier_address'] ?? 'Local Hub';

$statusMap = [
    'packing' => 'processing',
    'courier_assigned' => 'processing',
    'handoff_branch' => 'in_transit',
    'out_for_delivery' => 'out_for_delivery',
    'delivered' => 'delivered'
];

$messages = [
    'packing' => [
        'buyer' => "Packing started for order #{$orderId}.",
        'seller' => "Packing order #{$orderId} for shipment.",
        'admin' => "Order #{$orderId} is being packed."
    ],
    'courier_assigned' => [
        'buyer' => "Courier assigned: {$courierName} ({$courierAddress}).",
        'seller' => "Courier assigned for order #{$orderId}: {$courierName}.",
        'admin' => "Courier assigned for order #{$orderId}: {$courierName}."
    ],
    'handoff_branch' => [
        'buyer' => "Shipment handed to {$courierName} branch for nearby transfer.",
        'seller' => "Order #{$orderId} moved to {$courierName} branch.",
        'admin' => "Order #{$orderId} moved through courier branch."
    ],
    'out_for_delivery' => [
        'buyer' => "Delivery rider is on the way for order #{$orderId}.",
        'seller' => "Order #{$orderId} out for delivery.",
        'admin' => "Order #{$orderId} out for delivery."
    ],
    'delivered' => [
        'buyer' => "Order #{$orderId} delivered successfully.",
        'seller' => "Order #{$orderId} delivered to customer.",
        'admin' => "Order #{$orderId} delivered."
    ]
];

if (!isset($messages[$step])) {
    json_out(['success' => false, 'message' => 'Unknown step.'], 400);
}

$existingStep = db_fetch(
    "SELECT step FROM order_delivery_steps WHERE order_id = ? AND step = ?",
    [$orderId, $step]
);
if ($existingStep) {
    json_out(['success' => true, 'message' => 'Already processed.']);
}

db_execute(
    "INSERT INTO order_delivery_steps (order_id, step, created_at) VALUES (?, ?, NOW())",
    [$orderId, $step]
);

if (isset($statusMap[$step])) {
    db_execute("UPDATE orders SET status = ? WHERE order_id = ?", [$statusMap[$step], $orderId]);
}

$sellerRows = db_fetch_all(
    "SELECT DISTINCT p.seller_id
     FROM order_items oi
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE oi.order_id = ?",
    [$orderId]
);
$sellerIds = array_map(fn($row) => (int)$row['seller_id'], $sellerRows);

$adminRow = db_fetch(
    "SELECT u.user_id
     FROM users u
     INNER JOIN roles r ON r.role_id = u.role_id
     WHERE r.role_name = 'admin'
     ORDER BY u.user_id ASC
     LIMIT 1"
);
$adminId = $adminRow ? (int)$adminRow['user_id'] : null;

add_notification($buyerId, 'Delivery update', $messages[$step]['buyer'], 'delivery');
foreach ($sellerIds as $sellerId) {
    add_notification($sellerId, 'Delivery update', $messages[$step]['seller'], 'delivery');
}
if ($adminId) {
    add_notification($adminId, 'Delivery update', $messages[$step]['admin'], 'delivery');
}

json_out(['success' => true]);
