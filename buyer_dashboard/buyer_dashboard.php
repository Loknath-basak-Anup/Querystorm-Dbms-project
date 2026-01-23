<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('buyer');

$buyerId = get_user_id() ?? 0;

$totalOrdersRow = db_fetch(
    "SELECT COUNT(*) AS total_orders FROM orders WHERE buyer_id = ?",
    [$buyerId]
);
$totalOrders = (int)($totalOrdersRow['total_orders'] ?? 0);

$cartItemsRow = db_fetch(
    "SELECT COALESCE(SUM(ci.quantity), 0) AS cart_items
     FROM carts c
     LEFT JOIN cart_items ci ON c.cart_id = ci.cart_id
     WHERE c.buyer_id = ?",
    [$buyerId]
);
$cartItems = (int)($cartItemsRow['cart_items'] ?? 0);

$walletRow = db_fetch(
    "SELECT COALESCE(SUM(
        CASE
            WHEN txn_type IN ('credit','deposit','topup','refund') THEN amount
            WHEN txn_type IN ('debit','purchase','withdraw') THEN -amount
            ELSE amount
        END
    ), 0) AS balance
     FROM wallet_transactions
     WHERE user_id = ?",
    [$buyerId]
);
$walletBalance = (float)($walletRow['balance'] ?? 0);
if ($walletBalance < 0) {
    $walletBalance = 0; // Prevent negative balance display
}

$inTransitRow = db_fetch(
    "SELECT COUNT(*) AS in_transit
     FROM orders
     WHERE buyer_id = ?
       AND status IN ('shipped','in_transit','out_for_delivery')",
    [$buyerId]
);
$inTransitCount = (int)($inTransitRow['in_transit'] ?? 0);

$recentOrders = db_fetch_all(
    "SELECT
        o.order_id,
        o.status,
        o.created_at,
        (
            SELECT p.name
            FROM order_items oi
            INNER JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = o.order_id
            ORDER BY oi.order_item_id ASC
            LIMIT 1
        ) AS product_name
     FROM orders o
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC
     LIMIT 3",
    [$buyerId]
);

