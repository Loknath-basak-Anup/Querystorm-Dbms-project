<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/coupons.php';

require_role('buyer');
ensure_coupon_tables();

$buyerId = get_user_id() ?? 0;
$couponId = (int)($_POST['coupon_id'] ?? 0);

try {
    db_query(
        "CREATE TABLE IF NOT EXISTS wallet_transactions (
            txn_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            txn_type VARCHAR(30) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            note VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (txn_id),
            KEY idx_wallet_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
} catch (Throwable $e) {
    // Continue; wallet check will fail if table is missing
}

if ($buyerId <= 0 || $couponId <= 0) {
    header("Location: " . BASE_URL . "/coupon_store.php?err=invalid");
    exit;
}

function get_wallet_balance_simple(int $userId): float {
    $row = db_fetch(
        "SELECT COALESCE(SUM(
            CASE
                WHEN txn_type IN ('credit','deposit','topup','refund') THEN amount
                WHEN txn_type IN ('debit','purchase','withdraw') THEN -amount
                ELSE amount
            END
        ), 0) AS balance
         FROM wallet_transactions
         WHERE user_id = ?",
        [$userId]
    );
    $balance = (float)($row['balance'] ?? 0);
    return $balance < 0 ? 0 : $balance;
}

function generate_invoice_file(int $purchaseId, array $coupon, float $price, string $buyerName): string {
    $dir = __DIR__ . '/../uploads/invoices';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $baseName = 'coupon_invoice_' . $purchaseId;

    $filePath = $dir . '/' . $baseName . '.pdf';
    $lines = [
        'QuickMart Coupon Invoice',
        'Invoice ID: ' . $purchaseId,
        'Buyer: ' . $buyerName,
        'Coupon Code: ' . $coupon['code'],
        'Discount: ' . $coupon['discount_value'] . ' (' . $coupon['discount_type'] . ')',
        'Min Purchase: ' . number_format((float)$coupon['min_purchase'], 2) . ' BDT',
        'Price Paid: ' . number_format($price, 2) . ' BDT',
        'Generated at: ' . date('Y-m-d H:i:s')
    ];
    $content = "BT\n/F1 14 Tf\n50 770 Td\n";
    foreach ($lines as $index => $line) {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
        if ($index > 0) {
            $content .= "0 -22 Td\n";
        }
        $content .= "({$escaped}) Tj\n";
    }
    $content .= "ET\n";
    $stream = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
    $pdf = "%PDF-1.4\n";
    $offsets = [];
    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj";
    $objects[] = "4 0 obj\n{$stream}\nendobj";
    $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";
    $offset = strlen($pdf);
    foreach ($objects as $obj) {
        $offsets[] = $offset;
        $pdf .= $obj . "\n";
        $offset = strlen($pdf);
    }
    $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $off) {
        $xref .= str_pad((string)$off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= $xref;
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $offset . "\n%%EOF";
    file_put_contents($filePath, $pdf);
    return $filePath;
}

try {
    $coupon = db_fetch(
        "SELECT * FROM coupons
         WHERE coupon_id = ? AND is_active = 1 AND is_published = 1
           AND (starts_at IS NULL OR starts_at <= NOW())
           AND (ends_at IS NULL OR ends_at >= NOW())",
        [$couponId]
    );
    if (!$coupon) {
        header("Location: " . BASE_URL . "/coupon_store.php?err=not_found");
        exit;
    }

    $price = (float)$coupon['price'];
    $balance = get_wallet_balance_simple($buyerId);
    if ($balance < $price) {
        header("Location: " . BASE_URL . "/coupon_store.php?err=insufficient_balance");
        exit;
    }

    $usageLimit = max(1, (int)($coupon['usage_limit'] ?? 1));
    $purchaseId = db_execute(
        "INSERT INTO coupon_purchases (coupon_id, buyer_id, price, status, uses_left, created_at)
         VALUES (?, ?, ?, 'paid', ?, NOW())",
        [$couponId, $buyerId, $price, $usageLimit]
    );

    $token = bin2hex(random_bytes(16));
    $buyerRow = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$buyerId]);
    $buyerName = (string)($buyerRow['full_name'] ?? 'Buyer');
    $invoicePath = generate_invoice_file((int)$purchaseId, $coupon, $price, $buyerName);

    db_execute(
        "UPDATE coupon_purchases
         SET invoice_path = ?, download_token = ?
         WHERE purchase_id = ?",
        [$invoicePath, $token, $purchaseId]
    );

    db_execute(
        "INSERT INTO wallet_transactions (user_id, txn_type, amount, note)
         VALUES (?, 'purchase', ?, ?)",
        [$buyerId, $price, 'Coupon purchase #' . $purchaseId]
    );

    $adminId = get_admin_user_id();
    if ($adminId) {
        db_execute(
            "INSERT INTO wallet_transactions (user_id, txn_type, amount, note)
             VALUES (?, 'credit', ?, ?)",
            [$adminId, $price, 'Coupon sold #' . $purchaseId]
        );
    }

    header("Location: " . BASE_URL . "/coupon_store.php?msg=purchased");
    exit;
} catch (Throwable $e) {
    header("Location: " . BASE_URL . "/coupon_store.php?err=" . urlencode($e->getMessage()));
    exit;
}
