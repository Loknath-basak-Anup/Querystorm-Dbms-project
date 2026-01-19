<?php
require_once 'includes/db.php';

// Test the get_users query
$sql = "
    SELECT u.user_id, u.full_name, u.email, u.created_at,
           COALESCE(r.role_name, u.role) AS role_name,
           CASE WHEN u.status = 'blocked' THEN 0 ELSE 1 END AS is_active,
           u.status,
           COALESCE(bp.address, 'N/A') AS buyer_address,
           COALESCE(sp.shop_name, 'N/A') AS seller_shop
    FROM users u
    LEFT JOIN roles r ON r.role_id = u.role_id
    LEFT JOIN buyer_profiles bp ON u.user_id = bp.buyer_id
    LEFT JOIN seller_profiles sp ON u.user_id = sp.seller_id
    ORDER BY u.created_at DESC
    LIMIT 10
";

try {
    $users = db_fetch_all($sql, []);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'items' => $users, 'count' => count($users)], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
