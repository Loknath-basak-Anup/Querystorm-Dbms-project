<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';

require_role('seller');

$sellerId = get_user_id() ?? 0;

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

$orderFilter = (int)($_GET['order_id'] ?? 0);

$pendingApprovals = db_fetch_all(
    "SELECT oda.order_id, oda.status, oda.created_at,
            o.created_at AS order_created,
            SUM(oi.quantity) AS item_count
     FROM order_delivery_approvals oda
     INNER JOIN orders o ON o.order_id = oda.order_id
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE oda.seller_id = ?
       AND oda.status = 'pending'
       AND (? = 0 OR oda.order_id = ?)
       AND p.seller_id = ?
     GROUP BY oda.order_id, oda.status, oda.created_at, o.created_at
     ORDER BY oda.created_at DESC",
    [$sellerId, $orderFilter, $orderFilter, $sellerId]
);

$flashMsg = $_GET['msg'] ?? '';
$flashErr = $_GET['err'] ?? '';

$couriers = [
    ['name' => 'Sundarban Courier Service', 'address' => 'Dhaka North Hub, Mirpur 10'],
    ['name' => 'SA Paribahan', 'address' => 'Motijheel Depot, Dhaka'],
    ['name' => 'Janani Courier', 'address' => 'Chattogram Service Point, Agrabad'],
    ['name' => 'Continental Courier', 'address' => 'Sylhet Central Office, Zindabazar'],
    ['name' => 'Korotoa Courier', 'address' => 'Rajshahi City Branch, Shaheb Bazar']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Approve Delivery | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#0f172a;
            --card:#111827;
            --line:rgba(148,163,184,0.2);
            --text:#e2e8f0;
            --muted:#94a3b8;
            --accent:#38bdf8;
            --success:#22c55e;
        }
        *{box-sizing:border-box}
        body{margin:0; font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text);}
        .wrap{max-width:1100px; margin:0 auto; padding:32px 18px 60px;}
        .header{display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;}
        .card{background:var(--card); border:1px solid var(--line); border-radius:16px; padding:18px; margin-top:16px;}
        .row{display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; align-items:center;}
        .label{font-size:0.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em;}
        .value{font-weight:600;}
        .btn{border:none; border-radius:10px; padding:10px 14px; font-weight:700; cursor:pointer;}
        .btn-approve{background:linear-gradient(135deg,#22c55e,#16a34a); color:#0f172a;}
        .btn-back{background:transparent; border:1px solid var(--line); color:var(--text); text-decoration:none; padding:8px 12px; border-radius:10px;}
        select{width:100%; padding:10px; border-radius:10px; border:1px solid var(--line); background:#0b1220; color:var(--text);}
        .flash{padding:10px 12px; border-radius:10px; margin-top:14px;}
        .flash.success{background:rgba(34,197,94,0.15); border:1px solid rgba(34,197,94,0.4); color:#bbf7d0;}
        .flash.error{background:rgba(248,113,113,0.15); border:1px solid rgba(248,113,113,0.4); color:#fecaca;}
        @media (max-width: 720px){ .row{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <div>
                <h2 style="margin:0;">Approve Delivery</h2>
                <div style="color:var(--muted);">Select a courier before dispatching.</div>
            </div>
            <a class="btn-back" href="seller_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($flashMsg): ?>
            <div class="flash success"><?php echo htmlspecialchars($flashMsg); ?></div>
        <?php endif; ?>
        <?php if ($flashErr): ?>
            <div class="flash error"><?php echo htmlspecialchars($flashErr); ?></div>
        <?php endif; ?>

        <?php if (empty($pendingApprovals)): ?>
            <div class="card" style="text-align:center; color:var(--muted);">
                No pending delivery approvals.
            </div>
        <?php else: ?>
            <?php foreach ($pendingApprovals as $approval): ?>
                <div class="card">
                    <div class="row">
                        <div>
                            <div class="label">Order</div>
                            <div class="value">#<?php echo (int)$approval['order_id']; ?></div>
                        </div>
                        <div>
                            <div class="label">Items</div>
                            <div class="value"><?php echo (int)$approval['item_count']; ?> items</div>
                        </div>
                        <div>
                            <div class="label">Placed</div>
                            <div class="value"><?php echo htmlspecialchars(date('M d, Y', strtotime($approval['order_created']))); ?></div>
                        </div>
                    </div>
                    <form method="POST" action="../actions/seller_delivery_approval.php" style="margin-top:14px;">
                        <input type="hidden" name="order_id" value="<?php echo (int)$approval['order_id']; ?>">
                        <div style="margin-bottom:12px;">
                            <label class="label" for="courier-<?php echo (int)$approval['order_id']; ?>">Courier Service</label>
                            <select name="courier" id="courier-<?php echo (int)$approval['order_id']; ?>" required>
                                <option value="" disabled selected>Select a courier</option>
                                <?php foreach ($couriers as $courier): ?>
                                    <option value="<?php echo htmlspecialchars($courier['name'] . '|' . $courier['address']); ?>">
                                        <?php echo htmlspecialchars($courier['name'] . ' - ' . $courier['address']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-approve"><i class="fas fa-truck"></i> Approve & Send</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
