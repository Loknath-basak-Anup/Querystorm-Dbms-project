<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/coupons.php';
require_role('seller');

$sellerId = get_user_id() ?? 0;
ensure_coupon_tables();

$statsRow = db_fetch(
    "SELECT
        COALESCE(SUM(oi.price * oi.quantity), 0) AS total_sales,
        COUNT(DISTINCT oi.order_id) AS total_orders
     FROM order_items oi
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?",
    [$sellerId]
);
$totalSales = (float)($statsRow['total_sales'] ?? 0);
$totalOrders = (int)($statsRow['total_orders'] ?? 0);

$productsRow = db_fetch(
    "SELECT COUNT(*) AS total_products
     FROM products
     WHERE seller_id = ?",
    [$sellerId]
);
$totalProducts = (int)($productsRow['total_products'] ?? 0);

$customersRow = db_fetch(
    "SELECT COUNT(DISTINCT o.buyer_id) AS total_customers
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?",
    [$sellerId]
);
$totalCustomers = (int)($customersRow['total_customers'] ?? 0);

$couponRow = db_fetch(
    "SELECT COUNT(*) AS total
     FROM coupon_seller_requests
     WHERE seller_id = ? AND status = 'pending'",
    [$sellerId]
);
$pendingCouponRequests = (int)($couponRow['total'] ?? 0);

$sellerProfile = db_fetch(
    "SELECT sp.shop_name, sp.shop_description, sp.verified, u.full_name
     FROM seller_profiles sp
     INNER JOIN users u ON u.user_id = sp.seller_id
     WHERE sp.seller_id = ?
     LIMIT 1",
    [$sellerId]
);
$shopName = trim((string)($sellerProfile['shop_name'] ?? ''));
$shopDescription = trim((string)($sellerProfile['shop_description'] ?? ''));
$sellerName = trim((string)($sellerProfile['full_name'] ?? 'Seller'));
$isVerified = (int)($sellerProfile['verified'] ?? 0) === 1;
$verificationStatus = 'pending';
$declineReason = null;
try {
    $verificationRow = db_fetch(
        "SELECT status, decline_reason
         FROM seller_verification_requests
         WHERE seller_id = ?
         ORDER BY created_at DESC
         LIMIT 1",
        [$sellerId]
    );
    if ($verificationRow) {
        $verificationStatus = $verificationRow['status'] ?? 'pending';
        $declineReason = $verificationRow['decline_reason'] ?? null;
    }
} catch (Exception $e) {
    $verificationStatus = $isVerified ? 'approved' : 'pending';
}
$showDeclinedToast = (!$isVerified && $verificationStatus === 'declined');
$showWelcomeToast = false;
$showVerifiedToast = false;
if (!empty($_SESSION['seller_welcome'])) {
    $showWelcomeToast = true;
    unset($_SESSION['seller_welcome']);
}
if ($isVerified && empty($_SESSION['seller_verified_notice'])) {
    $showVerifiedToast = true;
    $_SESSION['seller_verified_notice'] = true;
}

$statusCounts = db_fetch(
    "SELECT
        SUM(CASE WHEN o.status IN ('delivered','completed') THEN 1 ELSE 0 END) AS delivered_count,
        SUM(CASE WHEN o.status IN ('shipped','in_transit','out_for_delivery') THEN 1 ELSE 0 END) AS in_progress_count,
        SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?",
    [$sellerId]
);
$deliveredCount = (int)($statusCounts['delivered_count'] ?? 0);
$inProgressCount = (int)($statusCounts['in_progress_count'] ?? 0);
$cancelledCount = (int)($statusCounts['cancelled_count'] ?? 0);

$stockRow = db_fetch(
    "SELECT COUNT(*) AS in_stock
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.product_id
     WHERE p.seller_id = ? AND COALESCE(i.stock_qty, 0) > 0",
    [$sellerId]
);
$inStockProducts = (int)($stockRow['in_stock'] ?? 0);

$repeatCustomersRow = db_fetch(
    "SELECT COUNT(*) AS repeat_customers
     FROM (
        SELECT o.buyer_id, COUNT(DISTINCT o.order_id) AS order_count
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.order_id
        INNER JOIN products p ON p.product_id = oi.product_id
        WHERE p.seller_id = ?
        GROUP BY o.buyer_id
        HAVING order_count > 1
     ) t",
    [$sellerId]
);
$repeatCustomers = (int)($repeatCustomersRow['repeat_customers'] ?? 0);

// Monthly revenue for current year for this seller
$currentYear = (int)date('Y');
$monthlyRevenue = array_fill(1, 12, 0.0);

$monthlyRows = db_fetch_all(
    "SELECT
        YEAR(o.created_at) AS year,
        MONTH(o.created_at) AS month,
        COALESCE(SUM(oi.price * oi.quantity), 0) AS revenue
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?
       AND YEAR(o.created_at) = ?
       AND o.status IN ('delivered','completed')
     GROUP BY YEAR(o.created_at), MONTH(o.created_at)
     ORDER BY YEAR(o.created_at), MONTH(o.created_at)",
    [$sellerId, $currentYear]
);

foreach ($monthlyRows as $row) {
    $m = (int)($row['month'] ?? 0);
    if ($m >= 1 && $m <= 12) {
        $monthlyRevenue[$m] = (float)$row['revenue'];
    }
}

$monthNames = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

$monthlyLabels = [];
$monthlyValues = [];
for ($i = 1; $i <= 12; $i++) {
    $monthlyLabels[] = $monthNames[$i];
    $monthlyValues[] = round($monthlyRevenue[$i], 2);
}

