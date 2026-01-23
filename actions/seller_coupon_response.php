<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/coupons.php';

require_role('seller');
ensure_coupon_tables();

$sellerId = get_user_id();
$action = $_POST['action'] ?? '';
$couponId = (int)($_POST['coupon_id'] ?? 0);
$note = trim($_POST['response_note'] ?? '');

if ($sellerId === null || $couponId <= 0 || $action === '') {
    header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=invalid");
    exit;
}

$request = db_fetch(
    "SELECT request_id FROM coupon_seller_requests WHERE coupon_id = ? AND seller_id = ?",
    [$couponId, $sellerId]
);

if (!$request) {
    header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=request_not_found");
    exit;
}

try {
    if ($action === 'approve') {
        $productIds = $_POST['product_ids'] ?? [];
        if (!is_array($productIds) || empty($productIds)) {
            header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=select_products");
            exit;
        }

        $cleanIds = array_map('intval', $productIds);
        $cleanIds = array_filter($cleanIds, fn($id) => $id > 0);
        if (empty($cleanIds)) {
            header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=select_products");
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $params = $cleanIds;
        $params[] = $sellerId;
        $owned = db_fetch_all(
            "SELECT product_id FROM products WHERE product_id IN ($placeholders) AND seller_id = ?",
            $params
        );
        $ownedIds = array_map(fn($row) => (int)$row['product_id'], $owned);

        if (count($ownedIds) !== count($cleanIds)) {
            header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=invalid_products");
            exit;
        }

        db_execute(
            "UPDATE coupon_seller_requests
             SET status = 'approved', response_note = ?, responded_at = NOW()
             WHERE coupon_id = ? AND seller_id = ?",
            [$note, $couponId, $sellerId]
        );

        db_execute(
            "DELETE FROM coupon_product_allow WHERE coupon_id = ? AND seller_id = ?",
            [$couponId, $sellerId]
        );

        foreach ($ownedIds as $productId) {
            db_execute(
                "INSERT INTO coupon_product_allow (coupon_id, seller_id, product_id) VALUES (?, ?, ?)",
                [$couponId, $sellerId, $productId]
            );
        }

        header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?msg=approved");
        exit;
    }

    if ($action === 'decline') {
        db_execute(
            "UPDATE coupon_seller_requests
             SET status = 'declined', response_note = ?, responded_at = NOW()
             WHERE coupon_id = ? AND seller_id = ?",
            [$note, $couponId, $sellerId]
        );
        db_execute(
            "DELETE FROM coupon_product_allow WHERE coupon_id = ? AND seller_id = ?",
            [$couponId, $sellerId]
        );
        header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?msg=declined");
        exit;
    }

    header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=invalid_action");
} catch (Throwable $e) {
    header("Location: " . BASE_URL . "/seller_dashboard/coupons.php?err=" . urlencode($e->getMessage()));
}
exit;
