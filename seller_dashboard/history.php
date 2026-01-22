<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('seller');

$sellerId = get_user_id() ?? 0;

// Fetch all orders that include this seller's products
$allOrders = db_fetch_all(
    "SELECT
        o.order_id,
        o.status,
        o.created_at,
        u.full_name AS buyer_name,
        SUM(oi.price * oi.quantity) AS total_amount,
        COUNT(DISTINCT oi.order_item_id) AS item_count,
        (
            SELECT GROUP_CONCAT(DISTINCT p2.name SEPARATOR ', ')
            FROM order_items oi2
            INNER JOIN products p2 ON p2.product_id = oi2.product_id
            WHERE oi2.order_id = o.order_id AND p2.seller_id = ?
        ) AS product_names
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     INNER JOIN users u ON u.user_id = o.buyer_id
     WHERE p.seller_id = ?
     GROUP BY o.order_id, o.status, o.created_at, u.full_name
     ORDER BY o.created_at DESC",
    [$sellerId, $sellerId]
);

$totalOrders = is_array($allOrders) ? count($allOrders) : 0;
$deliveredOrders = 0;
$totalRevenue = 0.0;
foreach ($allOrders as $orderRow) {
    if (isset($orderRow['status']) && strtolower((string)$orderRow['status']) === 'delivered') {
        $deliveredOrders++;
    }
    if (isset($orderRow['total_amount'])) {
        $totalRevenue += (float)$orderRow['total_amount'];
    }
}

function format_order_status(string $status): string {
    $status = strtolower(trim($status));
    $map = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    return $map[$status] ?? 'Unknown';
}

function get_status_color(string $status): string {
    $status = strtolower(trim($status));
    $colors = [
        'pending' => '#f59e0b',
        'processing' => '#3b82f6',
        'shipped' => '#8b5cf6',
        'in_transit' => '#06b6d4',
        'out_for_delivery' => '#10b981',
        'delivered' => '#34d399',
        'cancelled' => '#ef4444'
    ];
    return $colors[$status] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seller Order History | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/seller_history.css" />
</head>
<body class="dark-mode">
    <script>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'seller');
    </script>
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar(){
                const r = await fetch('../html/navbar.php');
                const h = await r.text();
                document.getElementById('navbarContainer').innerHTML = h;
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => { const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-history"></i> Seller Order History';
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal();
                }, 50);
            }
            loadNavbar();

            async function loadSidebar(){
                const r = await fetch('../html/leftsidebar.php');
                const h = await r.text();
                document.getElementById('sidebarContainer').innerHTML = h;
                const scripts = document.getElementById('sidebarContainer').querySelectorAll('script');
                scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
            }
            loadSidebar();
        </script>
        <div class="page-content">
            <section class="history-section" data-aos="fade-up">
                <div class="history-header">
                    <div>
                        <h1><i class="fas fa-history"></i> Order History</h1>
                        <p class="subtitle">All orders that include your products.</p>
                        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.75rem;">
                            <div style="min-width:150px;padding:0.6rem 0.9rem;border-radius:0.75rem;background:rgba(148,163,184,0.08);border:1px solid rgba(148,163,184,0.3);">
                                <div style="font-size:0.75rem;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;">Total Orders</div>
                                <div style="font-size:1.1rem;font-weight:600;color:#e5e7eb;">
                                    <?php echo number_format($totalOrders); ?>
                                </div>
                            </div>
                            <div style="min-width:150px;padding:0.6rem 0.9rem;border-radius:0.75rem;background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.4);">
                                <div style="font-size:0.75rem;color:#6ee7b7;text-transform:uppercase;letter-spacing:0.06em;">Delivered</div>
                                <div style="font-size:1.1rem;font-weight:600;color:#6ee7b7;">
                                    <?php echo number_format($deliveredOrders); ?>
                                </div>
                            </div>
                            <div style="min-width:180px;padding:0.6rem 0.9rem;border-radius:0.75rem;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.4);">
                                <div style="font-size:0.75rem;color:#93c5fd;text-transform:uppercase;letter-spacing:0.06em;">Total Revenue</div>
                                <div style="font-size:1.1rem;font-weight:600;color:#bfdbfe;">
                                    BDT <?php echo number_format($totalRevenue, 2); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="./seller_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>

                <?php if (empty($allOrders)): ?>
                    <div class="empty-state">
                        <div class="icon"><i class="fas fa-inbox"></i></div>
                        <h2>No Orders Yet</h2>
                        <p>Once customers purchase your products, you'll see their orders here.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($allOrders as $order): ?>
                            <article class="order-card" data-aos="fade-up">
                                <header class="order-card-header">
                                    <div>
                                        <p class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></p>
                                        <p class="order-date"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars(date('M d, Y \a\t h:i A', strtotime($order['created_at']))); ?></p>
                                        <p class="order-buyer"><i class="fas fa-user"></i> Buyer: <?php echo htmlspecialchars($order['buyer_name'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <span class="order-status" style="background-color: <?php echo get_status_color($order['status']); ?>;">
                                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(format_order_status($order['status'])); ?>
                                    </span>
                                </header>
                                <div class="order-card-body">
                                    <div class="order-detail">
                                        <span class="label">Products</span>
                                        <span class="value"><?php echo htmlspecialchars($order['product_names'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="order-detail">
                                        <span class="label">Items</span>
                                        <span class="value"><?php echo htmlspecialchars($order['item_count']); ?> items</span>
                                    </div>
                                    <div class="order-detail">
                                        <span class="label">Total Revenue</span>
                                        <span class="value">BDT <?php echo number_format((float)$order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                <footer class="order-card-footer">
                                    <button type="button" class="btn-outline" onclick="alert('View order details for #<?php echo $order['order_id']; ?>')"><i class="fas fa-eye"></i> View Details</button>
                                    <button type="button" class="btn-outline" onclick="alert('Contact buyer for order #<?php echo $order['order_id']; ?>')"><i class="fas fa-comments"></i> Contact Buyer</button>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            <div id="footerContainer" class="mt-8"></div>
        </div>
    </main>
    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 });
        }
        async function loadFooter(){
            try {
                const r = await fetch('../html/footer.php');
                const h = await r.text();
                document.getElementById('footerContainer').innerHTML = h;
            } catch(e) {
                console.error('Error loading footer:', e);
            }
        }
        loadFooter();
    </script>
</body>
</html>