function percent(int $num, int $den): int {
    if ($den <= 0) return 0;
    return (int)round(($num / $den) * 100);
}

$fulfillmentRate = percent($deliveredCount, $totalOrders);
$stockHealth = percent($inStockProducts, $totalProducts);
$repeatRate = percent($repeatCustomers, $totalCustomers);
$deliveryRate = percent($inProgressCount + $deliveredCount, $totalOrders);
$ratingScore = $totalOrders > 0 ? min(5, max(1, round(3 + (2 * ($deliveredCount / $totalOrders)), 1))) : 0;

$recentOrders = db_fetch_all(
    "SELECT
        o.order_id,
        o.status,
        o.created_at,
        u.full_name AS buyer_name,
        SUM(oi.price * oi.quantity) AS amount
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     INNER JOIN users u ON u.user_id = o.buyer_id
     WHERE p.seller_id = ?
     GROUP BY o.order_id, o.status, o.created_at, u.full_name
     ORDER BY o.created_at DESC
     LIMIT 5",
    [$sellerId]
);

$deliveryOrders = db_fetch_all(
    "SELECT
        o.order_id,
        o.status,
        o.created_at,
        (
            SELECT p2.name
            FROM order_items oi2
            INNER JOIN products p2 ON p2.product_id = oi2.product_id
            WHERE oi2.order_id = o.order_id AND p2.seller_id = ?
            ORDER BY oi2.order_item_id ASC
            LIMIT 1
        ) AS product_name
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?
     GROUP BY o.order_id, o.status, o.created_at
     ORDER BY o.created_at DESC
     LIMIT 4",
    [$sellerId, $sellerId]
);

$categories = db_fetch_all("SELECT category_id, name FROM categories ORDER BY name ASC");
$subcategories = db_fetch_all("SELECT subcategory_id, category_id, name FROM subcategories ORDER BY name ASC");
$category_map = [];
foreach ($categories as $category) {
    $category_map[$category['category_id']] = $category['name'];
}

$sellerProducts = db_fetch_all(
    "SELECT
        p.product_id,
        p.name,
        p.price,
        p.status,
        p.created_at,
        c.name AS category_name,
        sc.name AS subcategory_name,
        COALESCE(i.stock_qty, 0) AS stock_qty,
        (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) AS image_url
    FROM products p
    INNER JOIN categories c ON p.category_id = c.category_id
    INNER JOIN subcategories sc ON p.subcategory_id = sc.subcategory_id
    LEFT JOIN inventory i ON p.product_id = i.product_id
    WHERE p.seller_id = ?
     ORDER BY p.created_at DESC
     LIMIT 8",
    [$sellerId]
);

function status_badge_class(string $status): string {
    $status = strtolower(trim($status));
    $map = [
        'delivered' => 'status-received',
        'received' => 'status-received',
        'cancelled' => 'status-cancelled',
        'canceled' => 'status-cancelled',
        'pending' => 'status-pending',
        'processing' => 'status-pending',
        'shipped' => 'status-pending',
        'in_transit' => 'status-pending',
        'out_for_delivery' => 'status-pending'
    ];
    return $map[$status] ?? 'status-pending';
}

function status_label(string $status): string {
    $status = strtolower(trim($status));
    $map = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'completed' => 'Delivered',
        'cancelled' => 'Cancelled',
        'canceled' => 'Cancelled'
    ];
    return $map[$status] ?? 'Pending';
}

function delivery_progress(string $status): int {
    $status = strtolower(trim($status));
    $map = [
        'pending' => 10,
        'processing' => 35,
        'shipped' => 65,
        'in_transit' => 65,
        'out_for_delivery' => 85,
        'delivered' => 100,
        'completed' => 100,
        'cancelled' => 0,
        'canceled' => 0
    ];
    return $map[$status] ?? 25;
}

