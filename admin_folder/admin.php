<?php
// Configure session cookie (path matches app root)
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
}
// Start session
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/supabase_storage.php';
require_once __DIR__ . '/../includes/notifications.php';

$adminNotifications = [];
$adminUserId = 0;
try {
    ensure_notifications_table();
    $adminRow = db_fetch("SELECT u.user_id FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'admin' ORDER BY u.user_id ASC LIMIT 1");
    $adminUserId = $adminRow ? (int)$adminRow['user_id'] : 0;
    if ($adminUserId > 0) {
        $adminNotifications = fetch_notifications($adminUserId, 8);
    }
} catch (Throwable $e) {
    $adminNotifications = [];
}
$adminDeliveryNotifications = array_values(array_filter($adminNotifications, function ($row) {
    return ($row['type'] ?? '') === 'delivery';
}));

// Simple admin authentication
$admin_password = 'admin1234'; // Change this to a secure password
$adminBase = ($base ?? '/QuickMart') . '/admin_folder';

// Handle logout first
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . $adminBase . '/admin.php');
    exit;
}

// Handle login submission (support Enter key submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax'])) {
    $entered_password = trim($_POST['password'] ?? '');
    
    if ($entered_password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();

        // Redirect to dashboard (without query parameters) + JS/meta fallback
        header('Location: ' . $adminBase . '/admin.php');
        echo "<!DOCTYPE html><html><head><meta http-equiv='refresh' content='0;url={$adminBase}/admin.php'></head><body><script>location.replace('{$adminBase}/admin.php');</script></body></html>";
        exit;
    } else {
        $login_error = "Invalid password! Please try again.";
    }
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Show login page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - QuickMart</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Poppins', sans-serif;
                background: radial-gradient(circle at top, #1f2937 0%, #0b0f1a 45%, #05070d 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #e2e8f0;
            }
            .login-container {
                background: rgba(17, 24, 39, 0.85);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.45);
                width: 420px;
                text-align: center;
                border: 1px solid rgba(148, 163, 184, 0.15);
                backdrop-filter: blur(18px);
            }
            .login-container h1 {
                color: #f8fafc;
                margin-bottom: 8px;
                font-size: 30px;
            }
            .login-container p {
                color: #94a3b8;
                margin-bottom: 28px;
                font-size: 0.95rem;
            }
            .form-group {
                margin-bottom: 20px;
                text-align: left;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #cbd5f5;
                font-weight: 500;
            }
            .form-group input {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                border-radius: 12px;
                font-size: 15px;
                background: rgba(15, 23, 42, 0.7);
                color: #f8fafc;
                transition: all 0.3s;
            }
            .form-group input:focus {
                outline: none;
                border-color: #38bdf8;
                box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            }
            .btn-login {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #38bdf8 0%, #6366f1 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 24px rgba(56, 189, 248, 0.35);
            }
            .error {
                background: rgba(239, 68, 68, 0.15);
                color: #fecaca;
                padding: 10px;
                border-radius: 10px;
                margin-bottom: 20px;
                border: 1px solid rgba(239, 68, 68, 0.3);
            }
            .lock-icon {
                font-size: 54px;
                color: #38bdf8;
                margin-bottom: 18px;
            }
            .btn-login:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="lock-icon"><i class="fa-solid fa-lock"></i></div>
            <h1>Admin Panel</h1>
            <p>QuickMart Control Center</p>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="password" id="passwordInput" placeholder="Enter admin password" required autocomplete="off">
                </div>
                <button type="submit" name="admin_login" class="btn-login" id="loginBtn">Login to Dashboard</button>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('loginForm');
                const passwordInput = document.getElementById('passwordInput');
                const loginBtn = document.getElementById('loginBtn');
                passwordInput.focus();
                passwordInput.disabled = false;
                passwordInput.readOnly = false;
                form.addEventListener('submit', function() {
                    loginBtn.disabled = true;
                    loginBtn.textContent = 'Logging in...';
                    setTimeout(function() {
                        loginBtn.disabled = false;
                        loginBtn.textContent = 'Login to Dashboard';
                        passwordInput.disabled = false;
                        passwordInput.readOnly = false;
                    }, 2000);
                });
                passwordInput.addEventListener('focus', function() {
                    this.disabled = false;
                    this.readOnly = false;
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Admin is logged in - show dashboard
$pendingSellerCount = 0;
$pendingRoleChangeCount = 0;
$adminDisplayName = 'Administrator';
$adminLoginTime = $_SESSION['admin_login_time'] ?? null;
$adminIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$adminDevice = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device';
$adminLocation = ($adminIp === '127.0.0.1' || $adminIp === '::1') ? 'Localhost' : 'Unknown';

db_query(
    "CREATE TABLE IF NOT EXISTS admin_revenue_entries (
        entry_id INT(11) NOT NULL AUTO_INCREMENT,
        source_type VARCHAR(40) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        note VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (entry_id),
        KEY idx_revenue_type (source_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_admin_revenue') {
    $sourceType = trim($_POST['source_type'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $allowed = ['monthly_fee', 'banner_ads'];
    if (in_array($sourceType, $allowed, true) && $amount > 0) {
        db_execute(
            "INSERT INTO admin_revenue_entries (source_type, amount, note, created_at)
             VALUES (?, ?, ?, NOW())",
            [$sourceType, $amount, $note !== '' ? $note : null]
        );
    }
    header('Location: ' . $adminBase . '/admin.php?msg=revenue_added');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_delivery_notifications') {
    $selected = $_POST['notification_ids'] ?? [];
    if (!is_array($selected)) {
        $selected = [];
    }
    $ids = array_values(array_filter(array_map('intval', $selected)));
    if ($adminUserId > 0 && !empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$adminUserId], $ids);
        db_execute("DELETE FROM notifications WHERE user_id = ? AND notification_id IN ($placeholders)", $params);
    }
    header('Location: ' . $adminBase . '/admin.php?msg=delivery_cleared');
    exit;
}

try {
    $pendingRow = db_fetch("
        SELECT COUNT(*) AS count
        FROM seller_profiles sp
        INNER JOIN users u ON u.user_id = sp.seller_id
        LEFT JOIN (
            SELECT r.*
            FROM seller_verification_requests r
            INNER JOIN (
                SELECT seller_id, MAX(created_at) AS max_created
                FROM seller_verification_requests
                GROUP BY seller_id
            ) latest ON latest.seller_id = r.seller_id AND latest.max_created = r.created_at
        ) svr ON svr.seller_id = sp.seller_id
        WHERE sp.verified = 0
          AND u.status <> 'blocked'
          AND (svr.status IS NULL OR svr.status = 'pending')
    ");
    $pendingSellerCount = (int)($pendingRow['count'] ?? 0);
} catch (Exception $e) {
    $pendingRow = db_fetch("SELECT COUNT(*) AS count FROM seller_profiles sp INNER JOIN users u ON u.user_id = sp.seller_id WHERE sp.verified = 0 AND u.status <> 'blocked'");
    $pendingSellerCount = (int)($pendingRow['count'] ?? 0);
}

try {
    $pendingRoleRow = db_fetch("SELECT COUNT(*) AS count FROM role_change_requests WHERE status = 'pending'");
    $pendingRoleChangeCount = (int)($pendingRoleRow['count'] ?? 0);
} catch (Exception $e) {
    $pendingRoleChangeCount = 0;
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    // Set error handling for AJAX - return JSON instead of HTML
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    // Verify admin is still logged in for AJAX requests
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated', 'redirect' => $adminBase . '/admin.php']);
        exit;
    }

    header('Content-Type: application/json');

    switch ($_GET['ajax']) {
        case 'get_stats':
            $ordersCountRow = db_fetch("SELECT COUNT(*) as total FROM orders WHERE status <> 'cancelled'");
            $deliveryRevenueRow = db_fetch("SELECT (COALESCE(COUNT(*), 0) * 100) AS total FROM orders WHERE status <> 'cancelled'");
            $couponRevenueRow = db_fetch("SELECT COALESCE(SUM(price), 0) as total FROM coupon_purchases WHERE status = 'paid'");
            $monthlyFeeRow = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM admin_revenue_entries WHERE source_type = 'monthly_fee'");
            $bannerAdsRow = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM admin_revenue_entries WHERE source_type = 'banner_ads'");
            $ordersCount = (int)($ordersCountRow['total'] ?? 0);
            $stats = [
                'total_users' => db_fetch("SELECT COUNT(*) as count FROM users")['count'],
                'total_buyers' => db_fetch("SELECT COUNT(*) as count FROM buyer_profiles")['count'],
                // Count only verified sellers as active sellers
                'total_sellers' => db_fetch("SELECT COUNT(*) as count FROM seller_profiles WHERE verified = 1")['count'],
                // Pending sellers waiting for admin approval
                'pending_sellers' => db_fetch("
                    SELECT COUNT(*) AS count
                    FROM seller_profiles sp
                    INNER JOIN users u ON u.user_id = sp.seller_id
                    LEFT JOIN (
                        SELECT r.*
                        FROM seller_verification_requests r
                        INNER JOIN (
                            SELECT seller_id, MAX(created_at) AS max_created
                            FROM seller_verification_requests
                            GROUP BY seller_id
                        ) latest ON latest.seller_id = r.seller_id AND latest.max_created = r.created_at
                    ) svr ON svr.seller_id = sp.seller_id
                    WHERE sp.verified = 0
                      AND u.status <> 'blocked'
                      AND (svr.status IS NULL OR svr.status = 'pending')
                ")['count'],
                'total_products' => db_fetch("SELECT COUNT(*) as count FROM products")['count'],
                'active_products' => db_fetch("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'],
                'pending_products' => db_fetch("SELECT COUNT(*) as count FROM products WHERE status = 'pending'")['count'],
                'total_orders' => db_fetch("SELECT COUNT(*) as count FROM orders")['count'],
                'total_revenue' => (float)($couponRevenueRow['total'] ?? 0)
                    + (float)($deliveryRevenueRow['total'] ?? 0)
                    + (float)($monthlyFeeRow['total'] ?? 0)
                    + (float)($bannerAdsRow['total'] ?? 0),
                'delivery_orders' => $ordersCount
            ];
            echo json_encode($stats);
            exit;

        case 'get_users':
            // Filters & pagination
            $search = trim($_GET['search'] ?? '');
            $role = trim($_GET['role'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = max(5, min(100, (int)($_GET['pageSize'] ?? 10)));

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = '(u.email LIKE ? OR u.full_name LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($role !== '') {
                $where[] = 'r.role_name = ?';
                $params[] = $role;
            }
            if ($status !== '') {
                // status column may be NULL or 'active'/'blocked'
                if ($status === 'active') {
                    $where[] = "(u.status = 'active' OR u.status IS NULL)";
                } elseif ($status === 'blocked') {
                    $where[] = "u.status = 'blocked'";
                }
            }

            $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

            // Count total
            $countRow = db_fetch("SELECT COUNT(*) AS c FROM users u LEFT JOIN roles r ON r.role_id=u.role_id $whereSql", $params);
            $total = (int)($countRow['c'] ?? 0);

            $offset = ($page - 1) * $pageSize;

            $sql = "
                SELECT u.user_id, u.full_name, u.email, u.created_at, u.profile_image_url,
                       r.role_name,
                       CASE WHEN u.status = 'blocked' THEN 0 ELSE 1 END AS is_active,
                       u.status,
                       COALESCE(bp.address, 'N/A') AS buyer_address,
                       COALESCE(sp.shop_name, 'N/A') AS seller_shop,
                       COALESCE(sp.verified, 0) AS seller_verified
                FROM users u
                LEFT JOIN roles r ON r.role_id = u.role_id
                LEFT JOIN buyer_profiles bp ON u.user_id = bp.buyer_id
                LEFT JOIN seller_profiles sp ON u.user_id = sp.seller_id
                $whereSql
                ORDER BY u.created_at DESC
                LIMIT $pageSize OFFSET $offset
            ";
            $users = db_fetch_all($sql, $params);
            echo json_encode(['items' => $users, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
            exit;

        
        case 'delete_profile_image':
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid user']);
                exit;
            }
            $row = db_fetch("SELECT profile_image_url FROM users WHERE user_id = ?", [$user_id]);
            $url = $row['profile_image_url'] ?? '';
            if ($url !== '') {
                try {
                    if (supabase_is_configured()) {
                        $supabaseUrl = getenv('SUPABASE_URL');
                        if ($supabaseUrl && strpos($url, $supabaseUrl) !== false) {
                            supabase_delete_image($url);
                        }
                    }
                } catch (Exception $e) {
                    // ignore delete errors
                }
            }
            db_query("UPDATE users SET profile_image_url = NULL WHERE user_id = ?", [$user_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'approve_seller':
            // Mark seller as verified so they become an approved seller
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid user']);
                exit;
            }
            $sellerRow = db_fetch("SELECT seller_id, verified FROM seller_profiles WHERE seller_id = ?", [$user_id]);
            if (!$sellerRow) {
                echo json_encode(['success' => false, 'error' => 'Seller profile not found']);
                exit;
            }
            if ((int)($sellerRow['verified'] ?? 0) === 1) {
                echo json_encode(['success' => true]);
                exit;
            }
            db_query("UPDATE seller_profiles SET verified = 1 WHERE seller_id = ?", [$user_id]);
            db_query("UPDATE users SET status = 'active' WHERE user_id = ?", [$user_id]);
            try {
                db_query("UPDATE seller_verification_requests SET status = 'approved' WHERE seller_id = ?", [$user_id]);
            } catch (Exception $e) {
                // Ignore missing table
            }
            echo json_encode(['success' => true]);
            exit;

        case 'decline_seller':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid user']);
                exit;
            }
            db_query("UPDATE seller_profiles SET verified = 0 WHERE seller_id = ?", [$user_id]);
            db_query("UPDATE users SET status = 'blocked' WHERE user_id = ?", [$user_id]);
            try {
                db_query("ALTER TABLE seller_verification_requests ADD COLUMN decline_reason TEXT NULL");
            } catch (Exception $e) {
                // ignore if column exists
            }
            try {
                db_query("UPDATE seller_verification_requests SET status = 'declined', decline_reason = ? WHERE seller_id = ?", [$reason !== '' ? $reason : null, $user_id]);
            } catch (Exception $e) {
                // Ignore missing table
            }
            echo json_encode(['success' => true]);
            exit;

        case 'get_pending_sellers':
            try {
                $rows = db_fetch_all("
                    SELECT
                        u.user_id,
                        u.full_name,
                        u.email,
                        u.phone,
                        sp.shop_name,
                        sp.shop_description,
                        sp.created_at,
                        svr.nid,
                        svr.date_of_birth,
                        svr.business_type,
                        svr.business_category,
                        svr.tax_id,
                        svr.business_license,
                        svr.address,
                        svr.bank_name,
                        svr.account_name,
                        svr.account_number,
                        svr.routing_number,
                        svr.branch_name,
                        svr.status AS request_status
                    FROM seller_profiles sp
                    INNER JOIN users u ON u.user_id = sp.seller_id
                    LEFT JOIN seller_verification_requests svr ON svr.seller_id = sp.seller_id
                    WHERE sp.verified = 0
                      AND u.status <> 'blocked'
                      AND (svr.status IS NULL OR svr.status = 'pending')
                    ORDER BY sp.created_at DESC
                ");
                echo json_encode(['items' => $rows]);
            } catch (Exception $e) {
                $rows = db_fetch_all("
                    SELECT
                        u.user_id,
                        u.full_name,
                        u.email,
                        u.phone,
                        sp.shop_name,
                        sp.shop_description,
                        sp.created_at
                    FROM seller_profiles sp
                    INNER JOIN users u ON u.user_id = sp.seller_id
                    WHERE sp.verified = 0
                      AND u.status <> 'blocked'
                    ORDER BY sp.created_at DESC
                ");
                echo json_encode(['items' => $rows]);
            }
            exit;

        case 'update_user_role':
            try {
                $user_id = (int)($_POST['user_id'] ?? 0);
                $role_name = trim($_POST['role'] ?? '');
                if ($user_id <= 0 || $role_name === '') {
                    echo json_encode(['success' => false, 'error' => 'Invalid user or role']);
                    exit;
                }

                // Get role_id from roles table
                $roleRow = db_fetch("SELECT role_id FROM roles WHERE role_name = ?", [$role_name]);
                if (!$roleRow || !isset($roleRow['role_id'])) {
                    echo json_encode(['success' => false, 'error' => 'Role not found']);
                    exit;
                }

                db_query("UPDATE users SET role_id = ? WHERE user_id = ?", [$roleRow['role_id'], $user_id]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
            }
            exit;

        case 'update_user_status':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_status = trim($_POST['status'] ?? ''); // 'active' | 'blocked'
            $reason = trim($_POST['reason'] ?? '');
            if ($user_id <= 0 || ($new_status !== 'active' && $new_status !== 'blocked')) { echo json_encode(['success' => false, 'error' => 'Invalid']); exit; }
            db_query("UPDATE users SET status = ? WHERE user_id = ?", [$new_status, $user_id]);
            // optional: store reason somewhere (needs a table). For now, ignore or log.
            echo json_encode(['success' => true]);
            exit;

        case 'reset_user_password':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $temp = substr(bin2hex(random_bytes(8)), 0, 8);
            if ($user_id <= 0) { echo json_encode(['success' => false, 'error' => 'Invalid']); exit; }
            // plain text for seed DB; in future use hash
            db_query("UPDATE users SET password = ? WHERE user_id = ?", [$temp, $user_id]);
            echo json_encode(['success' => true, 'temp_password' => $temp]);
            exit;

        case 'get_products':
            $search = trim($_GET['search'] ?? '');
            $where = '';
            $params = [];
            if ($search !== '') {
                $like = '%' . $search . '%';
                $where = "WHERE (p.name LIKE ? OR c.name LIKE ? OR sc.name LIKE ? OR u.email LIKE ? OR CAST(p.product_id AS CHAR) LIKE ?)";
                $params = [$like, $like, $like, $like, $like];
            }
            $products = db_fetch_all("
                SELECT p.product_id, p.name, p.price, p.status, p.created_at,
                       c.name as category, sc.name as subcategory, u.email as seller_email,
                       COALESCE(i.stock_qty, 0) as stock,
                       (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) AS image_url
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                JOIN subcategories sc ON p.subcategory_id = sc.subcategory_id
                JOIN users u ON p.seller_id = u.user_id
                LEFT JOIN inventory i ON p.product_id = i.product_id
                $where
                ORDER BY p.created_at DESC
            ", $params);
            echo json_encode($products);
            exit;

        case 'get_orders':
            $orders = db_fetch_all("
                SELECT o.order_id, o.total_amount, o.status, o.created_at,
                       u.email as buyer_email
                FROM orders o
                JOIN users u ON o.buyer_id = u.user_id
                ORDER BY o.created_at DESC
                LIMIT 100
            ");
            echo json_encode($orders);
            exit;

        case 'toggle_user':
            $user_id = (int)$_POST['user_id'];
            $current = db_fetch("SELECT status FROM users WHERE user_id = ?", [$user_id]);
            $currentStatus = $current['status'] ?? 'active';
            $new_status = ($currentStatus === 'blocked') ? 'active' : 'blocked';
            db_query("UPDATE users SET status = ? WHERE user_id = ?", [$new_status, $user_id]);
            echo json_encode(['success' => true, 'new_status' => $new_status]);
            exit;

        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            db_query("DELETE FROM users WHERE user_id = ?", [$user_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'update_product_status':
            $product_id = (int)$_POST['product_id'];
            $status = $_POST['status'];
            db_query("UPDATE products SET status = ? WHERE product_id = ?", [$status, $product_id]);
            echo json_encode(['success' => true]);
            exit;
        case 'delete_product':
            $product_id = (int)$_POST['product_id'];
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid product']);
                exit;
            }
            try {
                $imageRow = db_fetch("SELECT image_url FROM product_images WHERE product_id = ? LIMIT 1", [$product_id]);
                if ($imageRow && !empty($imageRow['image_url'])) {
                    try {
                        global $SUPABASE_CONFIG;
                        $supabaseBase = trim(getenv('SUPABASE_URL') ?: ($SUPABASE_CONFIG['url'] ?? ''));
                        if ($supabaseBase !== '' && strpos($imageRow['image_url'], $supabaseBase) !== false) {
                            supabase_delete_image($imageRow['image_url']);
                        }
                    } catch (Exception $e) {
                        // ignore storage delete failures
                    }
                }
                db_query("DELETE FROM inventory WHERE product_id = ?", [$product_id]);
                db_query("DELETE FROM product_images WHERE product_id = ?", [$product_id]);
                db_query("DELETE FROM products WHERE product_id = ?", [$product_id]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'delete_product_image':
            $product_id = (int)$_POST['product_id'];
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid product']);
                exit;
            }
            $imageRow = db_fetch("SELECT image_url FROM product_images WHERE product_id = ? LIMIT 1", [$product_id]);
            if (!$imageRow || empty($imageRow['image_url'])) {
                echo json_encode(['success' => false, 'error' => 'No image found']);
                exit;
            }
            try {
                global $SUPABASE_CONFIG;
                $supabaseBase = trim(getenv('SUPABASE_URL') ?: ($SUPABASE_CONFIG['url'] ?? ''));
                if ($supabaseBase !== '' && strpos($imageRow['image_url'], $supabaseBase) !== false) {
                    supabase_delete_image($imageRow['image_url']);
                }
                db_query("DELETE FROM product_images WHERE product_id = ?", [$product_id]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                db_query("DELETE FROM product_images WHERE product_id = ?", [$product_id]);
                echo json_encode([
                    'success' => true,
                    'warning' => 'Image removed from database. Storage delete failed: ' . $e->getMessage()
                ]);
            }
            exit;

        case 'update_order_status':
            $order_id = (int)$_POST['order_id'];
            $status = $_POST['status'];
            db_query("UPDATE orders SET status = ? WHERE order_id = ?", [$status, $order_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'get_categories':
            $categories = db_fetch_all("SELECT * FROM categories ORDER BY name");
            echo json_encode($categories);
            exit;

        case 'add_category':
            $name = trim($_POST['name']);
            $icon_class = trim($_POST['icon_class'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Category name required']);
                exit;
            }
            $category_id = (int)db_execute(
                "INSERT INTO categories (name, icon_class) VALUES (?, ?)",
                [$name, $icon_class !== '' ? $icon_class : null]
            );
            db_query("INSERT INTO subcategories (category_id, name) VALUES (?, ?)", [$category_id, 'General']);
            echo json_encode(['success' => true, 'category_id' => $category_id]);
            exit;

        case 'delete_category':
            $cat_id = (int)$_POST['category_id'];
            db_query("DELETE FROM categories WHERE category_id = ?", [$cat_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'get_subcategories':
            $subcategories = db_fetch_all("
                SELECT s.subcategory_id, s.name, s.category_id, s.icon_url, s.icon_class, c.name AS category_name, s.created_at
                FROM subcategories s
                JOIN categories c ON s.category_id = c.category_id
                ORDER BY c.name ASC, s.name ASC
            ");
            echo json_encode($subcategories);
            exit;

        case 'add_subcategory':
            $category_id = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $icon_class = trim($_POST['icon_class'] ?? '');
            if ($category_id <= 0 || $name === '') {
                echo json_encode(['success' => false, 'error' => 'Category and name required']);
                exit;
            }
            try {
                $existing = db_fetch(
                    "SELECT subcategory_id FROM subcategories WHERE category_id = ? AND name = ? LIMIT 1",
                    [$category_id, $name]
                );
                if ($existing) {
                    echo json_encode(['success' => false, 'error' => 'Subcategory already exists for this category']);
                    exit;
                }
                db_query(
                    "INSERT INTO subcategories (category_id, name, icon_class) VALUES (?, ?, ?)",
                    [$category_id, $name, $icon_class !== '' ? $icon_class : null]
                );
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'update_category':
            $category_id = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $icon_class = trim($_POST['icon_class'] ?? '');
            if ($category_id <= 0 || $name === '') {
                echo json_encode(['success' => false, 'error' => 'Category name required']);
                exit;
            }
            db_query(
                "UPDATE categories SET name = ?, icon_class = ? WHERE category_id = ?",
                [$name, $icon_class !== '' ? $icon_class : null, $category_id]
            );
            echo json_encode(['success' => true]);
            exit;

        case 'update_subcategory':
            $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $icon_class = trim($_POST['icon_class'] ?? '');
            if ($subcategory_id <= 0 || $category_id <= 0 || $name === '') {
                echo json_encode(['success' => false, 'error' => 'Category and name required']);
                exit;
            }
            db_query(
                "UPDATE subcategories SET category_id = ?, name = ?, icon_class = ? WHERE subcategory_id = ?",
                [$category_id, $name, $icon_class !== '' ? $icon_class : null, $subcategory_id]
            );
            echo json_encode(['success' => true]);
            exit;

        case 'delete_subcategory':
            $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);
            if ($subcategory_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid subcategory']);
                exit;
            }
            db_query("DELETE FROM subcategories WHERE subcategory_id = ?", [$subcategory_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'get_banners':
            $banners = db_fetch_all("SELECT banner_id, title, subtitle, image_url, link_url, position, is_active, sort_order, starts_at, ends_at, created_at FROM banners ORDER BY sort_order ASC, banner_id DESC");
            echo json_encode($banners);
            exit;

        case 'update_banner_active':
            $banner_id = (int)($_POST['banner_id'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? 0);
            if ($banner_id <= 0) { echo json_encode(['success' => false, 'error' => 'Invalid banner']); exit; }
            db_query("UPDATE banners SET is_active = ? WHERE banner_id = ?", [$is_active, $banner_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'delete_banner':
            $banner_id = (int)($_POST['banner_id'] ?? 0);
            if ($banner_id <= 0) { echo json_encode(['success' => false, 'error' => 'Invalid banner']); exit; }
            db_query("DELETE FROM banners WHERE banner_id = ?", [$banner_id]);
            echo json_encode(['success' => true]);
            exit;

        case 'save_banner':
            $banner_id = (int)($_POST['banner_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $link_url = trim($_POST['link_url'] ?? '');
            $position = trim($_POST['position'] ?? 'products_top');
            $sort_order = (int)($_POST['sort_order'] ?? 0);
            $starts_at = $_POST['starts_at'] !== '' ? $_POST['starts_at'] : null;
            $ends_at = $_POST['ends_at'] !== '' ? $_POST['ends_at'] : null;
            $image_url = trim($_POST['image_url'] ?? '');

            $uploadUrl = '';
            if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid image type.']); exit;
                }
                $uploadDir = __DIR__ . '/../uploads/banners';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $filename = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $uploadDir . '/' . $filename;
                if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
                    echo json_encode(['success' => false, 'error' => 'Upload failed.']); exit;
                }
                $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
                $uploadUrl = $base . '/uploads/banners/' . $filename;
            }

            $finalImage = $uploadUrl !== '' ? $uploadUrl : $image_url;
            if ($banner_id > 0) {
                if ($finalImage === '') {
                    $existing = db_fetch("SELECT image_url FROM banners WHERE banner_id = ?", [$banner_id]);
                    $finalImage = $existing['image_url'] ?? '';
                }
                if ($finalImage === '') {
                    echo json_encode(['success' => false, 'error' => 'Image URL required.']); exit;
                }
                db_query(
                    "UPDATE banners SET title = ?, subtitle = ?, image_url = ?, link_url = ?, position = ?, sort_order = ?, starts_at = ?, ends_at = ? WHERE banner_id = ?",
                    [
                        $title !== '' ? $title : null,
                        $subtitle !== '' ? $subtitle : null,
                        $finalImage,
                        $link_url !== '' ? $link_url : null,
                        $position,
                        $sort_order,
                        $starts_at,
                        $ends_at,
                        $banner_id
                    ]
                );
                echo json_encode(['success' => true]);
                exit;
            }

            if ($finalImage === '') {
                echo json_encode(['success' => false, 'error' => 'Image is required.']); exit;
            }
            db_query(
                "INSERT INTO banners (title, subtitle, image_url, link_url, position, sort_order, starts_at, ends_at, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                [
                    $title !== '' ? $title : null,
                    $subtitle !== '' ? $subtitle : null,
                    $finalImage,
                    $link_url !== '' ? $link_url : null,
                    $position,
                    $sort_order,
                    $starts_at,
                    $ends_at
                ]
            );
            echo json_encode(['success' => true]);
            exit;

        case 'get_table_data':
            $table = trim($_GET['table'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = max(5, min(100, (int)($_GET['pageSize'] ?? 25)));
            $offset = ($page - 1) * $pageSize;
            // Whitelist tables
            $allowed = [
                'users','roles','buyer_profiles','seller_profiles','banners','categories','subcategories','inventory','product_images',
                'products','orders','order_items','carts','cart_items','conversations','messages','wallet_transactions'
            ];
            if (!in_array($table, $allowed, true)) {
                echo json_encode(['success' => false, 'error' => 'Table not allowed']);
                exit;
            }

            $countRow = db_fetch("SELECT COUNT(*) as c FROM `$table`");
            $total = (int)($countRow['c'] ?? 0);

            // Order preference
            $order = '';
            $cols = db_fetch_all("SHOW COLUMNS FROM `$table`");
            $colNames = array_map(fn($c) => $c['Field'], $cols);
            if (in_array('created_at', $colNames, true)) {
                $order = 'ORDER BY created_at DESC';
            } elseif (in_array('id', $colNames, true)) {
                $order = 'ORDER BY id DESC';
            }

            $rows = db_fetch_all("SELECT * FROM `$table` $order LIMIT $pageSize OFFSET $offset");
            echo json_encode(['success' => true, 'items' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
            exit;
    }
}

// Dashboard starts here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QuickMart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #0b0f1a;
            --panel: rgba(17, 24, 39, 0.8);
            --stroke: rgba(148, 163, 184, 0.15);
            --card: rgba(30, 41, 59, 0.65);
            --muted: #94a3b8;
            --text: #e2e8f0;
            --accent: #38bdf8;
            --accent-strong: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at top left, #131a2b 0%, #0b0f1a 45%, #06070d 100%);
            color: var(--text);
            min-height: 100vh;
        }
        .app-shell { display: flex; min-height: 100vh; opacity: 0; transform: translateY(20px); transition: 0.6s; }
        body.loaded .app-shell { opacity: 1; transform: translateY(0); }
        .sidebar {
            width: 260px;
            background: rgba(10, 14, 25, 0.92);
            border-right: 1px solid rgba(148, 163, 184, 0.12);
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            transition: width 0.3s ease;
        }
        .sidebar.collapsed { width: 88px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            display: grid;
            place-items: center;
            color: #0b0f1a;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(56, 189, 248, 0.4);
        }
        .brand span { font-weight: 700; letter-spacing: 0.5px; font-size: 18px; }
        .sidebar.collapsed .brand span { display: none; }
        .collapse-btn {
            margin-left: auto;
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: var(--muted);
            border-radius: 12px;
            padding: 6px 10px;
            cursor: pointer;
        }
        .nav-group { display: flex; flex-direction: column; gap: 10px; }
        .nav-title { text-transform: uppercase; letter-spacing: 1px; font-size: 11px; color: var(--muted); margin-bottom: 8px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            color: var(--muted);
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .nav-link.active,
        .nav-link:hover { color: var(--text); background: rgba(56, 189, 248, 0.12); border-color: rgba(56, 189, 248, 0.3); }
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .nav-title { display: none; }
        .sidebar-footer { margin-top: auto; padding: 16px; border-radius: 16px; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.2); color: #bae6fd; font-size: 12px; }
        .sidebar.collapsed .sidebar-footer { display: none; }
        .main-content { flex: 1; padding: 28px 34px 40px; display: flex; flex-direction: column; gap: 24px; }
        .topbar {
            position: relative;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--stroke);
            backdrop-filter: blur(18px);
        }
        .topbar h1 { font-size: 22px; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; }
        .notification-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(15, 23, 42, 0.6);
            color: var(--text);
            cursor: pointer;
        }
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: grid;
            place-items: center;
            padding: 0 6px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.35);
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 10px 14px;
            border-radius: 12px;
            color: var(--muted);
        }
        .search-box input { background: transparent; border: none; color: var(--text); outline: none; min-width: 220px; }
        .profile { position: relative; }
        .profile-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.2);
            cursor: pointer;
            color: var(--text);
        }
        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            display: grid;
            place-items: center;
            color: #0b0f1a;
            font-weight: 700;
        }
        .profile-menu {
            position: absolute;
            right: 0;
            top: 52px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            padding: 12px;
            min-width: 180px;
            display: none;
            flex-direction: column;
            gap: 8px;
            z-index: 3000;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.45);
        }
        .profile-menu a { color: var(--muted); text-decoration: none; padding: 8px 10px; border-radius: 8px; }
        .profile-menu a:hover { background: rgba(56, 189, 248, 0.12); color: var(--text); }
        .profile-meta {
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            padding-top: 10px;
            font-size: 11px;
            color: var(--muted);
            line-height: 1.4;
        }
        .notification-list { display: grid; gap: 12px; max-height: 60vh; overflow-y: auto; }
        .notification-card {
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 14px;
            padding: 14px;
        }
        .notification-card h4 { margin-bottom: 6px; font-size: 15px; }
        .notification-card p { margin-bottom: 6px; font-size: 12px; color: var(--muted); }
        .notification-actions { display: flex; gap: 8px; margin-top: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .stat-card {
            background: var(--card);
            border-radius: 18px;
            padding: 20px;
            border: 1px solid var(--stroke);
            backdrop-filter: blur(20px);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.5); }
        .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .stat-icon { width: 40px; height: 40px; border-radius: 12px; background: rgba(56, 189, 248, 0.15); display: grid; place-items: center; color: var(--accent); }
        .stat-card h3 { font-size: 13px; color: var(--muted); font-weight: 500; }
        .stat-value { font-size: 26px; font-weight: 700; margin-top: 4px; }
        .card-block {
            background: rgba(15, 23, 42, 0.75);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--stroke);
            backdrop-filter: blur(18px);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .card-header h2 { font-size: 18px; }
        .badge { padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #fecaca; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #fde68a; }
        .badge-info { background: rgba(56, 189, 248, 0.2); color: #bae6fd; }
        .table-wrap { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table thead th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--muted); padding: 12px; border-bottom: 1px solid rgba(148, 163, 184, 0.2); }
        table tbody td { padding: 14px 12px; border-bottom: 1px solid rgba(148, 163, 184, 0.1); font-size: 13px; }
        table tbody tr:hover { background: rgba(30, 41, 59, 0.5); }
        .action-btn { padding: 6px 10px; border-radius: 8px; border: 1px solid transparent; background: rgba(56, 189, 248, 0.15); color: var(--text); font-size: 12px; cursor: pointer; margin: 2px; }
        .btn-approve { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .btn-delete { background: rgba(239, 68, 68, 0.2); color: #fecaca; }
        .btn-edit { background: rgba(99, 102, 241, 0.2); color: #c7d2fe; }
        .btn-block { background: rgba(245, 158, 11, 0.2); color: #fde68a; }
        .pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(56, 189, 248, 0.15); color: #bae6fd; font-size: 12px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .form-group label { font-size: 12px; color: var(--muted); margin-bottom: 6px; display: block; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(15, 23, 42, 0.6); color: var(--text); }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-strong)); color: #0b0f1a; padding: 10px 18px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-secondary { background: rgba(148, 163, 184, 0.15); color: #e2e8f0; padding: 10px 18px; border: 1px solid rgba(148, 163, 184, 0.25); border-radius: 12px; font-weight: 600; cursor: pointer; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(3, 7, 18, 0.8); align-items: center; justify-content: center; z-index: 1000; }
        .modal.show { display: flex; }
        .modal-content { background: rgba(15, 23, 42, 0.95); border-radius: 18px; padding: 26px; border: 1px solid rgba(148, 163, 184, 0.2); width: min(520px, 90%); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .close-modal { background: none; border: none; color: var(--muted); font-size: 22px; cursor: pointer; }
        .loading { text-align: center; padding: 30px; color: var(--muted); }
        .spinner { border: 3px solid rgba(148, 163, 184, 0.2); border-top: 3px solid var(--accent); border-radius: 50%; width: 34px; height: 34px; animation: spin 1s linear infinite; margin: 0 auto 14px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .content-grid { display: grid; gap: 20px; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .quick-actions button { background: rgba(15, 23, 42, 0.6); color: var(--text); border: 1px solid rgba(148, 163, 184, 0.2); padding: 10px 14px; border-radius: 12px; cursor: pointer; }
        @media (max-width: 1024px) { .main-content { padding: 20px; } }
        @media (max-width: 768px) { .topbar { flex-direction: column; align-items: flex-start; gap: 16px; } .search-box input { min-width: 140px; } }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-logo"><i class="fa-solid fa-shield"></i></div>
                <span>QuickMart Admin</span>
                <button class="collapse-btn" id="collapseBtn"><i class="fa-solid fa-bars"></i></button>
            </div>

            <div class="nav-group">
                <div class="nav-title">Dashboard</div>
                <button class="nav-link active" data-tab="overview" onclick="switchTab('overview', this)"><i class="fa-solid fa-chart-column"></i><span>Overview</span></button>
                <button class="nav-link" data-tab="users" onclick="switchTab('users', this)"><i class="fa-solid fa-users"></i><span>Users</span></button>
                <button class="nav-link" data-tab="products" onclick="switchTab('products', this)"><i class="fa-solid fa-store"></i><span>Products</span></button>
                <button class="nav-link" data-tab="orders" onclick="switchTab('orders', this)"><i class="fa-solid fa-network-wired"></i><span>Orders</span></button>
            </div>

            <div class="nav-group">
                <div class="nav-title">Manage</div>
                <button class="nav-link" data-tab="categories" onclick="switchTab('categories', this)"><i class="fa-solid fa-folder"></i><span>Categories</span></button>
                <button class="nav-link" data-tab="banners" onclick="switchTab('banners', this)"><i class="fa-solid fa-image"></i><span>Banners</span></button>
                <button class="nav-link" data-tab="data" onclick="switchTab('data', this)"><i class="fa-solid fa-database"></i><span>Data Explorer</span></button>
                <button class="nav-link" data-tab="settings" onclick="switchTab('settings', this)"><i class="fa-solid fa-gear"></i><span>Settings</span></button>
            </div>

            <div class="sidebar-footer">
                <strong>System Health</strong>
                <p style="margin-top:8px;">All services operational</p>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>QuickMart Admin Panel</h1>
                    <div class="pill"><i class="fa-solid fa-bolt"></i> Real-time monitoring enabled</div>
                </div>
                <div class="topbar-actions">
                    <button class="btn-primary" id="couponCenterBtn" onclick="window.location.href='admin_coupons.php'" style="display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-ticket"></i> Coupons</button>
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search dashboard">
                    </div>
                    <button class="notification-btn" id="sellerNotificationsBtn" title="Seller verification requests">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($pendingSellerCount > 0): ?>
                            <span class="notification-badge" id="sellerNotificationBadge"><?php echo (int)$pendingSellerCount; ?></span>
                        <?php else: ?>
                            <span class="notification-badge" id="sellerNotificationBadge" style="display:none;">0</span>
                        <?php endif; ?>
                    </button>
                    <button class="notification-btn" onclick="window.location.href='admin_role_change.php'" title="Role change requests">
                        <i class="fa-solid fa-user-gear"></i>
                        <?php if ($pendingRoleChangeCount > 0): ?>
                            <span class="notification-badge"><?php echo (int)$pendingRoleChangeCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="profile">
                        <button class="profile-toggle" id="profileToggle">
                            <span class="profile-avatar">A</span>
                            <span><?php echo htmlspecialchars($adminDisplayName); ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="profile-menu" id="profileMenu">
                            <div style="font-weight:600; font-size:13px; color:var(--text); padding: 4px 10px;">
                                <?php echo htmlspecialchars($adminDisplayName); ?>
                            </div>
                            <a href="#" onclick="switchTab('settings', this)"><i class="fa-solid fa-gear"></i> Settings</a>
                            <a href="?logout=1"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                            <div class="profile-meta">
                                <div>Signed in: <?php echo $adminLoginTime ? date('M d, Y H:i', (int)$adminLoginTime) : 'Unknown'; ?></div>
                                <div>Device: <?php echo htmlspecialchars($adminDevice); ?></div>
                                <div>IP: <?php echo htmlspecialchars($adminIp); ?></div>
                                <div>Location: <?php echo htmlspecialchars($adminLocation); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div id="seller-notification-modal" class="modal">
                <div class="modal-content" style="width:min(720px, 92%);">
                    <div class="modal-header">
                        <h2>Seller Verification Requests</h2>
                        <button class="close-modal" onclick="closeModal('seller-notification-modal')">&times;</button>
                    </div>
                    <div style="display:grid; gap:14px;">
                        <p style="color:var(--muted);">Open the verification center to review seller documents and approve or decline requests.</p>
                        <a class="btn-primary" href="admin_verification.php" style="text-decoration:none; width:fit-content;">
                            <i class="fa-solid fa-shield-check"></i> Open Verification Center
                        </a>
                    </div>
                </div>
            </div>

            <section class="content-grid">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <h3>Total Users</h3>
                                <div class="stat-value" id="stat-users">--</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <h3>Total Products</h3>
                                <div class="stat-value" id="stat-products">--</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-basket-shopping"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <h3>Total Orders</h3>
                                <div class="stat-value" id="stat-orders">--</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-network-wired"></i></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <div>
                                <h3>Total Revenue</h3>
                                <div class="stat-value" id="stat-revenue">--</div>
                            </div>
                            <div class="stat-icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                        </div>
                    </div>
                </div>
                <div id="tab-overview" class="tab-content active">
                    <div class="card-block">
                        <div class="card-header">
                            <h2>Dashboard Overview</h2>
                            <span class="badge badge-info">Live</span>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-top">
                                    <div>
                                        <h3>Buyers</h3>
                                        <div class="stat-value" id="stat-buyers">--</div>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-top">
                                    <div>
                                        <h3>Sellers</h3>
                                        <div class="stat-value" id="stat-sellers">--</div>
                                        <div id="stat-pending-sellers" style="font-size:12px; color:var(--muted);"></div>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-store"></i></div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-top">
                                    <div>
                                        <h3>Active Products</h3>
                                        <div class="stat-value" id="stat-active-products">--</div>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-top">
                                    <div>
                                        <h3>Pending Products</h3>
                                        <div class="stat-value" id="stat-pending-products">--</div>
                                    </div>
                                    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-block">
                        <div class="card-header">
                            <h2>Quick Actions</h2>
                            <span class="pill"><i class="fa-solid fa-lightning-bolt"></i> Optimize workflow</span>
                        </div>
                        <div class="quick-actions">
                            <button onclick="switchTab('users', this)"><i class="fa-solid fa-users"></i> View Users</button>
                            <button onclick="switchTab('products', this)"><i class="fa-solid fa-store"></i> Manage Products</button>
                            <button onclick="switchTab('orders', this)"><i class="fa-solid fa-network-wired"></i> Check Orders</button>
                            <button onclick="switchTab('categories', this)"><i class="fa-solid fa-folder"></i> Manage Categories</button>
                            <button onclick="window.location.href='admin_wallet.php'"><i class="fa-solid fa-wallet"></i> Admin Wallet</button>
                            <button onclick="window.location.href='admin_coupons.php'"><i class="fa-solid fa-ticket"></i> Manage Coupons</button>
                        </div>
                    </div>

                    <div class="card-block">
                        <div class="card-header">
                            <h2>Revenue Sources</h2>
                            <span class="pill"><i class="fa-solid fa-coins"></i> Admin income</span>
                        </div>
                        <div class="form-row" style="margin-bottom:16px;">
                            <div class="form-group">
                                <label>Source</label>
                                <select name="source_type" form="adminRevenueForm">
                                    <option value="monthly_fee">Monthly Website Fee (Seller)</option>
                                    <option value="banner_ads">Banner Ads</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (BDT)</label>
                                <input type="number" step="0.01" min="0" name="amount" form="adminRevenueForm" required>
                            </div>
                            <div class="form-group" style="flex:2;">
                                <label>Note (optional)</label>
                                <input type="text" name="note" form="adminRevenueForm" placeholder="e.g., July subscription">
                            </div>
                            <div style="align-self:flex-end;">
                                <form id="adminRevenueForm" method="POST">
                                    <input type="hidden" name="action" value="add_admin_revenue">
                                    <button class="btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Add Revenue</button>
                                </form>
                            </div>
                        </div>
                        <div class="notification-card" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:12px;">
                            <?php
                                $deliveryOrders = db_fetch("SELECT COUNT(*) AS total FROM orders WHERE status <> 'cancelled'");
                                $deliveryRevenue = ((int)($deliveryOrders['total'] ?? 0)) * 100;
                                $couponRevenue = db_fetch("SELECT COALESCE(SUM(price), 0) AS total FROM coupon_purchases WHERE status = 'paid'");
                                $monthlyRevenue = db_fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM admin_revenue_entries WHERE source_type = 'monthly_fee'");
                                $bannerRevenue = db_fetch("SELECT COALESCE(SUM(amount), 0) AS total FROM admin_revenue_entries WHERE source_type = 'banner_ads'");
                            ?>
                            <div>
                                <h4>Coupon Sales</h4>
                                <p>BDT <?php echo number_format((float)($couponRevenue['total'] ?? 0), 2); ?></p>
                            </div>
                            <div>
                                <h4>Delivery Charges</h4>
                                <p>BDT <?php echo number_format((float)$deliveryRevenue, 2); ?></p>
                            </div>
                            <div>
                                <h4>Seller Monthly Fees</h4>
                                <p>BDT <?php echo number_format((float)($monthlyRevenue['total'] ?? 0), 2); ?></p>
                            </div>
                            <div>
                                <h4>Banner Ads</h4>
                                <p>BDT <?php echo number_format((float)($bannerRevenue['total'] ?? 0), 2); ?></p>
                            </div>
                        </div>
                    </div>
                
                    <div class="card-block">
                        <div class="card-header">
                            <h2>Delivery Updates</h2>
                            <span class="pill"><i class="fa-solid fa-truck-fast"></i> Live</span>
                        </div>
                        <div class="notification-list">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px;">
                                <label style="display:flex; align-items:center; gap:8px; color:var(--muted); font-size:0.9rem;">
                                    <input type="checkbox" id="deliverySelectAll">
                                    Select all
                                </label>
                                <form method="POST" onsubmit="return confirm('Clear selected delivery updates?');">
                                    <input type="hidden" name="action" value="clear_delivery_notifications">
                                    <div id="deliverySelectedInputs"></div>
                                    <button class="action-btn" type="submit"><i class="fa-solid fa-trash"></i> Clear Selected</button>
                                </form>
                            </div>
                            <?php if (empty($adminDeliveryNotifications)): ?>
                                <div class="notification-card">
                                    <h4>No delivery updates yet</h4>
                                    <p>Delivery notifications will appear here once orders are dispatched.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($adminDeliveryNotifications as $notif): ?>
                                    <div class="notification-card" style="display:flex; gap:12px;">
                                        <input type="checkbox" class="delivery-select" value="<?php echo (int)($notif['notification_id'] ?? 0); ?>">
                                        <div>
                                            <h4><?php echo htmlspecialchars($notif['title'] ?? 'Update'); ?></h4>
                                            <p><?php echo htmlspecialchars($notif['message'] ?? ''); ?></p>
                                            <small><?php echo htmlspecialchars($notif['created_at'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
</div>

                <div id="tab-users" class="tab-content">
                    <div class="card-block">
                        <div class="card-header"><h2>User Management</h2><span class="badge badge-info">Directory</span></div>
                        <div class="form-row" style="margin-bottom:16px;">
                            <div class="form-group"><input id="user-search" type="text" placeholder="Search name/email"></div>
                            <div class="form-group">
                                <select id="user-role-filter"><option value="">All Roles</option><option value="buyer">Buyer</option><option value="seller">Seller</option><option value="admin">Admin</option></select>
                            </div>
                            <div class="form-group">
                                <select id="user-status-filter"><option value="">All Status</option><option value="active">Active</option><option value="blocked">Blocked</option></select>
                            </div>
                            <div class="form-group">
                                <select id="user-page-size"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select>
                            </div>
                            <div><button class="btn-primary" onclick="loadUsers(1)"><i class="fa-solid fa-magnifying-glass"></i> Apply</button></div>
                        </div>
                        <div class="loading" id="users-loading"><div class="spinner"></div><p>Loading users...</p></div>
                        <div id="users-content" style="display:none;">
                            <div class="table-wrap">
                                <table id="users-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Details</th><th>Avatar</th><th>Actions</th></tr></thead><tbody id="users-tbody"></tbody></table>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
                                <div id="users-count" style="color:var(--muted)"></div>
                                <div style="display:flex; gap:8px;">
                                    <button class="action-btn" onclick="changeUsersPage(-1)"><i class="fa-solid fa-arrow-left"></i> Prev</button>
                                    <span id="users-page" style="align-self:center; color:var(--muted)">Page 1</span>
                                    <button class="action-btn" onclick="changeUsersPage(1)">Next <i class="fa-solid fa-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-products" class="tab-content">
                    <div class="card-block">
                        <div class="card-header"><h2>Product Management</h2><span class="badge badge-info">Inventory</span></div>
                        <div class="form-row" style="margin-bottom:16px;">
                            <div class="form-group" style="flex:2;">
                                <input id="product-search" type="text" placeholder="Search name, category, or seller email">
                            </div>
                            <div>
                                <button class="btn-primary" onclick="loadProducts()"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                            </div>
                        </div>
                        <div class="loading" id="products-loading"><div class="spinner"></div><p>Loading products...</p></div>
                        <div id="products-content" style="display:none;">
                            <div class="table-wrap">
                                <table id="products-table"><thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Subcategory</th><th>Price</th><th>Stock</th><th>Status</th><th>Seller</th><th>Actions</th></tr></thead><tbody id="products-tbody"></tbody></table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-orders" class="tab-content">
                    <div class="card-block">
                        <div class="card-header"><h2>Order Management</h2><span class="badge badge-info">Operations</span></div>
                        <div class="loading" id="orders-loading"><div class="spinner"></div><p>Loading orders...</p></div>
                        <div id="orders-content" style="display:none;">
                            <div class="table-wrap">
                                <table id="orders-table"><thead><tr><th>Order ID</th><th>Buyer</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody id="orders-tbody"></tbody></table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-categories" class="tab-content">
                    <div class="card-block">
                        <div class="card-header">
                            <h2>Category Management</h2>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-primary" onclick="showAddCategoryModal()"><i class="fa-solid fa-plus"></i> Add Category</button>
                            </div>
                        </div>
                        <div class="form-row" style="margin-bottom:16px;">
                            <div class="form-group" style="flex:2;">
                                <input id="category-search" type="text" placeholder="Search categories">
                            </div>
                        </div>
                        <div class="loading" id="categories-loading"><div class="spinner"></div><p>Loading categories...</p></div>
                        <div id="categories-content" style="display:none;">
                            <div class="table-wrap">
                                <table id="categories-table">
                                    <thead>
                                        <tr><th>ID</th><th>Icon</th><th>Category</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody id="categories-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-banners" class="tab-content">
                    <div class="card-block">
                        <div class="card-header"><h2>Banner Management</h2><span class="badge badge-info">Marketing</span></div>
                        <form id="banner-form" style="margin-bottom:16px;">
                            <input type="hidden" name="banner_id" id="banner-id" value="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" id="banner-title" placeholder="e.g., Summer Deals">
                                </div>
                                <div class="form-group">
                                    <label>Subtitle</label>
                                    <input type="text" name="subtitle" id="banner-subtitle" placeholder="Short message">
                                </div>
                                <div class="form-group">
                                    <label>Position</label>
                                    <select name="position" id="banner-position">
                                        <option value="products_top">Products Top</option>
                                        <option value="home_hero">Home Hero</option>
                                        <option value="shop_sidebar">Shop Sidebar</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Sort Order</label>
                                    <input type="number" name="sort_order" id="banner-sort" value="0">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Link URL</label>
                                    <input type="text" name="link_url" id="banner-link" placeholder="https://example.com">
                                </div>
                                <div class="form-group">
                                    <label>Starts At</label>
                                    <input type="datetime-local" name="starts_at" id="banner-starts">
                                </div>
                                <div class="form-group">
                                    <label>Ends At</label>
                                    <input type="datetime-local" name="ends_at" id="banner-ends">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Image URL</label>
                                    <input type="text" name="image_url" id="banner-image-url" placeholder="/QuickMart/images/...">
                                </div>
                                <div class="form-group">
                                    <label>Upload Image</label>
                                    <input type="file" name="image_file" id="banner-image-file" accept="image/*">
                                </div>
                                <div style="align-self:flex-end; display:flex; gap:10px;">
                                    <button class="btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Save Banner</button>
                                    <button class="btn-secondary" type="button" onclick="resetBannerForm()">Clear</button>
                                </div>
                            </div>
                        </form>
                        <div class="loading" id="banners-loading"><div class="spinner"></div><p>Loading banners...</p></div>
                        <div id="banners-content" style="display:none;">
                            <div class="table-wrap">
                                <table id="banners-table"><thead><tr><th>ID</th><th>Title</th><th>Subtitle</th><th>Image URL</th><th>Link URL</th><th>Position</th><th>Active</th><th>Created</th><th>Actions</th></tr></thead><tbody id="banners-tbody"></tbody></table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-data" class="tab-content">
                    <div class="card-block">
                        <div class="card-header"><h2>Data Explorer</h2><span class="badge badge-info">Read-only</span></div>
                        <div class="form-row" style="margin-bottom:16px;">
                            <div class="form-group"><select id="data-table-select"><option value="users">users</option><option value="roles">roles</option><option value="buyer_profiles">buyer_profiles</option><option value="seller_profiles">seller_profiles</option><option value="banners">banners</option><option value="categories">categories</option><option value="subcategories">subcategories</option><option value="inventory">inventory</option><option value="product_images">product_images</option><option value="products">products</option><option value="orders">orders</option><option value="order_items">order_items</option><option value="carts">carts</option><option value="cart_items">cart_items</option><option value="conversations">conversations</option><option value="messages">messages</option><option value="wallet_transactions">wallet_transactions</option></select></div>
                            <div class="form-group"><select id="data-page-size"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></div>
                            <div><button class="btn-primary" onclick="loadTableData(1)"><i class="fa-solid fa-arrows-rotate"></i> Load</button></div>
                        </div>
                        <div class="loading" id="data-loading" style="display:none;"><div class="spinner"></div><p>Loading data...</p></div>
                        <div id="data-content" style="display:none;">
                            <div class="table-wrap" style="max-height:400px;"><table id="data-table" style="min-width:600px;"><thead id="data-thead"></thead><tbody id="data-tbody"></tbody></table></div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                                <div id="data-count" style="color:var(--muted)"></div>
                                <div style="display:flex; gap:8px;">
                                    <button class="action-btn" onclick="changeDataPage(-1)"><i class="fa-solid fa-arrow-left"></i> Prev</button>
                                    <span id="data-page" style="align-self:center; color:var(--muted)">Page 1</span>
                                    <button class="action-btn" onclick="changeDataPage(1)">Next <i class="fa-solid fa-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-settings" class="tab-content">
                    <div class="card-block">
                        <div class="card-header"><h2>Platform Settings</h2><span class="badge badge-info">System</span></div>
                        <div class="form-row">
                            <div class="form-group"><label>Platform Commission (%)</label><input type="number" value="5" min="0" max="100" step="0.5"></div>
                            <div class="form-group"><label>Tax Rate (%)</label><input type="number" value="15" min="0" max="100" step="0.5"></div>
                            <div class="form-group"><label>Minimum Order Amount</label><input type="number" value="100" min="0"></div>
                            <div class="form-group"><label>Admin Email</label><input type="email" value="admin@quickmart.com"></div>
                            <div class="form-group"><label>Product Auto-Approval</label><select><option value="0">Manual Approval Required</option><option value="1">Auto-Approve All Products</option></select></div>
                        </div>
                        <div style="margin-top:16px;"><button class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="add-category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Add New Category</h2><button class="close-modal" onclick="closeModal('add-category-modal')">&times;</button></div>
            <form id="add-category-form">
                <div class="form-group"><label>Category Name</label><input type="text" name="category_name" required placeholder="e.g., Gaming Accessories"></div>
                <div class="form-group"><label>Icon Class</label><input type="text" name="category_icon" placeholder="fa-solid fa-diagram-next"></div>
                <div class="form-group" style="display:flex; gap:10px; align-items:center;">
                    <button type="button" class="btn-secondary" onclick="previewIcon('add-category-icon-preview', document.querySelector('#add-category-form [name=category_icon]').value)">Preview Icon</button>
                    <div id="add-category-icon-preview" style="width:32px; height:32px; border-radius:8px; border:1px solid rgba(148,163,184,0.4); display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:12px;">N/A</div>
                </div>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Add Category</button>
            </form>
        </div>
    </div>

    <div id="edit-category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Edit Category</h2><button class="close-modal" onclick="closeModal('edit-category-modal')">&times;</button></div>
            <form id="edit-category-form">
                <input type="hidden" id="edit-category-id">
                <div class="form-group"><label>Category Name</label><input type="text" id="edit-category-name" required></div>
                <div class="form-group"><label>Icon Class</label><input type="text" id="edit-category-icon" placeholder="fa-solid fa-diagram-next"></div>
                <div class="form-group" style="display:flex; gap:10px; align-items:center;">
                    <button type="button" class="btn-secondary" onclick="previewIcon('edit-category-icon-preview', document.getElementById('edit-category-icon').value)">Preview Icon</button>
                    <div id="edit-category-icon-preview" style="width:32px; height:32px; border-radius:8px; border:1px solid rgba(148,163,184,0.4); display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:12px;">N/A</div>
                </div>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            const tabEl = document.getElementById('tab-' + tabName);
            if (tabEl) tabEl.classList.add('active');
            document.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.querySelector(`.nav-link[data-tab="${tabName}"]`);
            if (activeBtn) activeBtn.classList.add('active');
            loadTabData(tabName);
        }

        function loadStats() {
            return fetch('?ajax=get_stats', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(stats => {
                    document.getElementById('stat-users').textContent = stats.total_users;
                    document.getElementById('stat-buyers').textContent = stats.total_buyers;
                    document.getElementById('stat-sellers').textContent = stats.total_sellers;
                    document.getElementById('stat-products').textContent = stats.total_products;
                    document.getElementById('stat-active-products').textContent = stats.active_products;
                    document.getElementById('stat-pending-products').textContent = stats.pending_products;
                    document.getElementById('stat-orders').textContent = stats.total_orders;

                    const rawRevenue = stats.total_revenue;
                    const revenueNumber = Number(rawRevenue || 0);
                    const safeRevenue = Number.isFinite(revenueNumber) ? revenueNumber : 0;
                    document.getElementById('stat-revenue').textContent = 'BDT ' + safeRevenue.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    if (typeof stats.pending_sellers !== 'undefined') {
                        const pendingEl = document.getElementById('stat-pending-sellers');
                        if (pendingEl) {
                            const pending = Number(stats.pending_sellers || 0);
                            pendingEl.textContent = pending > 0 ? (pending + ' pending approval') : '';
                        }
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        async function loadTabData(tabName) {
            switch(tabName) {
                case 'users':
                    await loadUsers();
                    break;
                case 'products':
                    await loadProducts();
                    break;
                case 'orders':
                    await loadOrders();
                    break;
                case 'categories':
                    await Promise.all([loadCategories(), loadSubcategories()]);
                    break;
                case 'banners':
                    await loadBanners();
                    break;
                case 'data':
                    await loadTableData();
                    break;
            }
        }

        let usersState = { page: 1, pageSize: 10, search: '', role: '', status: '' };
        let categoriesCache = [];
        let subcategoriesCache = [];

        async function loadUsers(page) {
            try {
                if (typeof page === 'number') usersState.page = page;
                usersState.pageSize = parseInt(document.getElementById('user-page-size').value, 10);
                usersState.search = document.getElementById('user-search').value.trim();
                usersState.role = document.getElementById('user-role-filter').value;
                usersState.status = document.getElementById('user-status-filter').value;

                const qs = new URLSearchParams({
                    ajax: 'get_users',
                    page: usersState.page,
                    pageSize: usersState.pageSize,
                    search: usersState.search,
                    role: usersState.role,
                    status: usersState.status
                }).toString();

                const response = await fetch('?' + qs, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const text = await response.text();

                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                    document.getElementById('users-loading').innerHTML = '<div style="color: #fca5a5; padding: 20px;">Session expired. <a href="admin.php">Please login again</a></div>';
                    return;
                }

                const data = JSON.parse(text);
                if (data.error && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                const users = data.items || [];
                const total = data.total || 0;
                const tbody = document.getElementById('users-tbody');
                tbody.innerHTML = '';

                if (users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 20px;">No users found</td></tr>';
                } else {
                    users.forEach(user => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${user.user_id}</td>
                            <td>${user.full_name || '-'}</td>
                            <td>${user.email}</td>
                            <td>
                                <span class="badge badge-info">${user.role_name || '-'}</span>
                                <select class="action-btn" onchange="changeUserRole(${user.user_id}, this.value)">
                                    <option value="">Change</option>
                                    <option value="buyer">Buyer</option>
                                    <option value="seller">Seller</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>
                            <td><span class="badge ${user.is_active ? 'badge-success' : 'badge-danger'}">${user.is_active ? 'Active' : 'Blocked'}</span></td>
                            <td>${new Date(user.created_at).toLocaleDateString()}</td>
                            <td>${(user.role_name === 'buyer')
                                ? user.buyer_address
                                : (user.seller_shop + (user.seller_verified ? ' (Verified)' : ' (Pending)'))}</td>
                            <td>
                                ${user.profile_image_url
                                    ? `<img src="${user.profile_image_url}" alt="Avatar" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:1px solid rgba(148,163,184,0.3);">`
                                    : '<span style="color:var(--muted)">?</span>'}
                            </td>
                            <td>
                                <button class="action-btn btn-block" onclick="updateUserStatus(${user.user_id}, ${user.is_active ? `'blocked'` : `'active'`})">
                                    ${user.is_active ? 'Block' : 'Unblock'}
                                </button>
                                <button class="action-btn btn-edit" onclick="resetUserPassword(${user.user_id})">Reset</button>
                                <button class="action-btn btn-delete" onclick="deleteUser(${user.user_id})">Delete</button>
                                ${user.profile_image_url ? `<button class="action-btn btn-delete" onclick="deleteProfileImage(${user.user_id})">Remove Photo</button>` : ''}
                                ${user.role_name === 'seller' && !user.seller_verified ? `<button class="action-btn btn-primary" onclick="approveSeller(${user.user_id})">Approve</button>` : ''}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('users-count').textContent = `Total: ${total}`;
                document.getElementById('users-page').textContent = `Page ${usersState.page}`;
                document.getElementById('users-loading').style.display = 'none';
                document.getElementById('users-content').style.display = 'block';
            } catch (error) {
                console.error('Error loading users:', error);
                document.getElementById('users-loading').innerHTML = `<div style="color:#fca5a5; padding: 20px;">Error: ${error.message}</div>`;
            }
        }

        function changeUsersPage(delta) {
            const current = usersState.page;
            const next = Math.max(1, current + delta);
            loadUsers(next);
        }

        async function loadProducts() {
            try {
                const searchInput = document.getElementById('product-search');
                const searchValue = searchInput ? searchInput.value.trim() : '';
                const qs = new URLSearchParams({ ajax: 'get_products', search: searchValue }).toString();
                const response = await fetch('?' + qs, { credentials: 'same-origin' });
                const products = await response.json();

                const tbody = document.getElementById('products-tbody');
                tbody.innerHTML = '';

                const basePath = window.location.pathname.split('/').filter(Boolean)[0] || '';
                const baseUrl = basePath ? `/${basePath}` : '';
                const resolveImageUrl = (value) => {
                    const raw = (value || '').toString().trim();
                    if (!raw) return '';
                    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('//')) {
                        return raw;
                    }
                    if (raw.startsWith('/')) {
                        return raw;
                    }
                    return `${baseUrl}/${raw.replace(/^\/+/, '')}`;
                };

                products.forEach(product => {
                    const tr = document.createElement('tr');
                    const statusBadge = product.status === 'active' ? 'badge-success' :
                                      product.status === 'pending' ? 'badge-warning' : 'badge-danger';
                    const imageUrl = resolveImageUrl(product.image_url);
                    const imageCell = imageUrl ? `
                        <div style="display:flex; align-items:center; gap:8px;">
                            <a href="${imageUrl}" target="_blank" rel="noopener">
                                <img src="${imageUrl}" alt="${product.name}" style="width:44px; height:44px; object-fit:cover; border-radius:8px; border:1px solid rgba(148,163,184,0.3);">
                            </a>
                        </div>
                    ` : '<span style="color:var(--muted);">No image</span>';
                    tr.innerHTML = `
                        <td>${product.product_id}</td>
                        <td>${imageCell}</td>
                        <td>${product.name}</td>
                        <td>${product.category}</td>
                        <td>${product.subcategory || '-'}</td>
                        <td>BDT ${parseFloat(product.price).toLocaleString()}</td>
                        <td>${product.stock}</td>
                        <td><span class="badge ${statusBadge}">${product.status}</span></td>
                        <td>${product.seller_email}</td>
                        <td>
                            ${product.status === 'pending' ?
                                `<button class="action-btn btn-approve" onclick="updateProductStatus(${product.product_id}, 'active')"><i class="fa-solid fa-thumbs-up"></i> Approve</button>` :
                                `<button class="action-btn btn-block" onclick="updateProductStatus(${product.product_id}, 'inactive')">Deactivate</button>`
                            }
                            ${imageUrl ? `<button class="action-btn btn-edit" onclick="deleteProductImage(${product.product_id})">Remove Image</button>` : ''}
                            <button class="action-btn btn-delete" onclick="deleteProduct(${product.product_id})">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('products-loading').style.display = 'none';
                document.getElementById('products-content').style.display = 'block';
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        async function loadOrders() {
            try {
                const response = await fetch('?ajax=get_orders', { credentials: 'same-origin' });
                const orders = await response.json();

                const tbody = document.getElementById('orders-tbody');
                tbody.innerHTML = '';

                if (orders.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">No orders yet</td></tr>';
                } else {
                    orders.forEach(order => {
                        const tr = document.createElement('tr');
                        const statusBadge = order.status === 'completed' ? 'badge-success' :
                                          order.status === 'pending' ? 'badge-warning' : 'badge-info';
                        tr.innerHTML = `
                            <td>#${order.order_id}</td>
                            <td>${order.buyer_email}</td>
                            <td>BDT ${parseFloat(order.total_amount).toLocaleString()}</td>
                            <td><span class="badge ${statusBadge}">${order.status}</span></td>
                            <td>${new Date(order.created_at).toLocaleString()}</td>
                            <td>
                                <select class="action-btn" onchange="updateOrderStatus(${order.order_id}, this.value)">
                                    <option value="">Change Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('orders-loading').style.display = 'none';
                document.getElementById('orders-content').style.display = 'block';
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        }

        async function loadCategories() {
            try {
                const response = await fetch('?ajax=get_categories', { credentials: 'same-origin' });
                const categories = await response.json();
                categoriesCache = Array.isArray(categories) ? categories : [];

                const tbody = document.getElementById('categories-tbody');
                tbody.innerHTML = '';

                categories.forEach(category => {
                    const tr = document.createElement('tr');
                    tr.classList.add('category-row');
                    tr.setAttribute('data-category-id', category.category_id);
                    tr.setAttribute('data-name', (category.name || '').toLowerCase());
                    tr.innerHTML = `
                        <td>${category.category_id}</td>
                        <td>${renderIconCell(category.icon_class, category.icon_url)}</td>
                        <td>
                            <span>${category.name}</span>
                            <button class="category-toggle-btn" type="button" title="Show subcategories" style="margin-left:8px;"><i class="fa-solid fa-chevron-right"></i></button>
                        </td>
                        <td>
                            <button class="action-btn btn-edit" onclick="openCategoryEdit(${category.category_id})">Edit</button>
                            <button class="action-btn btn-delete" onclick="deleteCategory(${category.category_id})">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);

                    // detail row for inline subcategories
                    const subRow = document.createElement('tr');
                    subRow.classList.add('category-subcats-row');
                    subRow.setAttribute('data-category-id', category.category_id);
                    subRow.style.display = 'none';
                    subRow.innerHTML = `
                        <td colspan="4">
                            <div class="subcategory-inline" data-category-id="${category.category_id}"></div>
                        </td>
                    `;
                    tbody.appendChild(subRow);
                });

                document.getElementById('categories-loading').style.display = 'none';
                document.getElementById('categories-content').style.display = 'block';
                applyCategoryFilters();
                populateSubcategoryCategorySelect();
                refreshInlineSubcategoriesView();
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        async function loadSubcategories() {
            try {
                const loadingEl = document.getElementById('subcategories-loading');
                const contentEl = document.getElementById('subcategories-content');
                if (loadingEl) loadingEl.style.display = 'block';
                if (contentEl) contentEl.style.display = 'none';

                const response = await fetch('?ajax=get_subcategories', { credentials: 'same-origin' });
                const subcategories = await response.json();
                subcategoriesCache = Array.isArray(subcategories) ? subcategories : [];

                const tbody = document.getElementById('subcategories-tbody');
                if (tbody) {
                    tbody.innerHTML = '';
                    if (!subcategoriesCache.length) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:16px;">No subcategories found</td></tr>';
                    } else {
                        subcategoriesCache.forEach(sub => {
                            const createdLabel = sub.created_at ? new Date(sub.created_at).toLocaleDateString() : '';
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${sub.subcategory_id}</td>
                                <td>${renderIconCell(sub.icon_class, sub.icon_url)}</td>
                                <td>${sub.name}</td>
                                <td>${sub.category_name}</td>
                                <td>${createdLabel}</td>
                                <td>
                                    <button class="action-btn btn-delete" onclick="deleteSubcategory(${sub.subcategory_id})">Delete</button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    }
                }

                refreshInlineSubcategoriesView();
                if (loadingEl) loadingEl.style.display = 'none';
                if (contentEl) contentEl.style.display = 'block';
            } catch (error) {
                console.error('Error loading subcategories:', error);
            }
        }
        function refreshInlineSubcategoriesView() {
            const detailRows = document.querySelectorAll('#categories-tbody tr.category-subcats-row');
            detailRows.forEach(row => {
                if (row.style.display === 'none') return;
                const catId = row.getAttribute('data-category-id');
                if (!catId) return;
                const container = row.querySelector('.subcategory-inline');
                if (container) {
                    container.innerHTML = buildInlineSubcategoryHTML(catId);
                }
            });
        }

        function buildInlineSubcategoryHTML(categoryId) {
            const items = (subcategoriesCache || []).filter(s => String(s.category_id) === String(categoryId));
            if (items.length === 0) {
                return `<div style="color:var(--muted); font-size:12px; padding:6px 0;">No subcategories yet.</div>`;
            }
            const rows = items.map(sub => {
                const createdLabel = sub.created_at ? new Date(sub.created_at).toLocaleDateString() : '';
                return `
                    <tr>
                        <td>${renderIconCell(sub.icon_class, sub.icon_url)}</td>
                        <td>${sub.name}</td>
                        <td>${sub.category_name}</td>
                        <td>${createdLabel}</td>
                        <td>
                            <button class="action-btn btn-delete" onclick="deleteSubcategory(${sub.subcategory_id})">Delete</button>
                        </td>
                    </tr>
                `;
            }).join('');
            return `
                <table class="inline-subcategory-table">
                    <thead>
                        <tr><th>Icon</th><th>Name</th><th>Category</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            `;
        }

        function renderIconCell(iconClass, iconUrl) {
            const cls = (iconClass || '').toString().trim();
            const url = (iconUrl || '').toString().trim();
            if (cls) {
                return `<i class="${cls}" style="font-size:18px; color: var(--accent);"></i>`;
            }
            if (!url) {
                return '<span class="badge badge-secondary">None</span>';
            }
            return `<img src="${url}" alt="icon" style="width:26px; height:26px; object-fit:cover; border-radius:6px; border:1px solid rgba(148,163,184,0.4);">`;
        }

        function normalizeIconClass(value) {
            const raw = (value || '').toString().trim();
            if (raw === '') return '';
            if (raw.includes('<i') && raw.includes('class=')) {
                const match = raw.match(/class\s*=\s*["']([^"']+)["']/i);
                return match ? match[1].trim() : raw;
            }
            return raw;
        }

        function previewIcon(targetId, iconClass) {
            const target = document.getElementById(targetId);
            if (!target) return;
            const clean = normalizeIconClass(iconClass);
            if (!clean) {
                target.innerHTML = 'N/A';
                return;
            }
            target.innerHTML = `<i class="${clean}" style="font-size:20px; color: var(--accent);"></i>`;
        }

        function openCategoryEdit(categoryId) {
            const category = categoriesCache.find(c => String(c.category_id) === String(categoryId));
            if (!category) return;
            document.getElementById('edit-category-id').value = category.category_id;
            document.getElementById('edit-category-name').value = category.name || '';
            document.getElementById('edit-category-icon').value = category.icon_class || '';
            previewIcon('edit-category-icon-preview', category.icon_class || '');
            document.getElementById('edit-category-modal').classList.add('show');
        }

        function populateSubcategoryCategorySelect() {
            const select = document.getElementById('subcategory-category');
            if (!select) return;
            select.innerHTML = '';
            if (!categoriesCache.length) {
                select.innerHTML = '<option value="">No categories available</option>';
                return;
            }
            select.innerHTML = '<option value="">Select category</option>';
            categoriesCache.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.category_id;
                option.textContent = cat.name;
                select.appendChild(option);
            });
        }

        function applyCategoryFilters() {
            const searchInput = document.getElementById('category-search');
            const term = ((searchInput && searchInput.value) ? searchInput.value : '').toLowerCase().trim();

            const categoryRows = document.querySelectorAll('#categories-tbody tr.category-row');
            categoryRows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const matchesSearch = term === '' || name.includes(term);
                row.style.display = matchesSearch ? '' : 'none';

                // hide matching detail row when category is hidden
                const catId = row.getAttribute('data-category-id') || '';
                const detailRow = document.querySelector(`#categories-tbody tr.category-subcats-row[data-category-id="${catId}"]`);
                if (detailRow && !matchesSearch) {
                    detailRow.style.display = 'none';
                }
            });
        }

        
        async function deleteProfileImage(user_id) {
            if (!confirm('Remove this profile image?')) return;
            const form = new URLSearchParams();
            form.append('ajax', 'delete_profile_image');
            form.append('user_id', user_id);
            const res = await fetch('', { method: 'POST', body: form });
            const result = await res.json();
            if (result.success) {
                await loadUsers(usersState.page);
                await loadStats();
            } else {
                alert(result.error || 'Failed to remove profile image');
            }
        }

async function updateUserStatus(userId, status) {
            const reason = prompt(status === 'blocked' ? 'Reason for blocking (optional):' : 'Reason for unblocking (optional):', '');
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('status', status);
                formData.append('reason', reason || '');

                const response = await fetch('?ajax=update_user_status', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) { await loadUsers(usersState.page); await loadStats(); }
            } catch (error) {
                console.error('Error updating user:', error);
                alert('Error updating user status');
            }
        }

        async function approveSeller(userId) {
            if (!confirm('Approve this seller account? They will become a verified seller.')) return;
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                const response = await fetch('?ajax=approve_seller', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const result = await response.json();
                if (result.success) {
                    await loadUsers(usersState.page);
                    await loadStats();
                } else {
                    alert('Error: ' + (result.error || 'Failed to approve seller'));
                }
            } catch (error) {
                console.error('Error approving seller:', error);
                alert('Error approving seller');
            }
        }

        async function changeUserRole(userId, role) {
            if (!role) return;
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('role', role);
                const response = await fetch('?ajax=update_user_role', { method: 'POST', body: formData, credentials: 'same-origin' });
                const result = await response.json();
                if (result.success) {
                    await loadUsers(usersState.page);
                    await loadStats();
                } else {
                    alert('Error: ' + (result.error || 'Failed to change role'));
                }
            } catch (error) {
                console.error('Error changing role:', error);
                alert('Error changing role: ' + error.message);
            }
        }

        async function resetUserPassword(userId) {
            if (!confirm('Reset password for this user? A temporary password will be generated.')) return;
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                const response = await fetch('?ajax=reset_user_password', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Temporary password: ' + result.temp_password);
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                alert('Error resetting password');
            }
        }

        async function loadBanners() {
            try {
                const resp = await fetch('?ajax=get_banners', { credentials: 'same-origin' });
                const banners = await resp.json();
                const tbody = document.getElementById('banners-tbody');
                tbody.innerHTML = '';
                banners.forEach(b => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${b.banner_id}</td>
                        <td>${b.title}</td>
                        <td>${b.subtitle || ''}</td>
                        <td><a href="${b.image_url}" target="_blank" rel="noopener">Image</a></td>
                        <td><a href="${b.link_url}" target="_blank" rel="noopener">${b.link_url}</a></td>
                        <td>${b.position}</td>
                        <td><span class="badge ${b.is_active ? 'badge-success' : 'badge-danger'}">${b.is_active ? 'Active' : 'Inactive'}</span></td>
                        <td>${new Date(b.created_at).toLocaleString()}</td>
                        <td>
                            <button class="action-btn btn-approve" onclick="toggleBannerActive(${b.banner_id}, ${b.is_active ? 0 : 1})">${b.is_active ? 'Deactivate' : 'Activate'}</button>
                            <button class="action-btn btn-delete" onclick="deleteBanner(${b.banner_id})">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                document.getElementById('banners-loading').style.display = 'none';
                document.getElementById('banners-content').style.display = 'block';
            } catch (err) {
                console.error('Error loading banners', err);
            }
        }

        async function toggleBannerActive(id, val) {
            try {
                const fd = new FormData();
                fd.append('banner_id', id);
                fd.append('is_active', val);
                const resp = await fetch('?ajax=update_banner_active', { method: 'POST', body: fd });
                const r = await resp.json();
                if (r.success) loadBanners();
            } catch (err) {
                console.error('Error toggling banner', err);
            }
        }

        async function deleteBanner(id) {
            if (!confirm('Delete this banner?')) return;
            try {
                const fd = new FormData();
                fd.append('banner_id', id);
                const resp = await fetch('?ajax=delete_banner', { method: 'POST', body: fd });
                const r = await resp.json();
                if (r.success) loadBanners();
            } catch (err) {
                console.error('Error deleting banner', err);
            }
        }

        let dataState = { page: 1, pageSize: 10 };
        async function loadTableData(page) {
            if (typeof page === 'number') dataState.page = page;
            dataState.pageSize = parseInt(document.getElementById('data-page-size').value, 10);
            const table = document.getElementById('data-table-select').value;
            document.getElementById('data-loading').style.display = 'block';
            document.getElementById('data-content').style.display = 'none';
            try {
                const qs = new URLSearchParams({ ajax: 'get_table_data', table, page: dataState.page, pageSize: dataState.pageSize });
                const resp = await fetch('?' + qs.toString());
                const r = await resp.json();
                if (!r.success) { alert(r.error || 'Failed'); return; }
                const items = r.items || [];
                const total = r.total || 0;

                const thead = document.getElementById('data-thead');
                const tbody = document.getElementById('data-tbody');
                thead.innerHTML = '';
                tbody.innerHTML = '';

                if (items.length === 0) {
                    thead.innerHTML = '<tr><th>No data</th></tr>';
                } else {
                    const cols = Object.keys(items[0]);
                    thead.innerHTML = '<tr>' + cols.map(c => `<th>${c}</th>`).join('') + '</tr>';
                    items.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = cols.map(c => `<td>${row[c] ?? ''}</td>`).join('');
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('data-count').textContent = `Total: ${total}`;
                document.getElementById('data-page').textContent = `Page ${dataState.page}`;
                document.getElementById('data-loading').style.display = 'none';
                document.getElementById('data-content').style.display = 'block';
            } catch (err) {
                console.error('Error loading table', err);
                alert('Error loading data');
            }
        }

        function changeDataPage(delta) {
            const next = Math.max(1, dataState.page + delta);
            loadTableData(next);
        }

        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to DELETE this user? This action cannot be undone!')) return;
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                const response = await fetch('?ajax=delete_user', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadUsers();
                    await loadStats();
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                alert('Error deleting user');
            }
        }

        async function updateProductStatus(productId, status) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('status', status);
                const response = await fetch('?ajax=update_product_status', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadProducts();
                    await loadStats();
                }
            } catch (error) {
                console.error('Error updating product:', error);
                alert('Error updating product status');
            }
        }

        async function deleteProduct(productId) {
            if (!confirm('Are you sure you want to DELETE this product?')) return;
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                const response = await fetch('?ajax=delete_product', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadProducts();
                    await loadStats();
                } else {
                    alert('Error: ' + (result.error || 'Failed to delete product'));
                }
            } catch (error) {
                console.error('Error deleting product:', error);
                alert('Error deleting product');
            }
        }

        async function deleteProductImage(productId) {
            if (!confirm('Remove this product image from Supabase?')) return;
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                const response = await fetch('?ajax=delete_product_image', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadProducts();
                    if (result.warning) {
                        alert(result.warning);
                    }
                } else {
                    alert('Error: ' + (result.error || 'Failed to delete image'));
                }
            } catch (error) {
                console.error('Error deleting product image:', error);
                alert('Error deleting product image');
            }
        }

        async function updateOrderStatus(orderId, status) {
            if (!status) return;
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('status', status);
                const response = await fetch('?ajax=update_order_status', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadOrders();
                    await loadStats();
                }
            } catch (error) {
                console.error('Error updating order:', error);
                alert('Error updating order status');
            }
        }

        async function deleteCategory(categoryId) {
            if (!confirm('Delete this category? Products in this category will also be affected!')) return;
            try {
                const formData = new FormData();
                formData.append('category_id', categoryId);
                const response = await fetch('?ajax=delete_category', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadCategories();
                }
            } catch (error) {
                console.error('Error deleting category:', error);
                alert('Error deleting category');
            }
        }

        async function deleteSubcategory(subcategoryId) {
            if (!confirm('Delete this subcategory? Products in this subcategory may be affected.')) return;
            try {
                const formData = new FormData();
                formData.append('subcategory_id', subcategoryId);
                const response = await fetch('?ajax=delete_subcategory', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await loadSubcategories();
                } else {
                    alert('Error: ' + (result.error || 'Failed to delete subcategory'));
                }
            } catch (error) {
                console.error('Error deleting subcategory:', error);
                alert('Error deleting subcategory');
            }
        }

        function toggleCategorySubcategories(categoryId, btn) {
            const detailRow = document.querySelector(`#categories-tbody tr.category-subcats-row[data-category-id="${categoryId}"]`);
            if (!detailRow) return;
            const container = detailRow.querySelector('.subcategory-inline');

            const isHidden = detailRow.style.display === 'none' || detailRow.style.display === '';

            if (isHidden) {
                if (container) {
                    container.innerHTML = buildInlineSubcategoryHTML(categoryId);
                }
                detailRow.style.display = 'table-row';
                if (btn) btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';
            } else {
                detailRow.style.display = 'none';
                if (btn) btn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
            }
        }

        function showAddCategoryModal() {
            document.getElementById('add-category-modal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        document.getElementById('add-category-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const formData = new FormData(e.target);
                formData.append('name', formData.get('category_name'));
                formData.append('icon_class', normalizeIconClass(formData.get('category_icon') || ''));
                const response = await fetch('?ajax=add_category', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    closeModal('add-category-modal');
                    e.target.reset();
                    await loadCategories();
                }
            } catch (error) {
                console.error('Error adding category:', error);
                alert('Error adding category');
            }
        });

        const categorySearch = document.getElementById('category-search');
        if (categorySearch) {
            categorySearch.addEventListener('input', applyCategoryFilters);
        }

        const addSubcategoryForm = document.getElementById('add-subcategory-form');
        if (addSubcategoryForm) {
            addSubcategoryForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const categoryId = document.getElementById('subcategory-category').value;
                    const name = document.getElementById('subcategory-name').value.trim();
                    const iconClass = normalizeIconClass(document.getElementById('subcategory-icon').value);

                    if (!categoryId || !name) {
                        alert('Please select a category and enter a subcategory name.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('category_id', categoryId);
                    formData.append('name', name);
                    formData.append('icon_class', iconClass);

                    const response = await fetch('?ajax=add_subcategory', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        addSubcategoryForm.reset();
                        await loadSubcategories();
                    } else {
                        alert('Error: ' + (result.error || 'Failed to add subcategory'));
                    }
                } catch (error) {
                    console.error('Error adding subcategory:', error);
                    alert('Error adding subcategory');
                }
            });
        }

        document.getElementById('edit-category-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const formData = new FormData();
                formData.append('category_id', document.getElementById('edit-category-id').value);
                formData.append('name', document.getElementById('edit-category-name').value.trim());
                formData.append('icon_class', normalizeIconClass(document.getElementById('edit-category-icon').value));
                const response = await fetch('?ajax=update_category', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    closeModal('edit-category-modal');
                    await loadCategories();
                } else {
                    alert('Error: ' + (result.error || 'Failed to update category'));
                }
            } catch (error) {
                console.error('Error updating category:', error);
                alert('Error updating category');
            }
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        };

        const collapseBtn = document.getElementById('collapseBtn');
        const sidebar = document.getElementById('sidebar');
        if (collapseBtn && sidebar) {
            collapseBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }

        const profileToggle = document.getElementById('profileToggle');
        const profileMenu = document.getElementById('profileMenu');
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.style.display = profileMenu.style.display === 'flex' ? 'none' : 'flex';
            });
            document.addEventListener('click', () => {
                profileMenu.style.display = 'none';
            });
        }

        const sellerNotificationsBtn = document.getElementById('sellerNotificationsBtn');
        const sellerNotificationsModal = document.getElementById('seller-notification-modal');
        if (sellerNotificationsBtn && sellerNotificationsModal) {
            sellerNotificationsBtn.addEventListener('click', () => {
                sellerNotificationsModal.classList.add('show');
            });
        }

        const productSearchInput = document.getElementById('product-search');
        if (productSearchInput) {
            productSearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadProducts();
                }
            });
        }

        const deliverySelectAll = document.getElementById('deliverySelectAll');
        const deliveryChecks = Array.from(document.querySelectorAll('.delivery-select'));
        const deliveryInputs = document.getElementById('deliverySelectedInputs');

        function syncDeliveryInputs() {
            if (!deliveryInputs) return;
            const selected = deliveryChecks.filter(chk => chk.checked).map(chk => chk.value);
            deliveryInputs.innerHTML = selected
                .map(id => `<input type="hidden" name="notification_ids[]" value="${id}">`)
                .join('');
        }

        if (deliverySelectAll && deliveryChecks.length) {
            deliverySelectAll.addEventListener('change', () => {
                deliveryChecks.forEach(chk => { chk.checked = deliverySelectAll.checked; });
                syncDeliveryInputs();
            });
            deliveryChecks.forEach(chk => {
                chk.addEventListener('change', () => {
                    if (!chk.checked && deliverySelectAll.checked) {
                        deliverySelectAll.checked = false;
                    } else if (deliveryChecks.every(c => c.checked)) {
                        deliverySelectAll.checked = true;
                    }
                    syncDeliveryInputs();
                });
            });
            syncDeliveryInputs();
        }

        // Delegate clicks on category arrow buttons to toggle inline subcategories
        document.addEventListener('click', (event) => {
            const btn = event.target.closest('.category-toggle-btn');
            if (!btn) return;
            const row = btn.closest('tr.category-row');
            if (!row) return;
            const categoryId = row.getAttribute('data-category-id');
            if (!categoryId) return;
            toggleCategorySubcategories(categoryId, btn);
        });

        window.addEventListener('load', () => {
            document.body.classList.add('loaded');
        });

        loadStats();
        loadUsers();
    </script>
</body>
</html>

