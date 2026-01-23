<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

require_role('buyer');

$buyerId = get_user_id() ?? 0;
$orderId = (int)($_GET['order_id'] ?? 0);

// --- Database Setup (Keep existing logic) ---
try {
    db_query("CREATE TABLE IF NOT EXISTS order_delivery (order_id INT(11) NOT NULL, courier_name VARCHAR(120) NOT NULL, courier_address VARCHAR(160) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    db_query("CREATE TABLE IF NOT EXISTS order_delivery_steps (order_id INT(11) NOT NULL, step VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (order_id, step)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    db_query("CREATE TABLE IF NOT EXISTS order_delivery_approvals (approval_id INT(11) NOT NULL AUTO_INCREMENT, order_id INT(11) NOT NULL, seller_id INT(11) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', courier_name VARCHAR(120) DEFAULT NULL, courier_address VARCHAR(160) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, approved_at DATETIME DEFAULT NULL, PRIMARY KEY (approval_id), UNIQUE KEY uniq_order_seller (order_id, seller_id), KEY idx_order_status (order_id, status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Throwable $e) { }

// --- Data Fetching ---
$order = db_fetch(
    "SELECT o.order_id, o.status, o.created_at, o.total_amount, COUNT(oi.order_item_id) AS item_count
     FROM orders o INNER JOIN order_items oi ON oi.order_id = o.order_id
     WHERE o.order_id = ? AND o.buyer_id = ?
     GROUP BY o.order_id, o.status, o.created_at, o.total_amount",
    [$orderId, $buyerId]
);

$items = [];
if ($order) {
    $items = db_fetch_all(
        "SELECT oi.quantity, oi.price, p.name, p.description,
                (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) AS image_url
         FROM order_items oi INNER JOIN products p ON p.product_id = oi.product_id
         WHERE oi.order_id = ?",
        [$orderId]
    );
}

$delivery = $order ? db_fetch("SELECT courier_name FROM order_delivery WHERE order_id = ?", [$orderId]) : null;
$stepRows = $order ? db_fetch_all("SELECT step, created_at FROM order_delivery_steps WHERE order_id = ?", [$orderId]) : [];

$stepDates = [];
foreach ($stepRows as $row) {
    $stepDates[$row['step']] = $row['created_at'];
}

// --- Logic Helpers ---
function fmt_date(?string $dt, string $fallback = 'Pending'): string {
    if (!$dt) return $fallback;
    return date('M dS, Y', strtotime($dt)); // Format: Feb 20th, 2021
}

function status_label(string $status): string {
    return ucwords(str_replace('_', ' ', $status));
}

// Determine Progress (0-4)
$status = strtolower((string)($order['status'] ?? 'pending'));
$progressIndex = 0;
if ($status === 'processing') $progressIndex = 1;
elseif (in_array($status, ['shipped', 'in_transit'])) $progressIndex = 2;
elseif ($status === 'out_for_delivery') $progressIndex = 3;
elseif (in_array($status, ['delivered', 'completed'])) $progressIndex = 4;
if (isset($stepDates['delivered'])) $progressIndex = 4;

$orderPlaced = $order['created_at'] ?? '';
$orderDelivered = $stepDates['delivered'] ?? null;
$trackingId = $order ? ('QM-' . $orderId . '89') : ''; // Simulating a longer ID like the UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Details | QuickMart</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #f50057; /* Hot Pink */
            --primary-grad: linear-gradient(135deg, #a855f7 0%, #f50057 100%); /* Purple to Pink for icons */
            --bg: #f3f4f8;
            --card: #ffffff;
            --text-dark: #111827;
            --text-light: #9ca3af;
            --border: #e5e7eb;
        }

        body {
            background-color: var(--bg);
            font-family: 'Poppins', 'Manrope', sans-serif;
            color: var(--text-dark);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }

        .container {
            background: var(--card);
            width: 100%;
            max-width: 1100px;
            border-radius: 20px;
            padding: 40px 50px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            min-height: 80vh;
        }

        /* --- Header Section --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-link {
            text-decoration: none;
            color: var(--text-dark);
            font-size: 20px;
            font-weight: 600;
        }

        h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .btn-invoice {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .btn-invoice:hover { transform: translateY(-2px); }

        /* --- Info Strip --- */
        .info-strip {
            display: flex;
            gap: 60px;
            margin-bottom: 50px;
            flex-wrap: wrap;
        }

        .info-item h4 {
            color: var(--text-light);
            font-size: 12px;
            font-weight: 500;
            margin: 0 0 6px 0;
            text-transform: capitalize;
        }

        .info-item p {
            margin: 0;
            font-weight: 500;
            font-size: 15px;
            color: var(--text-dark);
        }

        /* --- Tracking Section --- */
        .tracking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .tracking-header h3 { font-size: 16px; font-weight: 500; margin: 0; }
        .tracking-id { color: var(--text-light); font-size: 14px; }

        .timeline-wrapper {
            position: relative;
            padding: 20px 0 60px;
            margin-bottom: 40px;
            border-bottom: 1px solid #f0f0f0;
        }

        /* The grey background line */
        .timeline-line-bg {
            position: absolute;
            top: 75px; /* Adjust based on icon height */
            left: 5%;
            width: 90%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 10px;
            z-index: 1;
        }

        /* The active pink line */
        .timeline-line-active {
            position: absolute;
            top: 75px;
            left: 5%;
            height: 4px;
            background: var(--primary);
            border-radius: 10px;
            z-index: 2;
            transition: width 0.5s ease;
            width: 0%; /* Set via inline PHP */
        }

        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 140px;
        }

        .step-icon {
            font-size: 32px;
            margin-bottom: 25px;
            /* Gradient Text Effect for Icons */
            background: var(--primary-grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.4; /* Inactive state */
            filter: grayscale(100%);
        }

        .step.active .step-icon {
            opacity: 1;
            filter: none;
        }

        .step-dot {
            width: 14px;
            height: 14px;
            background: #fff;
            border: 3px solid #e5e7eb;
            border-radius: 50%;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .step.active .step-dot {
            border-color: var(--primary);
            background: #fff;
        }
        
        /* The last active dot should be filled or distinct if desired, 
           matching reference: Reference has hollow dots with thick borders */

        .step-label {
            font-weight: 700;
            font-size: 13px;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .step-date {
            font-size: 11px;
            color: var(--text-light);
        }

        /* --- Items Table --- */
        .items-section h3 {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 25px;
        }

        .items-grid-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 20px;
            color: var(--text-light);
            font-size: 12px;
            font-weight: 500;
        }

        .item-row {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr;
            align-items: center;
            margin-bottom: 25px;
        }

        .product-col {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .product-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            background: #f9f9f9;
        }

        .product-details h4 {
            margin: 0 0 5px 0;
            font-size: 15px;
            font-weight: 700;
        }

        .product-details p {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: var(--text-light);
            line-height: 1.5;
        }

        .product-details .meta {
            font-size: 12px;
            color: #6b7280;
        }

        .qty-col {
            font-weight: 500;
            font-size: 14px;
            padding-left: 10px; /* Align slightly */
        }

        .total-col {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-dark);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; } /* Reference has total left aligned actually, but standard is right. Checking ref... ref shows text left aligned under header. Okay. */
        
        /* Correction based on visual: Reference Total header is left-aligned relative to column, but looks centered in whitespace. I will use start for header, start for value. */

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .info-strip { gap: 20px; }
            .timeline-steps { overflow-x: auto; padding-bottom: 20px; }
            .step { min-width: 100px; }
            .timeline-line-bg, .timeline-line-active { width: 100%; left: 0; min-width: 500px; } 
            .item-row, .items-grid-header { grid-template-columns: 1fr; gap: 10px; }
            .qty-col, .total-col { display: flex; justify-content: space-between; width: 100%; border-bottom: 1px dashed #eee; padding-bottom: 5px; }
            .qty-col::before { content: "Quantity:"; color: #999; }
            .total-col::before { content: "Total:"; color: #999; }
            .timeline-wrapper { overflow-x: auto; }
        }
        
        .empty-state { text-align: center; padding: 100px 0; color: #999; }
    </style>
</head>
<body>

    <div class="container">
        <?php if (!$order): ?>
            <div class="empty-state">
                <h2>Order Not Found</h2>
                <p>Please check the order ID or go back to history.</p>
                <a href="./history.php" style="color:var(--primary);">Back to Orders</a>
            </div>
        <?php else: ?>

            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <a href="./history.php" class="back-link"><i class="fa-solid fa-chevron-left"></i></a>
                    <h1>Order Details</h1>
                </div>
                <button class="btn-invoice" onclick="window.print()">
                    Download Invoice <i class="fa-regular fa-file-lines"></i>
                </button>
            </div>

            <!-- Info Strip -->
            <div class="info-strip">
                <div class="info-item">
                    <h4>Order Number</h4>
                    <p><?php echo htmlspecialchars($orderId); ?></p>
                </div>
                <div class="info-item">
                    <h4>Order Placed</h4>
                    <p><?php echo fmt_date($orderPlaced); ?></p>
                </div>
                <div class="info-item">
                    <h4>Order Delivered</h4>
                    <p><?php echo fmt_date($orderDelivered, '--'); ?></p>
                </div>
                <div class="info-item">
                    <h4>No of items</h4>
                    <p><?php echo (int)$order['item_count']; ?> items</p>
                </div>
                <div class="info-item">
                    <h4>Status</h4>
                    <p><?php echo status_label($order['status']); ?></p>
                </div>
            </div>

            <!-- Tracking Section -->
            <div class="tracking-header">
                <h3>Order Tracking</h3>
                <span class="tracking-id">Tracking ID #<?php echo htmlspecialchars($trackingId); ?></span>
            </div>

            <div class="timeline-wrapper">
                <!-- Lines -->
                <div class="timeline-line-bg"></div>
                <div class="timeline-line-active" style="width: <?php echo ($progressIndex * 25); ?>%;"></div>

                <!-- Steps -->
                <div class="timeline-steps">
                    <?php 
                    $steps = [
                        ['label' => 'Order Placed', 'icon' => 'fa-cart-shopping', 'key' => 'created'],
                        ['label' => 'Order Packed', 'icon' => 'fa-box-open', 'key' => 'processing'],
                        ['label' => 'In Transit', 'icon' => 'fa-truck-fast', 'key' => 'shipped'],
                        ['label' => 'Out for delivery', 'icon' => 'fa-dolly', 'key' => 'out_for_delivery'],
                        ['label' => 'Delivered', 'icon' => 'fa-house-chimney', 'key' => 'delivered']
                    ];
                    
                    // Helper to get date for specific step
                    // Note: Simplification for UI demo based on $progressIndex
                    function getStepDate($key, $dates, $created) {
                        if ($key == 'created') return $created;
                        // Map internal status keys to DB step keys if necessary
                        $dbKey = ($key == 'processing') ? 'packing' : $key; 
                        return $dates[$dbKey] ?? null;
                    }

                    foreach ($steps as $i => $step): 
                        $isActive = $i <= $progressIndex;
                        $date = getStepDate($step['key'], $stepDates, $orderPlaced);
                        // If step is active but no specific date found, show 'Pending' or just the date if it's the current latest step
                        $displayDate = ($isActive && $date) ? fmt_date($date) : '';
                        if ($i == 0) $displayDate = fmt_date($orderPlaced);
                    ?>
                        <div class="step <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fa-solid <?php echo $step['icon']; ?> step-icon"></i>
                            <div class="step-dot"></div>
                            <div class="step-label"><?php echo $step['label']; ?></div>
                            <div class="step-date"><?php echo $displayDate; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Items Section -->
            <div class="items-section">
                <h3>Items from the order</h3>
                
                <div class="items-grid-header">
                    <div>Product</div>
                    <div>Quantity</div>
                    <div>Total</div>
                </div>

                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div class="product-col">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'placeholder.jpg'); ?>" alt="Product" class="product-img">
                            <div class="product-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p><?php echo htmlspecialchars($item['description'] ?? 'No description'); ?></p>
                                <?php if ($delivery): ?>
                                    <div class="meta">Courier: <?php echo htmlspecialchars($delivery['courier_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="qty-col">
                            <?php echo (int)$item['quantity']; ?>
                        </div>
                        <div class="total-col">
                            BDT <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            <div style="font-size:11px; color:#999; font-weight:400">includes tax</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>