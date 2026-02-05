<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/coupons.php';
require_once __DIR__ . '/../includes/notifications.php';

if (empty($_SESSION['admin_logged_in'])) {
    require_role('admin');
}
ensure_coupon_tables();
ensure_notifications_table();

$action = $_POST['action'] ?? '';
if ($action === '') {
    header("Location: " . BASE_URL . "/admin_folder/admin_coupons.php");
    exit;
}

function redirect_with(string $status, string $message): void {
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    header("Location: {$base}/admin_folder/admin_coupons.php?{$status}=" . urlencode($message));
    exit;
}

try {
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $minPurchase = (float)($_POST['min_purchase'] ?? 0);
        $maxDiscount = $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
        $price = (float)($_POST['price'] ?? 0);
        $usageLimit = max(1, (int)($_POST['usage_limit'] ?? 1));
        $startsAt = $_POST['starts_at'] !== '' ? $_POST['starts_at'] : null;
        $endsAt = $_POST['ends_at'] !== '' ? $_POST['ends_at'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $publishNow = isset($_POST['publish_now']) ? 1 : 0;

        if ($code === '' || $discountValue <= 0 || $price < 0) {
            redirect_with('err', 'Please provide valid coupon details.');
        }

        $existing = db_fetch("SELECT coupon_id FROM coupons WHERE code = ?", [$code]);
        if ($existing) {
            redirect_with('err', 'Coupon code already exists.');
        }

        $adminId = get_admin_user_id();
        db_execute(
            "INSERT INTO coupons (code, title, description, discount_type, discount_value, min_purchase, max_discount, price, usage_limit, starts_at, ends_at, is_active, is_published, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $code,
                $title !== '' ? $title : null,
                $description !== '' ? $description : null,
                $discountType,
                $discountValue,
                $minPurchase,
                $maxDiscount,
                $price,
                $usageLimit,
                $startsAt,
                $endsAt,
                $isActive,
                $publishNow,
                $adminId
            ]
        );

        if ($publishNow) {
            $coupon = db_fetch("SELECT coupon_id FROM coupons WHERE code = ?", [$code]);
            if ($coupon) {
                db_query(
                    "INSERT IGNORE INTO coupon_seller_requests (coupon_id, seller_id, status)
                     SELECT ?, u.user_id, 'pending'
                     FROM users u
                     INNER JOIN roles r ON r.role_id = u.role_id
                     WHERE r.role_name = 'seller' AND u.status = 'active'",
                    [(int)$coupon['coupon_id']]
                );
                db_query(
                    "INSERT INTO notifications (user_id, title, message, type, created_at)
                     SELECT u.user_id, 'New coupon approval', CONCAT('Coupon ', ?, ' needs your approval.'), 'coupon', NOW()
                     FROM users u
                     INNER JOIN roles r ON r.role_id = u.role_id
                     WHERE r.role_name = 'seller' AND u.status = 'active'",
                    [$code]
                );
            }
        }

        redirect_with('msg', 'Coupon created successfully.');
    }

    if ($action === 'publish') {
        $couponId = (int)($_POST['coupon_id'] ?? 0);
        if ($couponId <= 0) {
            redirect_with('err', 'Invalid coupon.');
        }
        db_execute("UPDATE coupons SET is_published = 1, is_active = 1 WHERE coupon_id = ?", [$couponId]);
        $couponRow = db_fetch("SELECT code FROM coupons WHERE coupon_id = ?", [$couponId]);
        $couponCode = $couponRow['code'] ?? '';
        db_query(
            "INSERT IGNORE INTO coupon_seller_requests (coupon_id, seller_id, status)
             SELECT ?, u.user_id, 'pending'
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE r.role_name = 'seller' AND u.status = 'active'",
            [$couponId]
        );
        if ($couponCode !== '') {
            db_query(
                "INSERT INTO notifications (user_id, title, message, type, created_at)
                 SELECT u.user_id, 'New coupon approval', CONCAT('Coupon ', ?, ' needs your approval.'), 'coupon', NOW()
                 FROM users u
                 INNER JOIN roles r ON r.role_id = u.role_id
                 WHERE r.role_name = 'seller' AND u.status = 'active'",
                [$couponCode]
            );
        }
        redirect_with('msg', 'Coupon published and sellers notified.');
    }

    if ($action === 'unpublish') {
        $couponId = (int)($_POST['coupon_id'] ?? 0);
        if ($couponId <= 0) {
            redirect_with('err', 'Invalid coupon.');
        }
        db_execute("UPDATE coupons SET is_published = 0 WHERE coupon_id = ?", [$couponId]);
        redirect_with('msg', 'Coupon unpublished.');
    }

    if ($action === 'delete') {
        $couponId = (int)($_POST['coupon_id'] ?? 0);
        if ($couponId <= 0) {
            redirect_with('err', 'Invalid coupon.');
        }
        db_execute("DELETE FROM order_coupons WHERE coupon_id = ?", [$couponId]);
        db_execute("DELETE FROM cart_coupons WHERE coupon_id = ?", [$couponId]);
        db_execute("DELETE FROM coupon_product_allow WHERE coupon_id = ?", [$couponId]);
        db_execute("DELETE FROM coupon_seller_requests WHERE coupon_id = ?", [$couponId]);
        db_execute("DELETE FROM coupon_purchases WHERE coupon_id = ?", [$couponId]);
        db_execute("DELETE FROM coupons WHERE coupon_id = ?", [$couponId]);
        redirect_with('msg', 'Coupon deleted.');
    }

    if ($action === 'delete_response') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            redirect_with('err', 'Invalid response.');
        }
        db_execute("DELETE FROM coupon_seller_requests WHERE request_id = ?", [$requestId]);
        redirect_with('msg', 'Response removed.');
    }

    redirect_with('err', 'Unknown action.');
} catch (Throwable $e) {
    redirect_with('err', $e->getMessage());
}