function product_status_badge_class(string $status): string {
    $status = strtolower(trim($status));
    return $status === 'active' ? 'status-received' : 'status-cancelled';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seller Dashboard | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <style>
        body.dark-mode {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
            margin: 0;
        }
        main.main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: margin-left 0.3s ease, width 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        body:has(.sidebar.collapsed) main.main-content {
            margin-left: 80px;
            width: calc(100% - 80px);
        }
        .page-content {
            flex: 1;
        }
    </style>
</head>
<body class="dark-mode">
    <script>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'seller');
        localStorage.setItem('userEmail', <?php echo json_encode($_SESSION['email'] ?? ''); ?>);
        localStorage.setItem('userName', <?php echo json_encode($_SESSION['full_name'] ?? 'Seller'); ?>);
    </script>
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadSidebar() {
                const response = await fetch('../html/leftsidebar.php');
                const html = await response.text();
                document.getElementById('sidebarContainer').innerHTML = html;
                const scripts = document.getElementById('sidebarContainer').querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    newScript.innerHTML = script.innerHTML;
                    document.body.appendChild(newScript);
                });
            }
            loadSidebar();

            async function loadNavbar() {
                const response = await fetch('../html/navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                
                // Execute scripts from loaded HTML
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    newScript.innerHTML = script.innerHTML;
                    document.body.appendChild(newScript);
                });
                
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-gauge"></i> Seller Dashboard';
                
                // Initialize user menu after scripts execute
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') {
                        window.initializeUserMenuGlobal();
                    }
                    
                    // Setup dropdown toggle with hover
                    const userMenu = document.getElementById('userMenu');
                    const userDropdown = document.getElementById('userDropdown');
                    let userMenuTimeout;
                    
                    if (userMenu && userDropdown) {
                        // Show on hover
                        userMenu.onmouseenter = function(e) {
                            clearTimeout(userMenuTimeout);
                            userDropdown.style.display = 'block';
                            userDropdown.style.opacity = '1';
                            userDropdown.style.visibility = 'visible';
                        };
                        
                        // Hide on leave
                        userMenu.onmouseleave = function() {
                            userMenuTimeout = setTimeout(() => {
                                userDropdown.style.display = 'none';
                                userDropdown.style.opacity = '0';
                                userDropdown.style.visibility = 'hidden';
                            }, 200);
                        };
                        
                        userDropdown.onmouseenter = function() {
                            clearTimeout(userMenuTimeout);
                        };
                        
                        userDropdown.onmouseleave = function() {
                            userMenuTimeout = setTimeout(() => {
                                userDropdown.style.display = 'none';
                                userDropdown.style.opacity = '0';
                                userDropdown.style.visibility = 'hidden';
                            }, 200);
                        };
                        
                        // Click fallback
                        userMenu.onclick = function(e) {
                            e.stopPropagation();
                            const isVisible = userDropdown.style.display === 'block';
                            userDropdown.style.display = isVisible ? 'none' : 'block';
                            userDropdown.style.opacity = isVisible ? '0' : '1';
                            userDropdown.style.visibility = isVisible ? 'hidden' : 'visible';
                        };
                    }
                    
                    // Close dropdown on outside click
                    document.onclick = function(e) {
                        const userMenu = document.getElementById('userMenu');
                        const userDropdown = document.getElementById('userDropdown');
                        if (userDropdown && userMenu && !userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                            userDropdown.style.display = 'none';
                            userDropdown.style.opacity = '0';
                            userDropdown.style.visibility = 'hidden';
                        }
                    };
                    
                    // Setup dark mode toggle
                    const themeToggle = document.getElementById('themeToggle');
                    if (themeToggle) {
                        themeToggle.onclick = function() {
                            const body = document.body;
                            const icon = themeToggle.querySelector('i');
                            body.classList.toggle('dark-mode');
                            if (body.classList.contains('dark-mode')) {
                                icon && icon.classList.remove('fa-moon');
                                icon && icon.classList.add('fa-sun');
                                localStorage.setItem('quickmart_theme', 'dark');
                            } else {
                                icon && icon.classList.remove('fa-sun');
                                icon && icon.classList.add('fa-moon');
                                localStorage.setItem('quickmart_theme', 'light');
                            }
                        };

                        // Load saved theme only if toggle exists
                        const savedTheme = localStorage.getItem('quickmart_theme');
                        if (savedTheme === 'light') {
                            document.body.classList.remove('dark-mode');
                            const icon = themeToggle.querySelector('i');
                            if (icon) {
                                icon.classList.remove('fa-moon');
                                icon.classList.add('fa-sun');
                            }
                        }
                    }
                    
                    // Setup notification modal
                    if (typeof window.setupNotificationModal === 'function') {
                        window.setupNotificationModal();
                    }
                }, 50);
            }
            loadNavbar();
        </script>
        <div class="page-content">
            <?php if (!$isVerified): ?>
                <?php if ($verificationStatus === 'declined'): ?>
                    <div style="margin-bottom:20px; padding:16px; border-radius:14px; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.45); color:#fecaca; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:42px; height:42px; border-radius:12px; background:rgba(239,68,68,0.2); display:grid; place-items:center; color:#fecaca;">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;">Application declined</div>
                                <div style="font-size:0.9rem; color:#fecaca;">Your application was declined. Product actions remain locked.</div>
                            </div>
                        </div>
                        <a href="../seller/signup.php?reapply=1" style="padding:10px 16px; border-radius:10px; background:#ef4444; color:#0b1020; font-weight:700; text-decoration:none;">Review & Re-apply</a>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom:20px; padding:16px; border-radius:14px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.45); color:#fde68a; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:42px; height:42px; border-radius:12px; background:rgba(245,158,11,0.2); display:grid; place-items:center; color:#fbbf24;">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;">Verification pending</div>
                                <div style="font-size:0.9rem; color:#fcd34d;">You can explore your dashboard, but product actions are locked until approval.</div>
                            </div>
                        </div>
                        <a href="verify_seller.php" style="padding:10px 16px; border-radius:10px; background:#f59e0b; color:#0b1020; font-weight:700; text-decoration:none;">Review verification</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <!-- Add Product Button -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                <button id="addProductBtn" <?php echo $isVerified ? '' : 'disabled'; ?> style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; border-radius: 8px; padding: 12px 28px; font-size: 1rem; font-family: 'Poppins', sans-serif; font-weight: 500; box-shadow: 0 2px 12px rgba(102,126,234,0.15); cursor: <?php echo $isVerified ? 'pointer' : 'not-allowed'; ?>; opacity: <?php echo $isVerified ? '1' : '0.6'; ?>; transition: background 0.2s;">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
            <!-- Add Product Modal -->
            <div id="addProductModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
                <div style="background:linear-gradient(145deg, #111827 0%, #0b1020 50%, #111827 100%); color:#f8fafc; border-radius:18px; padding:28px 24px; width:100%; max-width:680px; box-shadow:0 18px 48px rgba(0,0,0,0.35); position:relative; border:1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; gap:12px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:42px; height:42px; border-radius:12px; display:grid; place-items:center; background:linear-gradient(135deg,#667eea 0%, #8e9dff 100%); box-shadow:0 0 18px rgba(102,126,234,0.35); color:#0b1020;">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div>
                                <h2 style="margin:0; font-size:1.25rem; font-weight:600;">Add New Product</h2>
                                <p style="margin:4px 0 0; color:#cbd5e1; font-size:0.9rem;">Complete the details and preview before publishing.</p>
                            </div>
                        </div>
                        <button id="closeAddProductModal" style="background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer; transition:color 0.2s;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div style="height:1px; background:linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.12) 40%, rgba(255,255,255,0.12) 60%, transparent 100%); margin-bottom:18px;"></div>
                    <form id="addProductForm" style="display:flex; flex-direction:column; gap:14px;">
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productName" style="font-weight:600; color:#e2e8f0;">Product Name</label>
                                <input type="text" id="productName" name="productName" required placeholder="e.g., Wireless Headphones" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:#f8fafc;">
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productCategory" style="font-weight:600; color:#e2e8f0;">Category</label>
                                <select id="productCategory" name="productCategory" required style="width:100%; padding:10px 12px; border-radius:10px; border:2px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#e2e8f0; font-family:'Poppins',sans-serif; font-size:0.95rem; transition:all 0.3s ease; cursor:pointer;">
                                    <option value="" disabled selected style="color:#94a3b8;">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productSubcategory" style="font-weight:600; color:#e2e8f0;">Subcategory</label>
                                <select id="productSubcategory" name="productSubcategory" required disabled style="width:100%; padding:10px 12px; border-radius:10px; border:2px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#e2e8f0; font-family:'Poppins',sans-serif; font-size:0.95rem; transition:all 0.3s ease; cursor:pointer;">
                                    <option value="" disabled selected style="color:#94a3b8;">Select a subcategory</option>
                                    <?php foreach ($subcategories as $sub): ?>
                                        <?php $cat_name = $category_map[$sub['category_id']] ?? 'Category'; ?>
                                        <option value="<?php echo htmlspecialchars($sub['subcategory_id']); ?>" data-category="<?php echo htmlspecialchars($sub['category_id']); ?>" data-category-name="<?php echo htmlspecialchars($cat_name); ?>" data-subcategory-name="<?php echo htmlspecialchars($sub['name']); ?>">
                                            <?php echo htmlspecialchars($sub['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <style>
                            select#productCategory:focus,
                            select#productSubcategory:focus {
                                outline: none;
                                border-color: #667eea;
                                background: rgba(102, 126, 234, 0.1);
                                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
                            }
                            select#productCategory:hover:not(:focus),
                            select#productSubcategory:hover:not(:focus) {
                                border-color: rgba(255,255,255,0.2);
                                background: rgba(255,255,255,0.08);
                            }
                            select#productCategory option,
                            select#productSubcategory option {
                                background: #0f172a;
                                color: #e2e8f0;
                                padding: 8px 12px;
                            }
                            select#productCategory option:checked,
                            select#productSubcategory option:checked {
                                background: linear-gradient(#667eea, #667eea);
                                background-color: #667eea;
                            }
                        </style>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productPrice" style="font-weight:600; color:#e2e8f0;">Price</label>
                                <input type="number" id="productPrice" name="productPrice" required min="0" step="0.01" placeholder="0.00" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:#f8fafc;">
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productStock" style="font-weight:600; color:#e2e8f0;">Stock</label>
                                <input type="number" id="productStock" name="productStock" required min="0" step="1" placeholder="0" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:#f8fafc;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productSku" style="font-weight:600; color:#e2e8f0;">SKU (optional)</label>
                                <input type="text" id="productSku" name="productSku" placeholder="Product SKU" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:#f8fafc;">
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productImageUrl" style="font-weight:600; color:#e2e8f0;">Image URL (optional)</label>
                                <input type="url" id="productImageUrl" name="productImageUrl" placeholder="https://example.com/image.jpg" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:#f8fafc;">
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label for="productDescription" style="font-weight:600; color:#e2e8f0;">Description</label>
                            <textarea id="productDescription" name="productDescription" required rows="3" placeholder="Key features, materials, sizing, etc." style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:#f8fafc; resize:vertical;"></textarea>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:end;">
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <label for="productImage" style="font-weight:600; color:#e2e8f0;">Product Image (optional)</label>
                                <input type="file" id="productImage" name="productImage" accept="image/*" style="width:100%; color:#e2e8f0;">
                            </div>
                            <div style="display:flex; gap:10px; justify-content:flex-end;">
                                <button type="button" id="previewProductBtn" style="background:rgba(255,255,255,0.08); color:#e2e8f0; border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:10px 14px; font-size:0.95rem; font-weight:600; cursor:pointer; transition:all 0.2s;">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button type="submit" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#0b1020; border:none; border-radius:10px; padding:10px 18px; font-size:0.95rem; font-weight:700; cursor:pointer; box-shadow:0 12px 28px rgba(102,126,234,0.25);">
                                    <i class="fas fa-plus-circle"></i> Add Product
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="productPreview" style="display:none; margin-top:18px; padding:14px 12px; border:1px dashed rgba(255,255,255,0.18); border-radius:12px; background:rgba(255,255,255,0.03);">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px; color:#cbd5e1; font-weight:600;">
                            <i class="fas fa-clipboard-list"></i>
                            Live Preview
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px; color:#e2e8f0; font-size:0.95rem;">
                            <div><strong>Name:</strong> <span id="previewName">-</span></div>
                            <div><strong>Category:</strong> <span id="previewCategory">-</span></div>
                            <div><strong>Subcategory:</strong> <span id="previewSubcategory">-</span></div>
                            <div><strong>Price:</strong> <span id="previewPrice">-</span></div>
                            <div><strong>Stock:</strong> <span id="previewStock">-</span></div>
                            <div style="grid-column:1/-1;"><strong>Description:</strong> <span id="previewDescription">-</span></div>
                            <div style="grid-column:1/-1; display:flex; align-items:center; gap:12px;">
                                <strong>Image:</strong>
                                <div id="previewImageWrapper" style="display:flex; align-items:center; gap:12px;">
                                    <span id="previewImageFallback" style="color:#94a3b8;">No file selected</span>
                                    <img id="previewImagePreview" alt="Preview" style="display:none; width:120px; height:120px; object-fit:cover; border-radius:10px; border:1px solid rgba(255,255,255,0.12); box-shadow:0 8px 22px rgba(0,0,0,0.25);" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="toastContainer" style="position:fixed; right:18px; bottom:18px; display:flex; flex-direction:column; gap:10px; z-index:10000;"></div>
            <!-- Top Stats Row -->
            <div class="dashboard-stats" data-aos="zoom-in-up">
                <div class="stat-card-3d" style="cursor:pointer;" onclick="window.location.href='coupons.php'">
                    <div class="card-inner">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 0 30px rgba(102, 126, 234, 0.6);">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Total Sales</span>
                            <span class="stat-value">BDT <?php echo number_format($totalSales, 2); ?></span>
                            <span class="stat-change" style="color: #f59e0b;">
                                <i class="fas fa-info-circle"></i> All time revenue
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-3d">
                    <div class="card-inner">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 0 30px rgba(245, 87, 108, 0.6);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Customers</span>
                            <span class="stat-value"><?php echo number_format($totalCustomers); ?></span>
                            <span class="stat-change" style="color: #38bdf8;">
                                <i class="fas fa-user-check"></i> Unique buyers
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-3d">
                    <div class="card-inner">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 0 30px rgba(79, 172, 254, 0.6);">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Orders</span>
                            <span class="stat-value"><?php echo number_format($totalOrders); ?></span>
                            <span class="stat-change" style="color: #f59e0b;">
                                <i class="fas fa-box"></i> Total orders
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stat-card-3d">
                    <div class="card-inner">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); box-shadow: 0 0 30px rgba(250, 112, 154, 0.6);">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Products</span>
                            <span class="stat-value"><?php echo number_format($totalProducts); ?></span>
                            <span class="stat-change" style="color: #10b981;">
                                <i class="fas fa-layer-group"></i> Active listings
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-3d" style="cursor:pointer;" onclick="window.location.href='coupons.php'">
                    <div class="card-inner">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #38bdf8 0%, #22d3ee 100%); box-shadow: 0 0 30px rgba(56, 189, 248, 0.6);">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Coupon Requests</span>
                            <span class="stat-value"><?php echo number_format($pendingCouponRequests); ?></span>
                            <span class="stat-change" style="color: #38bdf8;">
                                <a href="coupons.php" style="color:inherit; text-decoration:none;">
                                    <i class="fas fa-arrow-right"></i> Review offers
                                </a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['success']) && in_array($_GET['success'], ['product_deleted', 'product_updated'], true)): ?>
                <div class="dashboard-section full-width" data-aos="zoom-in-up">
                    <div class="section-header">
                        <h3><i class="fas fa-check-circle"></i> Product <?php echo $_GET['success'] === 'product_deleted' ? 'Removed' : 'Updated'; ?></h3>
                    </div>
                    <div style="padding: 12px 16px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #bbf7d0;">
                        <?php if ($_GET['success'] === 'product_deleted'): ?>
                            Product deleted successfully.
                        <?php else: ?>
                            Product updated successfully.
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-section full-width">
                <div class="section-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <h3><i class="fas fa-boxes"></i> My Products <span class="dropdown-label"><?php echo number_format($totalProducts); ?> total</span></h3>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <div style="position:relative;">
                            <i class="fas fa-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:0.85rem;"></i>
                            <input
                                type="text"
                                id="sellerProductsSearch"
                                placeholder="Search products..."
                                style="padding:6px 10px 6px 28px; border-radius:999px; border:1px solid rgba(148,163,184,0.6); background:rgba(15,23,42,0.85); color:#e5e7eb; font-size:0.85rem; min-width:180px;"
                            >
                        </div>
                        <a href="my_products.php" class="view-all">View All</a>
                    </div>
                </div>
                <div class="table-container">
                    <table class="orders-table" id="sellerProductsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sellerProducts)): ?>
                                <tr class="table-row-hover">
                                    <td colspan="6">No products yet. Use "Add Product" to create your first listing.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sellerProducts as $product): ?>
                                    <tr class="table-row-hover">
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:42px; height:42px; border-radius:10px; object-fit:cover; border:1px solid rgba(255,255,255,0.12);">
                                                <?php else: ?>
                                                    <div style="width:42px; height:42px; border-radius:10px; background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div style="font-weight:600;"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div style="color:#94a3b8; font-size:0.85rem;">#<?php echo (int)$product['product_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['subcategory_name'] ?? '-'); ?></td>
                                        <td>BDT <?php echo number_format((float)$product['price'], 2); ?></td>
                                        <td><?php echo (int)($product['stock_qty'] ?? 0); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo product_status_badge_class($product['status'] ?? ''); ?>">
                                                <?php echo htmlspecialchars(ucfirst($product['status'] ?? 'inactive')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                <a href="edit_product.php?product_id=<?php echo (int)$product['product_id']; ?>&return=dashboard" style="padding:6px 10px; border-radius:8px; background:rgba(59,130,246,0.2); color:#bfdbfe; border:1px solid rgba(59,130,246,0.35); text-decoration:none;">
                                                    Edit
                                                </a>
                                                <a href="delete_product.php?product_id=<?php echo (int)$product['product_id']; ?>&return=dashboard" onclick="return confirm('Delete this product?');" style="padding:6px 10px; border-radius:8px; background:rgba(239,68,68,0.2); color:#fecaca; border:1px solid rgba(239,68,68,0.35); text-decoration:none;">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Main Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Monthly Revenue Table + Chart -->
                <div class="dashboard-section revenue-section" data-aos="zoom-in-up">
                    <div class="section-header">
                        <h3><i class="fas fa-calendar-alt"></i> Monthly Revenue <span class="dropdown-label"><?php echo (int)$currentYear; ?></span></h3>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div style="width:100%; max-width:100%; height:260px;">
                            <canvas id="monthlyRevenueChart"></canvas>
                        </div>
                        <div class="table-container">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Revenue</th>
                                        <th>Growth</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $prevRevenue = null;
                                    $hasRevenue = false;
                                    for ($m = 1; $m <= 12; $m++):
                                        $rev = $monthlyRevenue[$m];
                                        if ($rev > 0) { $hasRevenue = true; }
                                        if ($prevRevenue === null) {
                                            $growthLabel = '—';
                                            $growthColor = '#9ca3af';
                                        } elseif ($prevRevenue <= 0) {
                                            $growthLabel = '+∞';
                                            $growthColor = '#10b981';
                                        } else {
                                            $change = (($rev - $prevRevenue) / $prevRevenue) * 100.0;
                                            $growthLabel = sprintf('%+0.1f%%', $change);
                                            $growthColor = $change >= 0 ? '#10b981' : '#ef4444';
                                        }
                                        $statusText = $rev > 0 ? 'Completed' : 'No Sales';
                                        $statusClass = $rev > 0 ? 'status-received' : 'status-pending';
                                    ?>
                                        <tr class="table-row-hover">
                                            <td><?php echo htmlspecialchars($monthNames[$m]); ?></td>
                                            <td>BDT <?php echo number_format($rev, 2); ?></td>
                                            <td style="color: <?php echo $growthColor; ?>;">
                                                <?php echo htmlspecialchars($growthLabel); ?>
                                            </td>
                                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span></td>
                                        </tr>
                                    <?php
                                        $prevRevenue = $rev;
                                    endfor;
                                    if (!$hasRevenue): ?>
                                        <tr class="table-row-hover">
                                            <td colspan="4">No revenue data for this year yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Company Progress -->
                <div class="dashboard-section email-section" data-aos="zoom-in-up">
                    <div class="section-header">
                        <h3><i class="fas fa-building"></i> Company Progress</h3>
                    </div>
                    <div class="company-profile">
                        <div class="company-header">
                            <img src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/images/qmart.png" alt="Company Logo" class="company-logo">
                            <div class="company-info">
                                <h4 class="company-name"><?php echo htmlspecialchars($shopName !== '' ? $shopName : ($sellerName . ' Shop')); ?></h4>
                                <p class="seller-name"><?php echo htmlspecialchars($sellerName); ?></p>
                                <span class="company-badge"><?php echo $isVerified ? 'Verified Seller' : 'New Seller'; ?></span>
                            </div>
                        </div>
                        <div class="progress-stats">
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Order Fulfillment</span>
                                    <span class="progress-value"><?php echo $fulfillmentRate; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $fulfillmentRate; ?>%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Repeat Customers</span>
                                    <span class="progress-value"><?php echo $repeatRate; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $repeatRate; ?>%; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Stock Health</span>
                                    <span class="progress-value"><?php echo $stockHealth; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $stockHealth; ?>%; background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Delivery Progress</span>
                                    <span class="progress-value"><?php echo $deliveryRate; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $deliveryRate; ?>%; background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);"></div>
                                </div>
                            </div>
                        </div>
                        <div class="company-stats-grid">
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <div>
                                    <strong><?php echo $ratingScore > 0 ? number_format($ratingScore, 1) : 'N/A'; ?></strong>
                                    <span>Rating</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-box"></i>
                                <div>
                                    <strong><?php echo number_format($totalProducts); ?></strong>
                                    <span>Products</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-trophy"></i>
                                <div>
                                    <strong><?php echo $isVerified ? 'Verified' : 'Starter'; ?></strong>
                                    <span>Badge</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="dashboard-section full-width" data-aos="zoom-in-up">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Recent Orders <span class="dropdown-label">This Week</span></h3>
                </div>
                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Purchase On</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Tracking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr class="table-row-hover">
                                    <td colspan="6">No recent orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr class="table-row-hover">
                                        <td>#<?php echo (int)$order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['buyer_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['created_at']))); ?></td>
                                        <td>BDT <?php echo number_format((float)$order['amount'], 2); ?></td>
                                        <td><span class="status-badge <?php echo status_badge_class($order['status'] ?? ''); ?>"><?php echo htmlspecialchars(status_label($order['status'] ?? '')); ?></span></td>
                                        <td><a href="#" class="tracking-link">TRK-<?php echo (int)$order['order_id']; ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Delivery Progress -->
            <div class="dashboard-section delivery-section" data-aos="zoom-in-up">
                <div class="section-header">
                    <h3><i class="fas fa-truck"></i> Delivery <span class="dropdown-label">In Progress</span></h3>
                </div>
                <div class="delivery-list">
                    <?php if (empty($deliveryOrders)): ?>
                        <div class="delivery-item">
                            <div class="delivery-product">
                                <div class="product-icon" style="background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); box-shadow: 0 0 20px rgba(148, 163, 184, 0.5);">
                                    <i class="fas fa-box"></i>
                                </div>
                                <span class="product-name">No deliveries yet</span>
                            </div>
                            <div class="delivery-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%; background: linear-gradient(90deg, #94a3b8 0%, #64748b 100%);"></div>
                                </div>
                                <span class="progress-text">0%</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($deliveryOrders as $delivery): ?>
                            <?php $progress = delivery_progress($delivery['status'] ?? ''); ?>
                            <div class="delivery-item">
                                <div class="delivery-product">
                                    <div class="product-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <span class="product-name"><?php echo htmlspecialchars($delivery['product_name'] ?: ('Order #' . (int)$delivery['order_id'])); ?></span>
                                </div>
                                <div class="delivery-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo (int)$progress; ?>%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); box-shadow: 0 0 15px rgba(102, 126, 234, 0.6);"></div>
                                    </div>
                                    <span class="progress-text"><?php echo (int)$progress; ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer Container -->
        <div id="footerContainer" class="mt-8"></div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 });
        }

        function tryInitSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) {
                setTimeout(tryInitSidebar, 50);
                return;
            }
            if (typeof window.filterSidebarByRole === 'function') {
                window.filterSidebarByRole();
            }
            if (typeof window.initializeSidebar === 'function') {
                window.initializeSidebar();
            }
        }
        tryInitSidebar();

        // Load Footer
        async function loadFooter() {
            try {
                const response = await fetch('../html/footer.php');
                const html = await response.text();
                document.getElementById('footerContainer').innerHTML = html;
            } catch (error) {
                console.error('Error loading footer:', error);
            }
        }
        loadFooter();

        // Add Product Modal Logic
        const addProductBtn = document.getElementById('addProductBtn');
        const addProductModal = document.getElementById('addProductModal');
        const closeAddProductModal = document.getElementById('closeAddProductModal');
        const previewProductBtn = document.getElementById('previewProductBtn');
        const addProductForm = document.getElementById('addProductForm');
        const productPreview = document.getElementById('productPreview');
        const categorySelect = document.getElementById('productCategory');
        const subcategorySelect = document.getElementById('productSubcategory');
        const previewFields = {
            name: document.getElementById('previewName'),
            category: document.getElementById('previewCategory'),
            subcategory: document.getElementById('previewSubcategory'),
            price: document.getElementById('previewPrice'),
            stock: document.getElementById('previewStock'),
            description: document.getElementById('previewDescription'),
            imageFallback: document.getElementById('previewImageFallback'),
            imagePreview: document.getElementById('previewImagePreview')
        };

        let previewImageObjectUrl = null;
        const sellerVerified = <?php echo json_encode($isVerified); ?>;
        const showWelcomeToast = <?php echo json_encode($showWelcomeToast); ?>;
        const showVerifiedToast = <?php echo json_encode($showVerifiedToast); ?>;
        const showDeclinedToast = <?php echo json_encode($showDeclinedToast); ?>;
        const declineReason = <?php echo json_encode($declineReason); ?>;

        function showToast(message) {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) return;
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.background = 'linear-gradient(135deg,#22c55e 0%,#16a34a 100%)';
            toast.style.color = '#0b1020';
            toast.style.padding = '12px 14px';
            toast.style.borderRadius = '10px';
            toast.style.boxShadow = '0 10px 24px rgba(0,0,0,0.25)';
            toast.style.fontWeight = '700';
            toast.style.fontSize = '0.95rem';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            toastContainer.appendChild(toast);
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                setTimeout(() => toast.remove(), 200);
            }, 2400);
        }

        if (showWelcomeToast) {
            showToast('Welcome to QuickMart. Your seller account is ready for review.');
        }
        if (showVerifiedToast) {
            showToast('Congratulations! Your seller account is now verified.');
        }
        if (showDeclinedToast) {
            const message = declineReason
                ? `Verification declined: ${declineReason}`
                : 'Verification declined. Please review your details.';
            showToast(message);
        }

        function openModal() {
            addProductModal.style.display = 'flex';
            if (categorySelect && subcategorySelect) {
                categorySelect.value = '';
                updateSubcategoryOptions();
            }
        }

        function closeModal() {
            addProductModal.style.display = 'none';
            if (productPreview) productPreview.style.display = 'none';
            if (previewImageObjectUrl) {
                URL.revokeObjectURL(previewImageObjectUrl);
                previewImageObjectUrl = null;
            }
        }

        function updateSubcategoryOptions() {
            if (!categorySelect || !subcategorySelect) return;
            const selectedCategory = categorySelect.value;
            const options = Array.from(subcategorySelect.querySelectorAll('option'));
            options.forEach(option => {
                const catId = option.getAttribute('data-category');
                if (!catId) return;
                const shouldShow = selectedCategory !== '' && catId === selectedCategory;
                option.style.display = shouldShow ? 'block' : 'none';
            });
            const available = options.some(option => {
                const catId = option.getAttribute('data-category');
                return catId && catId === selectedCategory;
            });
            subcategorySelect.disabled = !available;
            subcategorySelect.value = '';
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', updateSubcategoryOptions);
        }

        function renderPreview() {
            if (!addProductForm || !productPreview) return;
            const formData = new FormData(addProductForm);
            const price = formData.get('productPrice');
            previewFields.name.textContent = formData.get('productName') || '-';
            const selectedCategoryOption = categorySelect ? categorySelect.options[categorySelect.selectedIndex] : null;
            const selectedOption = subcategorySelect ? subcategorySelect.options[subcategorySelect.selectedIndex] : null;
            const selectedCategory = selectedCategoryOption ? (selectedCategoryOption.textContent || '-') : '-';
            const selectedSubcategory = selectedOption ? (selectedOption.getAttribute('data-subcategory-name') || selectedOption.textContent || '-') : '-';
            previewFields.category.textContent = selectedCategory;
            previewFields.subcategory.textContent = selectedSubcategory;
            previewFields.price.textContent = price ? `BDT ${parseFloat(price).toFixed(2)}` : '-';
            previewFields.stock.textContent = formData.get('productStock') || '-';
            previewFields.description.textContent = formData.get('productDescription') || '-';
            const imageUrl = (formData.get('productImageUrl') || '').toString().trim();
            const file = formData.get('productImage');
            if (previewImageObjectUrl) {
                URL.revokeObjectURL(previewImageObjectUrl);
                previewImageObjectUrl = null;
            }
            if (imageUrl) {
                previewFields.imagePreview.src = imageUrl;
                previewFields.imagePreview.style.display = 'block';
                previewFields.imageFallback.style.display = 'none';
            } else if (file && file.name) {
                previewImageObjectUrl = URL.createObjectURL(file);
                previewFields.imagePreview.src = previewImageObjectUrl;
                previewFields.imagePreview.style.display = 'block';
                previewFields.imageFallback.style.display = 'none';
            } else {
                previewFields.imagePreview.style.display = 'none';
                previewFields.imageFallback.style.display = 'inline';
                previewFields.imageFallback.textContent = 'No file selected';
            }
            productPreview.style.display = 'block';
        }

        if (addProductBtn && addProductModal && closeAddProductModal) {
            addProductBtn.onclick = function() {
                if (!sellerVerified) {
                    showToast('Verification pending. Complete verification to add products.');
                    window.location.href = 'verify_seller.php';
                    return;
                }
                openModal();
            };
            closeAddProductModal.onclick = closeModal;
            addProductModal.onclick = function(e) {
                if (e.target === addProductModal) {
                    closeModal();
                }
            };
        }

        if (previewProductBtn) {
            previewProductBtn.onclick = function() {
                renderPreview();
            };
        }

        if (addProductForm) {
            addProductForm.onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(addProductForm);
                const payload = new FormData();
                payload.append('name', formData.get('productName') || '');
                payload.append('description', formData.get('productDescription') || '');
                payload.append('price', formData.get('productPrice') || '');
                payload.append('category_id', formData.get('productCategory') || '');
                payload.append('subcategory_id', formData.get('productSubcategory') || '');
                payload.append('stock_qty', formData.get('productStock') || '0');
                payload.append('sku', formData.get('productSku') || '');
                payload.append('image_url', formData.get('productImageUrl') || '');
                const file = formData.get('productImage');
                if (file && file.name) {
                    payload.append('product_image', file);
                }
                try {
                    const response = await fetch('../seller_dashboard/add_product.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: payload
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to add product');
                    }
                    showToast('Product added successfully');
                    closeModal();
                    addProductForm.reset();
                } catch (error) {
                    showToast(error.message || 'Failed to add product');
                }
            };
        }

        // Monthly revenue chart
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');
        if (monthlyRevenueCtx && window.Chart) {
            const monthlyRevenueLabels = <?php echo json_encode($monthlyLabels); ?>;
            const monthlyRevenueValues = <?php echo json_encode($monthlyValues); ?>;
            new Chart(monthlyRevenueCtx, {
                type: 'line',
                data: {
                    labels: monthlyRevenueLabels,
                    datasets: [{
                        label: 'Revenue (BDT)',
                        data: monthlyRevenueValues,
                        borderColor: 'rgba(59,130,246,1)',
                        backgroundColor: 'rgba(59,130,246,0.2)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(59,130,246,1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'BDT ' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: { color: '#9ca3af' }
                        }
                    }
                }
            });
        }

        // Seller products search (My Products table)
        const sellerProductsSearch = document.getElementById('sellerProductsSearch');
        const sellerProductsTable = document.getElementById('sellerProductsTable');
        if (sellerProductsSearch && sellerProductsTable) {
            const tableBody = sellerProductsTable.querySelector('tbody');
            const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];

            sellerProductsSearch.addEventListener('input', function () {
                const term = (this.value || '').toString().toLowerCase().trim();
                rows.forEach(function (row) {
                    const cells = row.querySelectorAll('td');

                    // Placeholder / empty-state row
                    if (!cells.length || cells.length < 3) {
                        row.style.display = term === '' ? '' : 'none';
                        return;
                    }

                    var textParts = [];
                    for (var i = 0; i < cells.length; i++) {
                        textParts.push((cells[i].innerText || '').toString().toLowerCase());
                    }
                    var rowText = textParts.join(' ');

                    row.style.display = term === '' || rowText.indexOf(term) !== -1 ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
