<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('buyer');

$buyerId = get_user_id() ?? 0;

// Fetch all orders for the buyer
$allOrders = db_fetch_all(
    "SELECT
        o.order_id,
        o.status,
        o.created_at,
        o.total_amount,
        (
            SELECT COUNT(*)
            FROM order_items
            WHERE order_id = o.order_id
        ) AS item_count,
        (
            SELECT GROUP_CONCAT(p.name SEPARATOR ', ')
            FROM order_items oi
            INNER JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = o.order_id
        ) AS product_names
     FROM orders o
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC",
    [$buyerId]
);

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
    <title>Order History | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display: flex; flex-direction: row; min-height: 100vh; }
        main.main-content { flex: 1; display: flex; flex-direction: column; margin-left: 280px; width: calc(100% - 280px); transition: margin-left 0.3s ease, width 0.3s ease; }
        body:has(.sidebar.collapsed) main.main-content { margin-left: 80px; width: calc(100% - 80px); }
        .page-content { flex: 1; padding: 1.5rem; }
        .history-container { background: #1e293b; border-radius: 12px; padding: 1.5rem; border: 1px solid #334155; }
        .history-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 2px solid #334155; padding-bottom: 1rem; }
        .history-header h2 { margin: 0; color: #fff; font-size: 1.5rem; }
        .order-item { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; transition: all 0.3s ease; }
        .order-item:hover { border-color: #64748b; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .order-id { color: #94a3b8; font-size: 0.9rem; font-weight: 600; }
        .order-status { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; color: #fff; }
        .order-date { color: #94a3b8; font-size: 0.9rem; }
        .order-body { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .order-detail { display: flex; flex-direction: column; gap: 0.25rem; }
        .order-detail-label { color: #94a3b8; font-size: 0.85rem; }
        .order-detail-value { color: #e2e8f0; font-weight: 500; }
        .order-footer { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .order-btn { background: #334155; color: #e2e8f0; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease; }
        .order-btn:hover { background: #475569; }
        .empty-state { text-align: center; padding: 3rem 1rem; }
        .empty-state-icon { font-size: 3rem; color: #64748b; margin-bottom: 1rem; }
        .empty-state-title { color: #e2e8f0; font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .empty-state-text { color: #94a3b8; margin-bottom: 1.5rem; }
        .empty-state-btn { background: #3b82f6; color: #fff; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; }
        .empty-state-btn:hover { background: #2563eb; }
    </style>
</head>
<body class="dark-mode">
    <script>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'buyer');
    </script>
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar() {
                const response = await fetch('../html/navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => { const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-history"></i> Order History';
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal();
                }, 50);
            }
            loadNavbar();
        </script>
        <div class="page-content">
            <div class="history-container" data-aos="fade-up">
                <div class="history-header">
                    <h2><i class="fas fa-history"></i> Order History</h2>
                    <a href="./buyer_dashboard.php" class="order-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
                
                <?php if (empty($allOrders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                        <div class="empty-state-title">No Orders Yet</div>
                        <div class="empty-state-text">You haven't placed any orders yet. Start shopping to see your order history.</div>
                        <button class="empty-state-btn" onclick="window.location.href='../html/products_page.php'"><i class="fas fa-shopping-cart"></i> Start Shopping</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($allOrders as $order): ?>
                        <div class="order-item" data-aos="fade-up">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                                    <div class="order-date"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars(date('M d, Y \a\t h:i A', strtotime($order['created_at']))); ?></div>
                                </div>
                                <span class="order-status" style="background-color: <?php echo get_status_color($order['status']); ?>">
                                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(format_order_status($order['status'])); ?>
                                </span>
                            </div>
                            <div class="order-body">
                                <div class="order-detail">
                                    <div class="order-detail-label">Products</div>
                                    <div class="order-detail-value"><?php echo htmlspecialchars($order['product_names'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="order-detail">
                                    <div class="order-detail-label">Total Amount</div>
                                    <div class="order-detail-value">BDT <?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                                <div class="order-detail">
                                    <div class="order-detail-label">Items</div>
                                    <div class="order-detail-value"><?php echo htmlspecialchars($order['item_count']); ?> items</div>
                                </div>
                            </div>
                            <div class="order-footer">
                                <button class="order-btn" onclick="alert('View order details for #<?php echo $order['order_id']; ?>')"><i class="fas fa-eye"></i> View Details</button>
                                <button class="order-btn" onclick="alert('Tracking for order #<?php echo $order['order_id']; ?>')"><i class="fas fa-map"></i> Track Order</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div id="footerContainer" class="mt-8"></div>
        </div>
    </main>
    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        if (typeof AOS !== 'undefined') { AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 }); }
        async function loadFooter(){ try{ const r=await fetch('../html/footer.php'); const h=await r.text(); document.getElementById('footerContainer').innerHTML=h; }catch(e){ console.error('Error loading footer:', e); } }
        loadFooter();
    </script>
</body>
</html>
