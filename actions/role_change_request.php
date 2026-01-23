<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

require_login();

$userId = get_user_id();
$currentRole = get_user_role() ?? '';
$requestedRole = trim($_POST['requested_role'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if ($userId === null || $currentRole === '' || $requestedRole === '' || $reason === '') {
    header("Location: " . BASE_URL . "/html/login.php");
    exit;
}

if ($currentRole === $requestedRole) {
    header("Location: " . BASE_URL . "/html/login.php");
    exit;
}

db_query(
    "CREATE TABLE IF NOT EXISTS role_change_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        current_role VARCHAR(30) NOT NULL,
        requested_role VARCHAR(30) NOT NULL,
        reason TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME NULL
    )"
);

db_execute(
    "INSERT INTO role_change_requests (user_id, current_role, requested_role, reason, status, created_at)
     VALUES (?, ?, ?, ?, 'pending', NOW())",
    [$userId, $currentRole, $requestedRole, $reason]
);

if ($currentRole === 'seller') {
    header("Location: " . BASE_URL . "/seller_dashboard/settings.php?role_request=sent");
} else {
    header("Location: " . BASE_URL . "/buyer_dashboard/settings.php?role_request=sent");
}
exit;