function format_order_status(string $status): string {
    $status = strtolower(trim($status));
    $map = [
        'pending' => 'Order Pending',
        'processing' => 'Order Processing',
        'shipped' => 'Order Shipped',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Order Delivered',
        'cancelled' => 'Order Cancelled'
    ];
    return $map[$status] ?? 'Order Update';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buyer Dashboard | QuickMart</title>
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
        .page-content { flex: 1; }
        .welcome-banner { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.25rem; border-radius:1rem; background:linear-gradient(135deg,#0b1324 0%,#101a37 100%); border:1px solid #1e293b; margin: 0 1rem 1.5rem; }
        .welcome-banner h2 { margin:0; font-size:1.25rem; color:#fff; }
        .welcome-banner p { margin:0.25rem 0 0; color:#94a3b8; font-size:0.95rem; }
        .welcome-actions { display:flex; gap:0.75rem; }
        .welcome-actions .btn { background:#1f2937; color:#e2e8f0; border:none; border-radius:10px; padding:0.6rem 1rem; cursor:pointer; }
    </style>
</head>
<body class="dark-mode">
    <script>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'buyer');
        localStorage.setItem('userEmail', <?php echo json_encode($_SESSION['email'] ?? ''); ?>);
        localStorage.setItem('userName', <?php echo json_encode($_SESSION['full_name'] ?? 'Buyer'); ?>);
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
                if (pageTitle) pageTitle.innerHTML = '<i class="fa-solid fa-basket-shopping"></i> Buyer Dashboard';
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal();
                    const themeToggle=document.getElementById('themeToggle');
                    if (themeToggle) themeToggle.onclick=function(){ const body=document.body; const icon=themeToggle.querySelector('i'); body.classList.toggle('dark-mode'); if (body.classList.contains('dark-mode')) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); localStorage.setItem('quickmart_theme','dark'); } else { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); localStorage.setItem('quickmart_theme','light'); } };
                    const savedTheme = localStorage.getItem('quickmart_theme');
                    if (savedTheme === 'light') { document.body.classList.remove('dark-mode'); const icon = themeToggle ? themeToggle.querySelector('i') : null; if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); } }
                    if (typeof window.setupNotificationModal === 'function') window.setupNotificationModal();
                }, 50);
            }
            loadNavbar();
        </script>
        <div class="page-content">
            <div class="welcome-banner" data-aos="zoom-in-up">
                <div>
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Buyer'); ?>!</h2>
                    <p>Your email: <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?> | Role: Buyer</p>
                </div>
                <div class="welcome-actions">
                    <button class="btn" onclick="window.location.href='../html/products_page.php'"><i class="fas fa-store"></i> Shop Now</button>
                    <button class="btn" onclick="window.location.href='./cart.php'"><i class="fas fa-basket-shopping"></i> Cart</button>
                    <button class="btn" onclick="window.location.href='../coupon_store.php'"><i class="fas fa-ticket"></i> Coupon Store</button>
                </div>
            </div>
            <div class="dashboard-stats" data-aos="zoom-in-up">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-details"><span class="stat-label">Total Orders</span><span class="stat-value"><?php echo number_format($totalOrders); ?></span><span class="stat-change"><i class="fas fa-receipt"></i> Orders placed</span></div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%)"><i class="fas fa-heart"></i></div>
                    <div class="stat-details"><span class="stat-label">Saved Items</span><span class="stat-value"><?php echo number_format($cartItems); ?></span><span class="stat-change"><i class="fas fa-bookmark"></i> Items in cart</span></div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)"><i class="fas fa-wallet"></i></div>
                    <div class="stat-details"><span class="stat-label">Wallet Balance</span><span class="stat-value">BDT <?php echo number_format($walletBalance, 2); ?></span><span class="stat-change"><i class="fas fa-coins"></i> Available funds</span></div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#fa709a 0%,#fee140 100%)"><i class="fas fa-truck"></i></div>
                    <div class="stat-details"><span class="stat-label">In Transit</span><span class="stat-value"><?php echo number_format($inTransitCount); ?></span><span class="stat-change"><i class="fas fa-shipping-fast"></i> On the way</span></div>
                </div>
            </div>
            <div class="dashboard-grid">
                <div class="dashboard-section" data-aos="zoom-in-up">
                    <div class="section-header">
                        <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                        <a href="./history.php" class="view-all">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php if (empty($recentOrders)): ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background:rgba(148,163,184,0.1);color:#94a3b8"><i class="fas fa-info-circle"></i></div>
                                <div class="activity-content">
                                    <span class="activity-title">No recent activity</span>
                                    <span class="activity-meta">Your orders will appear here</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="activity-item">
                                    <div class="activity-icon" style="background:rgba(79,172,254,0.1);color:#4facfe"><i class="fas fa-box"></i></div>
                                    <div class="activity-content">
                                        <span class="activity-title"><?php echo htmlspecialchars(format_order_status($order['status'] ?? '')); ?></span>
                                        <span class="activity-meta"><?php echo htmlspecialchars($order['product_name'] ?: 'Order #' . (int)$order['order_id']); ?> - <?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dashboard-section" data-aos="zoom-in-up">
                    <div class="section-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
                    <div class="quick-actions">
                        <button class="action-btn" onclick="window.location.href='./buyer_dashboard.php'"><i class="fas fa-gauge"></i><span>Dashboard</span></button>
                        <button class="action-btn" onclick="window.location.href='../html/products_page.php'"><i class="fas fa-shopping-cart"></i><span>Browse Products</span></button>
                        <button class="action-btn" onclick="window.location.href='./cart.php'"><i class="fas fa-basket-shopping"></i><span>View Cart</span></button>
                        <button class="action-btn" onclick="window.location.href='../coupon_store.php'"><i class="fas fa-ticket"></i><span>Coupon Store</span></button>
                        <button class="action-btn" onclick="window.location.href='./saved_items.php'"><i class="fas fa-bookmark"></i><span>Saved Items</span></button>
                        <button class="action-btn" onclick="window.location.href='./wallet.php'"><i class="fas fa-wallet"></i><span>Manage Wallet</span></button>
                        <button class="action-btn" onclick="window.location.href='../buyer/profile.php'"><i class="fas fa-user"></i><span>Edit Profile</span></button>
                        <button class="action-btn" onclick="window.location.href='./settings.php'"><i class="fas fa-cog"></i><span>Settings</span></button>
                    </div>
                </div>
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
