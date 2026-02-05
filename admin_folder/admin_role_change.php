<?php
if (session_status() === PHP_SESSION_NONE) {
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $base,
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

db_query(
    "CREATE TABLE IF NOT EXISTS role_change_requests (
        request_id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        current_role VARCHAR(30) NOT NULL,
        requested_role VARCHAR(30) NOT NULL,
        reason TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME NULL,
        PRIMARY KEY (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);

function remove_buyer_data(int $userId): void {
    $queries = [
        "DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM carts WHERE buyer_id = ?)",
        "DELETE FROM carts WHERE buyer_id = ?",
        "DELETE FROM order_items WHERE order_id IN (SELECT order_id FROM orders WHERE buyer_id = ?)",
        "DELETE FROM orders WHERE buyer_id = ?",
        "DELETE FROM saved_items WHERE buyer_id = ?",
        "DELETE FROM buyer_profiles WHERE buyer_id = ?",
        "DELETE FROM wallet_transactions WHERE user_id = ?"
    ];
    foreach ($queries as $sql) {
        try {
            db_query($sql, [$userId]);
        } catch (Exception $e) {
            // ignore missing tables
        }
    }
}

function remove_seller_data(int $userId): void {
    $queries = [
        "DELETE FROM order_items WHERE product_id IN (SELECT product_id FROM products WHERE seller_id = ?)",
        "DELETE FROM inventory WHERE product_id IN (SELECT product_id FROM products WHERE seller_id = ?)",
        "DELETE FROM product_images WHERE product_id IN (SELECT product_id FROM products WHERE seller_id = ?)",
        "DELETE FROM products WHERE seller_id = ?",
        "DELETE FROM shops WHERE seller_id = ?",
        "DELETE FROM seller_verification_requests WHERE seller_id = ?",
        "DELETE FROM seller_profiles WHERE seller_id = ?",
        "DELETE FROM wallet_transactions WHERE user_id = ?"
    ];
    foreach ($queries as $sql) {
        try {
            db_query($sql, [$userId]);
        } catch (Exception $e) {
            // ignore missing tables
        }
    }
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($requestId > 0 && in_array($action, ['approve', 'decline'], true)) {
        $request = db_fetch("SELECT * FROM role_change_requests WHERE request_id = ?", [$requestId]);
        if ($request) {
            $userId = (int)$request['user_id'];
            $requestedRole = $request['requested_role'];
            $currentRole = $request['current_role'];

            if ($action === 'approve') {
                $roleRow = db_fetch("SELECT role_id FROM roles WHERE role_name = ?", [$requestedRole]);
                $roleId = (int)($roleRow['role_id'] ?? 0);
                if ($roleId > 0) {
                    if ($currentRole === 'buyer') {
                        remove_buyer_data($userId);
                    } else {
                        remove_seller_data($userId);
                    }
                    db_query("UPDATE users SET role_id = ? WHERE user_id = ?", [$roleId, $userId]);
                    if ($requestedRole === 'buyer') {
                        try {
                            $exists = db_fetch("SELECT buyer_id FROM buyer_profiles WHERE buyer_id = ?", [$userId]);
                            if (!$exists) {
                                db_execute("INSERT INTO buyer_profiles (buyer_id, address, created_at) VALUES (?, '', NOW())", [$userId]);
                            }
                        } catch (Exception $e) {
                        }
                    }
                    db_query("UPDATE role_change_requests SET status = 'approved', reviewed_at = NOW() WHERE request_id = ?", [$requestId]);
                    $flash = 'Role change approved and user data cleared.';
                }
            } else {
                db_query("UPDATE role_change_requests SET status = 'declined', reviewed_at = NOW() WHERE request_id = ?", [$requestId]);
                $flash = 'Role change request declined.';
            }
        }
    }
}

$requests = db_fetch_all(
    "SELECT r.request_id, r.user_id, r.current_role, r.requested_role, r.reason, r.status, r.created_at,
            u.full_name, u.email
     FROM role_change_requests r
     INNER JOIN users u ON u.user_id = r.user_id
     WHERE r.status = 'pending'
     ORDER BY r.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Change Requests | QuickMart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin:0; font-family:'Space Grotesk',sans-serif; background:#0b1220; color:#f8fafc; padding:32px; }
        .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
        .header h1 { margin:0; }
        .card { background:#101a2c; border:1px solid rgba(148,163,184,0.2); border-radius:16px; padding:20px; margin-bottom:16px; }
        .meta { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:8px; color:#94a3b8; }
        .actions { display:flex; gap:10px; margin-top:12px; }
        .btn { border:none; border-radius:10px; padding:10px 14px; font-weight:600; cursor:pointer; }
        .btn-approve { background:#14b8a6; color:#0b1020; }
        .btn-decline { background:rgba(239,68,68,0.2); color:#fecaca; border:1px solid rgba(239,68,68,0.4); }
        .flash { background:rgba(20,184,166,0.2); border:1px solid rgba(20,184,166,0.4); padding:12px 16px; border-radius:12px; margin-bottom:16px; }
        .empty { padding:40px; border:1px dashed rgba(148,163,184,0.3); border-radius:16px; text-align:center; color:#94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Role Change Requests</h1>
        <a href="admin.php" style="color:#94a3b8;text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Admin</a>
    </div>
    <?php if ($flash !== ''): ?>
        <div class="flash"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>
    <?php if (!$requests): ?>
        <div class="empty">No pending role change requests.</div>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <div class="card">
                <h3 style="margin:0 0 8px;"><?php echo htmlspecialchars($req['full_name']); ?> (<?php echo htmlspecialchars($req['email']); ?>)</h3>
                <div class="meta">
                    <div><strong>Current:</strong> <?php echo htmlspecialchars($req['current_role']); ?></div>
                    <div><strong>Requested:</strong> <?php echo htmlspecialchars($req['requested_role']); ?></div>
                    <div><strong>Requested at:</strong> <?php echo htmlspecialchars($req['created_at']); ?></div>
                </div>
                <p style="margin-top:10px; color:#cbd5e1;"><strong>Reason:</strong> <?php echo htmlspecialchars($req['reason']); ?></p>
                <form method="post" class="actions">
                    <input type="hidden" name="request_id" value="<?php echo (int)$req['request_id']; ?>">
                    <button class="btn btn-approve" type="submit" name="action" value="approve"><i class="fa-solid fa-check"></i> Approve</button>
                    <button class="btn btn-decline" type="submit" name="action" value="decline" onclick="return confirm('Decline this role change request?');"><i class="fa-solid fa-ban"></i> Decline</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
