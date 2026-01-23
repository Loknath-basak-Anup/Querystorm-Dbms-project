<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
$adminBase = $base . '/admin_folder';

$adminSessionOk = !empty($_SESSION['admin_logged_in']);
if (!$adminSessionOk) {
    require_role('admin');
}

$adminRow = db_fetch(
    "SELECT u.user_id
     FROM users u
     INNER JOIN roles r ON r.role_id = u.role_id
     WHERE r.role_name = 'admin'
     ORDER BY u.user_id ASC
     LIMIT 1"
);
$adminId = (int)($adminRow['user_id'] ?? 0);

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

$couponRevenueRow = db_fetch("SELECT COALESCE(SUM(price), 0) as total FROM coupon_purchases WHERE status = 'paid'");
$deliveryOrdersRow = db_fetch("SELECT COUNT(*) AS total FROM orders WHERE status <> 'cancelled'");
$monthlyRevenueRow = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM admin_revenue_entries WHERE source_type = 'monthly_fee'");
$bannerRevenueRow = db_fetch("SELECT COALESCE(SUM(amount), 0) as total FROM admin_revenue_entries WHERE source_type = 'banner_ads'");

$deliveryRevenue = ((int)($deliveryOrdersRow['total'] ?? 0)) * 100;
$couponRevenue = (float)($couponRevenueRow['total'] ?? 0);
$monthlyRevenue = (float)($monthlyRevenueRow['total'] ?? 0);
$bannerRevenue = (float)($bannerRevenueRow['total'] ?? 0);
$totalRevenue = $couponRevenue + $deliveryRevenue + $monthlyRevenue + $bannerRevenue;

$couponRows = db_fetch_all(
    "SELECT cp.purchase_id AS ref_id, cp.price AS amount, cp.created_at,
            c.code AS label
     FROM coupon_purchases cp
     INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
     WHERE cp.status = 'paid'
     ORDER BY cp.created_at DESC
     LIMIT 10"
);

$adminEntries = db_fetch_all(
    "SELECT entry_id AS ref_id, amount, created_at, source_type, note
     FROM admin_revenue_entries
     ORDER BY created_at DESC
     LIMIT 10"
);

$deliveryRows = db_fetch_all(
    "SELECT order_id AS ref_id, created_at
     FROM orders
     WHERE status <> 'cancelled'
     ORDER BY created_at DESC
     LIMIT 10"
);

$entries = [];
foreach ($couponRows as $row) {
    $entries[] = [
        'label' => 'Coupon Sale: ' . ($row['label'] ?? ''),
        'amount' => (float)($row['amount'] ?? 0),
        'created_at' => $row['created_at'] ?? '',
        'type' => 'coupon'
    ];
}
foreach ($deliveryRows as $row) {
    $entries[] = [
        'label' => 'Delivery Charge: Order #' . (int)($row['ref_id'] ?? 0),
        'amount' => 100.00,
        'created_at' => $row['created_at'] ?? '',
        'type' => 'delivery'
    ];
}
foreach ($adminEntries as $row) {
    $label = $row['source_type'] === 'monthly_fee' ? 'Monthly Fee' : 'Banner Ads';
    if (!empty($row['note'])) {
        $label .= ' - ' . $row['note'];
    }
    $entries[] = [
        'label' => $label,
        'amount' => (float)($row['amount'] ?? 0),
        'created_at' => $row['created_at'] ?? '',
        'type' => $row['source_type'] ?? 'other'
    ];
}

usort($entries, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});
$entries = array_slice($entries, 0, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Wallet | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/wallet.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display:flex; min-height:100vh; }
        .main-content { flex:1; margin-left:0; }
        .wallet-container { grid-template-columns: 1fr 1fr; }
        .entry-pill { padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .pill-coupon { background: rgba(34,197,94,0.15); color: #22c55e; }
        .pill-delivery { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .pill-monthly { background: rgba(249,115,22,0.15); color: #f97316; }
        .pill-banner { background: rgba(168,85,247,0.15); color: #a855f7; }
        @media (max-width: 900px){ .wallet-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="dark-mode">
    <main class="main-content">
        <div style="padding:1.5rem;">
            <div style="margin-bottom: 1.5rem;">
                <a href="<?php echo htmlspecialchars($adminBase . '/admin.php'); ?>" style="color: #3b82f6; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to Admin
                </a>
            </div>
            <div class="wallet-container" style="display: grid; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="wallet-balance-card" style="background: linear-gradient(135deg, #38bdf8 0%, #6366f1 100%); border-radius: 12px; padding: 2rem; color: white; box-shadow: 0 10px 30px rgba(56, 189, 248, 0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2rem;">
                        <div>
                            <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Admin Revenue Balance</p>
                            <h1 style="margin: 0.5rem 0 0 0; font-size: 2.5rem; font-weight: 700;">BDT <?php echo number_format($totalRevenue, 2); ?></h1>
                        </div>
                        <i class="fas fa-coins" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                        <div class="entry-pill pill-coupon">Coupon Sales</div>
                        <div class="entry-pill pill-delivery">Delivery Charges</div>
                        <div class="entry-pill pill-monthly">Monthly Fees</div>
                        <div class="entry-pill pill-banner">Banner Ads</div>
                    </div>
                </div>
                <div style="background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid #334155;">
                    <h3 style="margin: 0 0 1.5rem 0; color: #e2e8f0;"><i class="fas fa-layer-group"></i> Revenue Breakdown</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#94a3b8;">Coupon Sales</span>
                            <strong style="color:#22c55e;">BDT <?php echo number_format($couponRevenue, 2); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#94a3b8;">Delivery Charges</span>
                            <strong style="color:#3b82f6;">BDT <?php echo number_format($deliveryRevenue, 2); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#94a3b8;">Seller Monthly Fees</span>
                            <strong style="color:#f97316;">BDT <?php echo number_format($monthlyRevenue, 2); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#94a3b8;">Banner Ads</span>
                            <strong style="color:#a855f7;">BDT <?php echo number_format($bannerRevenue, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background:#1e293b; border-radius:12px; padding:1.5rem; border:1px solid #334155;">
                <h3 style="margin:0 0 1rem 0; color:#e2e8f0;"><i class="fas fa-receipt"></i> Recent Revenue Activity</h3>
                <?php if (empty($entries)): ?>
                    <div style="color:#94a3b8;">No revenue activity yet.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:0.75rem;">
                        <?php foreach ($entries as $entry): ?>
                            <?php
                                $pill = 'pill-coupon';
                                if ($entry['type'] === 'delivery') $pill = 'pill-delivery';
                                elseif ($entry['type'] === 'monthly_fee') $pill = 'pill-monthly';
                                elseif ($entry['type'] === 'banner_ads') $pill = 'pill-banner';
                            ?>
                            <div style="display:flex; justify-content:space-between; gap:1rem; padding:0.75rem; background:#0f172a; border-radius:10px; border:1px solid #334155;">
                                <div>
                                    <div style="font-weight:600; color:#e2e8f0;"><?php echo htmlspecialchars($entry['label']); ?></div>
                                    <div style="color:#94a3b8; font-size:0.85rem;"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($entry['created_at']))); ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="entry-pill <?php echo $pill; ?>">BDT <?php echo number_format((float)$entry['amount'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
