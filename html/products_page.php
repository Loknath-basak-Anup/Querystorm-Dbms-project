<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/coupons.php";
require_once __DIR__ . "/../includes/notifications.php";
ensure_coupon_tables();

// Local fallback so images still render when external placeholders are blocked
if (!defined('PLACEHOLDER_IMAGE')) {
    define('PLACEHOLDER_IMAGE', '/QuickMart/images/placeholder.svg');
}

function normalize_image_url(?string $url): string {
    $url = trim((string)$url);

    // Empty or missing image gets the local placeholder
    if ($url === '') {
        return PLACEHOLDER_IMAGE;
    }

    $lower = strtolower($url);

    // Rewrite any via.placeholder.com or bare "600x600?text=..." urls to local
    if (strpos($lower, 'via.placeholder.com') !== false || preg_match('/^\d+x\d+\?text=.+$/', $lower)) {
        return PLACEHOLDER_IMAGE;
    }

    if (preg_match('#^https?://#i', $url) || strncmp($url, '//', 2) === 0) {
        return $url;
    }
    if ($url[0] === '/') {
        return $url;
    }

    // Default to absolute path using BASE_URL if provided
    if (defined('BASE_URL')) {
        return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    }

    return PLACEHOLDER_IMAGE;
}

// Fetch banners from DB
$banners = db_fetch_all(
    "SELECT * FROM banners 
     WHERE is_active = 1 AND position = 'products_top' 
     ORDER BY sort_order ASC"
);
foreach ($banners as $index => $banner) {
    $banners[$index]['image_url'] = normalize_image_url($banner['image_url'] ?? '');
}

// Fetch all categories and subcategories
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");
$top_categories = db_fetch_all("
    SELECT c.category_id, c.name, c.icon_url, c.icon_class, COUNT(p.product_id) AS total
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.category_id AND p.status = 'active'
    GROUP BY c.category_id
    ORDER BY total DESC, c.name ASC
    LIMIT 5
");
$subcategories = db_fetch_all("
    SELECT s.subcategory_id, s.name, c.name AS category_name
    FROM subcategories s
    JOIN categories c ON s.category_id = c.category_id
    ORDER BY c.name ASC, s.name ASC
");

// Fetch all products with images and stock
$products = db_fetch_all("
    SELECT 
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.status,
        c.name as category_name,
        sc.name as subcategory_name,
        u.full_name as seller_name,
        i.stock_qty,
        (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image_url
    FROM products p
    INNER JOIN categories c ON p.category_id = c.category_id
    INNER JOIN subcategories sc ON p.subcategory_id = sc.subcategory_id
    INNER JOIN seller_profiles sp ON p.seller_id = sp.seller_id
    INNER JOIN users u ON sp.seller_id = u.user_id
    LEFT JOIN inventory i ON p.product_id = i.product_id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
");
foreach ($products as $index => $product) {
    $products[$index]['image_url'] = normalize_image_url($product['image_url'] ?? '');
}

$couponRows = db_fetch_all(
    "SELECT ca.product_id, c.coupon_id, c.code, c.discount_type, c.discount_value, c.min_purchase, c.max_discount
     FROM coupons c
     INNER JOIN coupon_product_allow ca ON ca.coupon_id = c.coupon_id
     INNER JOIN coupon_seller_requests csr ON csr.coupon_id = c.coupon_id AND csr.seller_id = ca.seller_id
     WHERE c.is_active = 1 AND c.is_published = 1
       AND csr.status = 'approved'
       AND (c.starts_at IS NULL OR c.starts_at <= NOW())
       AND (c.ends_at IS NULL OR c.ends_at >= NOW())"
);

$productCoupons = [];
foreach ($couponRows as $row) {
    $pid = (int)$row['product_id'];
    if (!isset($productCoupons[$pid])) {
        $productCoupons[$pid] = [];
    }
    $productCoupons[$pid][] = [
        'code' => $row['code'],
        'discount_type' => $row['discount_type'],
        'discount_value' => (float)$row['discount_value'],
        'min_purchase' => (float)($row['min_purchase'] ?? 0),
        'max_discount' => $row['max_discount'] !== null ? (float)$row['max_discount'] : null
    ];
}

$walletBalance = 0.0;
$notificationCount = 0;
$isLoggedIn = is_logged_in();
$userRole = $isLoggedIn ? get_user_role() : null;
$userId = $isLoggedIn ? (get_user_id() ?? 0) : 0;
$pageNotifications = [];
$userImageUrl = '';

if ($isLoggedIn && $userId > 0) {
    ensure_notifications_table();
    $pageNotifications = fetch_notifications($userId, 8);
    try {
        $imageRow = db_fetch("SELECT profile_image_url FROM users WHERE user_id = ?", [$userId]);
        $userImageUrl = (string)($imageRow['profile_image_url'] ?? '');
    } catch (Throwable $e) {
        $userImageUrl = '';
    }
    try {
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
            [$userId]
        );
        $walletBalance = (float)($walletRow['balance'] ?? 0);
        if ($walletBalance < 0) {
            $walletBalance = 0;
        }
    } catch (Throwable $e) {
        $walletBalance = 0;
    }

    try {
        if (empty($pageNotifications) && $userRole === 'buyer') {
            $notifRow = db_fetch(
                "SELECT COUNT(*) AS total
                 FROM orders
                 WHERE buyer_id = ?
                   AND status IN ('pending','processing','shipped','in_transit','out_for_delivery')",
                [$userId]
            );
            $notificationCount = (int)($notifRow['total'] ?? 0);
        } elseif (empty($pageNotifications) && $userRole === 'seller') {
            $notifRow = db_fetch(
                "SELECT COUNT(DISTINCT o.order_id) AS total
                 FROM orders o
                 INNER JOIN order_items oi ON oi.order_id = o.order_id
                 INNER JOIN products p ON p.product_id = oi.product_id
                 WHERE p.seller_id = ?
                   AND o.status IN ('pending','processing','shipped','in_transit','out_for_delivery')",
                [$userId]
            );
            $notificationCount = (int)($notifRow['total'] ?? 0);
        }
    } catch (Throwable $e) {
        $notificationCount = 0;
    }
    if (!empty($pageNotifications)) {
        $notificationCount = count($pageNotifications);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover Products | QuickMart</title>
    <meta name="app-base" content="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/products_page.css">
    
    <style>
        /* Responsive sidebar layout with footer at bottom */
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
    <!-- Sidebar Container - Will be loaded dynamically -->
    <div id="sidebarContainer"></div>

    <!-- Top Toast Notification -->
    <div class="top-toast" id="topToast">
        <div class="toast-content">
            <div class="toast-message">
                <i class="fas fa-info-circle"></i>
                <span>New to QuickMart? Join us today! Want to sell something?</span>
            </div>
            <div class="toast-actions">
                <button class="toast-btn buyer-btn" onclick="window.location.href='../html/login.php'">
                    <i class="fas fa-shopping-bag"></i> Buyer Sign In
                </button>
                <button class="toast-btn seller-btn" onclick="window.location.href='../html/login.php'">
                    <i class="fas fa-store"></i> Seller Sign In
                </button>
                <button class="toast-close" id="closeToast">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <button class="btn-icon menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search items, collections, and users" id="searchInput">
            </div>
            
            <div class="header-actions">
                <div class="wallet-info">
                    <i class="fas fa-wallet"></i>
                    <span id="walletBalance"><?php echo number_format($walletBalance, 2); ?> BDT</span>
                </div>
                
                <button class="btn-icon" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo (int)$notificationCount; ?></span>
                </button>

                <button class="btn-icon" id="cartBtn">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge-header" id="cartCountHeader">0</span>
                </button>

                <button class="btn-icon dashboard-nav-btn" id="dashboardBtn" title="Dashboard" style="display: none;">
                    <i class="fas fa-gauge"></i>
                </button>
                
                <button class="btn-icon theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                
                <div class="user-menu" id="userMenu">
                    <img src="<?php echo htmlspecialchars($userImageUrl !== '' ? $userImageUrl : 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png'); ?>" alt="User" class="user-avatar-small">
                    <a class="user-menu-label" id="userMenuLabel" href="../html/login.php" style="font-weight:600;font-size:0.85rem;color:var(--text-primary);text-decoration:none;">Login</a>
                    <i class="fas fa-chevron-down"></i>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="../html/login.php" class="dropdown-item">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="../html/login.php#signup" class="dropdown-item">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Notification Modal -->
        <div id="notificationModal" class="notification-modal" style="display: none;">
            <div class="notification-modal-content">
                <div class="notification-modal-header">
                    <h3>Notifications</h3>
                    <button class="close-notification-modal" id="closeNotificationBtn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="notification-modal-body" id="notificationList"></div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Banner Carousel -->
            <div class="banner-carousel">
                <div class="carousel-container">
                    <?php if (!empty($banners)): ?>
                        <?php foreach ($banners as $index => $banner): ?>
                            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                <?php if (!empty($banner['link_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($banner['link_url']); ?>">
                                        <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                                    </a>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default banners if none in DB -->
                        <div class="carousel-slide active">
                            <img src="/QuickMart/images/banners/molla-soap.png" alt="Banner 1">
                        </div>
                        <div class="carousel-slide">
                            <img src="/QuickMart/images/banners/rflbulti.png" alt="Banner 2">
                        </div>
                    <?php endif; ?>
                </div>
                <button class="carousel-btn prev" id="carouselPrev"><i class="fas fa-chevron-left"></i></button>
                <button class="carousel-btn next" id="carouselNext"><i class="fas fa-chevron-right"></i></button>
                <div class="carousel-dots" id="carouselDots"></div>
            </div>

            <h1 class="page-title">All Products</h1>
            
            <!-- Categories Section -->
            <div class="categories-section">
                <div class="categories-scroll">
                    <div class="category-item" data-category="all">
                        <div class="category-icon-wrapper">
                            <i class="fas fa-th"></i>
                        </div>
                        <span>All</span>
                    </div>
                    <?php foreach ($top_categories as $category): ?>
                        <div class="category-item" data-category="<?php echo htmlspecialchars(strtolower($category['name'])); ?>">
                            <div class="category-icon-wrapper">
                                <?php if (!empty($category['icon_class'])): ?>
                                    <i class="<?php echo htmlspecialchars($category['icon_class']); ?>"></i>
                                <?php elseif (!empty($category['icon_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width:22px; height:22px; object-fit:cover; border-radius:6px;">
                                <?php else: ?>
                                    <i class="fas fa-hashtag"></i>
                                <?php endif; ?>
                            </div>
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab" id="categoryBtn">
                    <i class="fas fa-th"></i>
                    <span>Category</span>
                </button>
                <button class="filter-tab" id="priceRangeBtn">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Price Range</span>
                </button>
                
                <button class="filter-tab sort-btn" id="filterSortBtn">
                    <i class="fas fa-filter"></i>
                    <span>Filter & Sort</span>
                </button>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productsGrid" data-aos="fade-up">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-aos="zoom-in-up" data-product-id="<?php echo $product['product_id']; ?>" style="cursor:pointer">
                        <div class="product-image-container">
                               <img src="<?php echo htmlspecialchars($product['image_url'] ?: PLACEHOLDER_IMAGE); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                            <?php if ($product['stock_qty'] <= 0): ?>
                                <div class="stock-overlay"></div>
                                <div class="stock-ribbon">Stock Out</div>
                            <?php endif; ?>
                            <button class="product-favorite" onclick="toggleFavorite(<?php echo $product['product_id']; ?>)">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-creator">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                            <div class="product-footer">
                                <div class="product-price">
                                    <span class="price-label">Current Price</span>
                                    <div class="price-value">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo number_format($product['price'], 2); ?> BDT</span>
                                    </div>
                                </div>
                                <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                        <?php echo $product['stock_qty'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>Add to Cart</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Load More Button -->
            <div class="load-more-container">
                <button class="btn-load-more" id="loadMoreBtn">Load More</button>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods">
                <p class="payment-text">We Accept</p>
                <div class="payment-logos">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png" alt="Visa" class="payment-logo">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" class="payment-logo">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/2560px-PayPal.svg.png" alt="PayPal" class="payment-logo">
                    <img src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/images/Amazon_Pay-Logo.wine.svg" alt="American Express" class="payment-logo">
                    <img src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/images/bkash.png" alt="bKash" class="payment-logo">
                    <img src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/images/Nagad-Logo.wine.svg" alt="Nagad" class="payment-logo">
                </div>
            </div>
        </div>

        <!-- Footer Container -->
        <div id="footerContainer" class="mt-8"></div>
    </main>

    <!-- Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Category</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-grid">
                    <div class="category-card" data-category="all">
                        <i class="fas fa-layer-group"></i>
                        <span>All Products</span>
                    </div>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card" data-category="<?php echo htmlspecialchars(strtolower($category['name'])); ?>">
                            <?php if (!empty($category['icon_class'])): ?>
                                <i class="<?php echo htmlspecialchars($category['icon_class']); ?>"></i>
                            <?php elseif (!empty($category['icon_url'])): ?>
                                <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width:22px; height:22px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <i class="fas fa-tag"></i>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Price Range Modal -->
    <div class="modal" id="priceRangeModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Price Range</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="price-range-selector">
                    <div class="price-inputs">
                        <div class="price-input-group">
                            <label>Min Price (BDT)</label>
                            <input type="number" id="minPrice" placeholder="0" min="0">
                        </div>
                        <div class="price-input-group">
                            <label>Max Price (BDT)</label>
                            <input type="number" id="maxPrice" placeholder="100000" min="0">
                        </div>
                    </div>
                    <div class="price-range-slider">
                        <input type="range" id="priceSlider" min="0" max="100000" value="50000" step="500">
                        <div class="slider-value">Max: <span id="sliderValue">50000</span> BDT</div>
                    </div>
                    <button class="btn-apply" id="applyPriceRange">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Sort Modal -->
    <div class="modal" id="filterSortModal">
        <div class="modal-overlay"></div>
        <div class="modal-content" style="max-width:500px">
            <div class="modal-header">
                <h3><i class="fas fa-sliders-h"></i> Filter & Sort</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" style="padding:2rem">
                <div class="filter-section-modern">
                    <h4 style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.25rem;font-size:1.1rem;color:var(--text-primary)">
                        <i class="fas fa-sort" style="color:var(--primary-color)"></i>
                        Sort By
                    </h4>
                    <div class="filter-options-modern">
                        <label class="filter-option-modern">
                            <input type="radio" name="sort" value="newest" checked>
                            <div class="option-content">
                                <i class="fas fa-clock"></i>
                                <span>Newest First</span>
                            </div>
                            <div class="radio-check"></div>
                        </label>
                        <label class="filter-option-modern">
                            <input type="radio" name="sort" value="oldest">
                            <div class="option-content">
                                <i class="fas fa-history"></i>
                                <span>Oldest First</span>
                            </div>
                            <div class="radio-check"></div>
                        </label>
                        <label class="filter-option-modern">
                            <input type="radio" name="sort" value="price-low">
                            <div class="option-content">
                                <i class="fas fa-arrow-down"></i>
                                <span>Price: Low to High</span>
                            </div>
                            <div class="radio-check"></div>
                        </label>
                        <label class="filter-option-modern">
                            <input type="radio" name="sort" value="price-high">
                            <div class="option-content">
                                <i class="fas fa-arrow-up"></i>
                                <span>Price: High to Low</span>
                            </div>
                            <div class="radio-check"></div>
                        </label>
                        <label class="filter-option-modern">
                            <input type="radio" name="sort" value="popular">
                            <div class="option-content">
                                <i class="fas fa-fire"></i>
                                <span>Most Popular</span>
                            </div>
                            <div class="radio-check"></div>
                        </label>
                    </div>
                </div>
                <div class="filter-section-modern" style="margin-top:2rem">
                    <h4 style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.25rem;font-size:1.1rem;color:var(--text-primary)">
                        <i class="fas fa-filter" style="color:var(--success-color)"></i>
                        Availability
                    </h4>
                    <div class="filter-options-modern">
                        <label class="filter-option-modern checkbox-option">
                            <input type="checkbox" id="inStock" checked>
                            <div class="option-content">
                                <i class="fas fa-check-circle"></i>
                                <span>In Stock Only</span>
                            </div>
                            <div class="checkbox-check"></div>
                        </label>
                        <label class="filter-option-modern checkbox-option">
                            <input type="checkbox" id="onSale">
                            <div class="option-content">
                                <i class="fas fa-tag"></i>
                                <span>On Sale</span>
                            </div>
                            <div class="checkbox-check"></div>
                        </label>
                    </div>
                </div>
                <button class="btn-apply-modern" id="applyFilters">
                    <i class="fas fa-check"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3>Shopping Cart</h3>
            <button class="cart-close" id="closeCart">&times;</button>
        </div>
        <div class="cart-items" id="cartItems">
            <p class="empty-cart">Your cart is empty</p>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cartTotal">0 BDT</span>
            </div>
            <button class="btn-checkout" id="cartCheckoutBtn">Checkout</button>
        </div>
    </div>

    <!-- Live Chat Icon -->
    <div class="chat-icon" id="chatIcon">
        <i class="fas fa-comments"></i>
        <span class="chat-badge">1</span>
    </div>

    <!-- Chat Modal -->
    <div class="modal" id="chatModal">
        <div class="modal-overlay"></div>
        <div class="modal-content chat-modal-content">
            <div class="modal-header">
                <div class="chat-header-info">
                    <img src="https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png" alt="Support" class="chat-avatar">
                    <div>
                        <h3>Customer Support</h3>
                        <span class="chat-status"><i class="fas fa-circle"></i> Online</span>
                    </div>
                </div>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body chat-body" id="chatBody">
                <div class="chat-message received">
                    <div class="message-content">
                        <p>Hello! Welcome to QuickMart. How can I help you today?</p>
                        <span class="message-time">Just now</span>
                    </div>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" placeholder="Type your message..." id="chatInput">
                <button class="btn-send" id="sendChatBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- Guest User Modal -->
    <div class="modal" id="guestModal">
        <div class="modal-overlay"></div>
        <div class="modal-content guest-modal-content">
            <div class="modal-header">
                <h3>Sign In Required</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="guest-modal-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h4>Please Sign In to Continue</h4>
                <p>You need to be logged in to access this feature. Sign in now or create a new account to get started!</p>
                <div class="guest-modal-actions">
                    <a href="../html/login.php" class="btn-primary guest-btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                    <a href="../html/login.php#signup" class="btn-secondary guest-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                </div>
                <p class="guest-modal-footer">Join QuickMart as a <a href="../buyer/signup.php">Buyer</a> or <a href="../seller/signup.php">Seller</a></p>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div class="modal" id="productDetailsModal">
        <div class="modal-overlay"></div>
        <div class="modal-content" style="max-width:900px;max-height:90vh;overflow-y:auto">
            <div class="modal-header">
                <h3>Product Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="productDetailsBody">
                <!-- Product details will be dynamically loaded here -->
            </div>
        </div>
    </div>

    <script>
        <?php if ($isLoggedIn): ?>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userEmail', <?php echo json_encode($_SESSION['email'] ?? ''); ?>);
        localStorage.setItem('userRole', <?php echo json_encode($userRole); ?>);
        localStorage.setItem('userName', <?php echo json_encode($_SESSION['full_name'] ?? ''); ?>);
        localStorage.setItem('userImage', <?php echo json_encode($userImageUrl); ?>);
        <?php else: ?>
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userRole');
        localStorage.removeItem('userName');
        localStorage.removeItem('userImage');
        <?php endif; ?>
    </script>

    <script>
        window.quickmartSession = {
            loggedIn: <?php echo json_encode($isLoggedIn); ?>,
            role: <?php echo json_encode($userRole); ?>
        };
        window.navNotifications = <?php echo json_encode($pageNotifications); ?>;
    </script>

    <!-- Custom JS -->
    <script src="../assets/js/products_page.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Load Footer
        async function loadFooter() {
            try {
                const response = await fetch('./footer.php');
                const html = await response.text();
                document.getElementById('footerContainer').innerHTML = html;
            } catch (error) {
                console.error('Error loading footer:', error);
            }
        }
        loadFooter();

        function getAppBasePath() {
            const parts = window.location.pathname.split('/').filter(Boolean);
            return parts.length > 0 ? '/' + parts[0] : '';
        }

        window.logout = function() {
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userRole');
            localStorage.removeItem('userName');
            localStorage.removeItem('userImage');
            const basePath = getAppBasePath();
            window.location.href = basePath + '/actions/logout.php';
        };

        window.setupNotificationModal = function() {
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationModal = document.getElementById('notificationModal');
            const closeNotificationBtn = document.getElementById('closeNotificationBtn');
            const notificationList = document.getElementById('notificationList');
            if (!notificationBtn || !notificationModal) return;

            notificationBtn.onclick = function(e) {
                e.stopPropagation();
                const userRole = localStorage.getItem('userRole');
                const notifications = getNotifications(userRole);
                displayNotifications(notifications, notificationList);
                notificationModal.style.display = notificationModal.style.display === 'none' ? 'flex' : 'none';
            };

            if (closeNotificationBtn) {
                closeNotificationBtn.onclick = function() {
                    notificationModal.style.display = 'none';
                };
            }

            notificationModal.onclick = function(e) {
                if (e.target === notificationModal) {
                    notificationModal.style.display = 'none';
                }
            };
        };

        function getNotifications(userRole) {
            const rows = Array.isArray(window.navNotifications) ? window.navNotifications : [];
            if (rows.length === 0) return [];
            if (rows[0] && rows[0].title && rows[0].message) {
                return rows.map((row, index) => ({
                    id: row.notification_id || index + 1,
                    title: row.title,
                    message: row.message,
                    action_url: row.action_url || '',
                    time: new Date(row.created_at).toLocaleString(),
                    icon: row.type === 'delivery' ? 'fa-truck-fast' : 'fa-bell',
                    color: row.type === 'delivery' ? '#f59e0b' : '#3b82f6'
                }));
            }
            if (userRole === 'seller') {
                return rows.map((row, index) => ({
                    id: index + 1,
                    title: 'Product Sold',
                    message: `${row.buyer_name} bought ${row.product_name} (ID ${row.product_id}) in order #${row.order_id}`,
                    time: new Date(row.created_at).toLocaleString(),
                    icon: 'fa-shopping-bag',
                    color: '#3b82f6'
                }));
            }
            return rows.map((row, index) => ({
                id: index + 1,
                title: `Order #${row.order_id}`,
                message: `Status: ${row.status} · Total ${Number(row.total_amount || 0).toLocaleString()} BDT`,
                time: new Date(row.created_at).toLocaleString(),
                icon: 'fa-receipt',
                color: '#10b981'
            }));
        }

        function displayNotifications(notifications, container) {
            if (!container) return;
            if (!notifications.length) {
                container.innerHTML = '<div class="no-notifications"><i class="fas fa-inbox"></i><p>No notifications</p></div>';
                return;
            }
            container.innerHTML = notifications.map(notif => `
                <div class="notification-item" style="border-left: 4px solid ${notif.color};">
                    <div class="notification-icon" style="background: ${notif.color}20;">
                        <i class="fas ${notif.icon}" style="color: ${notif.color};"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.title}</div>
                        <div class="notification-message">${notif.message}</div>
                        <div class="notification-time">${notif.time}</div>
                    </div>
                    ${notif.action_url ? `
                        <button class="notification-action" onclick="window.location.href='${notif.action_url}'">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    ` : `
                        <button class="notification-action">
                            <i class="fas fa-check"></i>
                        </button>
                    `}
                </div>
            `).join('');
        }

        // Initialize user menu for products page (has inline navbar)
        document.addEventListener('DOMContentLoaded', () => {
            const userMenu = document.getElementById('userMenu');
            const userDropdown = document.getElementById('userDropdown');
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            const userEmail = localStorage.getItem('userEmail');
            const userRole = localStorage.getItem('userRole');
            const userName = localStorage.getItem('userName') || userEmail;
            const userImage = localStorage.getItem('userImage') || 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png';
            const dashboardBtn = document.getElementById('dashboardBtn');
            const userMenuLabel = document.getElementById('userMenuLabel');
            
            // Hide cart button for sellers
            const cartBtn = document.getElementById('cartBtn');
            if (cartBtn && userRole === 'seller') {
                cartBtn.style.display = 'none';
            } else if (cartBtn) {
                cartBtn.style.display = 'flex';
            }

            if (dashboardBtn && (userRole === 'buyer' || userRole === 'seller')) {
                dashboardBtn.style.display = 'flex';
                dashboardBtn.onclick = () => {
                    window.location.href = userRole === 'seller'
                        ? '../seller_dashboard/seller_dashboard.php'
                        : '../buyer_dashboard/buyer_dashboard.php';
                };
            } else if (dashboardBtn) {
                dashboardBtn.style.display = 'none';
            }
            
            if (isLoggedIn && userEmail && userMenu && userDropdown) {
                if (userMenuLabel) {
                    userMenuLabel.style.display = 'none';
                }
                // Update avatar
                const userAvatar = userMenu.querySelector('.user-avatar-small');
                if (userAvatar) {
                    userAvatar.src = userImage;
                }
                
                // Update dropdown content
                userDropdown.innerHTML = `
                    <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; display: flex; align-items: center; gap: 0.75rem;">
                        <img src="${userImage}" alt="User" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">${userName}</div>
                            <div style="color: var(--text-secondary); font-size: 0.8rem;">${userRole === 'seller' ? '<i class="fa-solid fa-truck-fast"></i> Seller' : '<i class="fa-solid fa-basket-shopping"></i> Buyer'}</div>
                        </div>
                    </div>
                    <a href="../${userRole}/profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    ${userRole === 'buyer' ? `
                        <a href="../buyer_dashboard/wallet.php" class="dropdown-item">
                            <i class="fas fa-wallet"></i> My Wallet
                        </a>
                        <a href="../buyer_dashboard/cart.php" class="dropdown-item">
                            <i class="fas fa-shopping-cart"></i> My Cart
                        </a>
                    ` : `
                        <a href="../seller_dashboard/wallet.php" class="dropdown-item">
                            <i class="fas fa-dollar-sign"></i> Earnings
                        </a>
                        <a href="../seller_dashboard/history.php" class="dropdown-item">
                            <i class="fas fa-list"></i> Sales History
                        </a>
                    `}
                    <a href="../${userRole}_dashboard/${userRole}_dashboard.php" class="dropdown-item">
                        <i class="fas fa-gauge"></i> Dashboard
                    </a>
                    <a href="../${userRole}_dashboard/settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <div style="border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">
                        <a href="#" onclick="window.logout(); return false;" class="dropdown-item" style="color: #ef4444;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                `;
                
                // Setup dropdown toggle
                userMenu.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isVisible = userDropdown.style.display === 'block';
                    userDropdown.style.display = isVisible ? 'none' : 'block';
                    userDropdown.style.opacity = isVisible ? '0' : '1';
                    userDropdown.style.visibility = isVisible ? 'hidden' : 'visible';
                });
                
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.user-menu')) {
                        userDropdown.style.display = 'none';
                        userDropdown.style.opacity = '0';
                        userDropdown.style.visibility = 'hidden';
                    }
                });
            }
            if (!isLoggedIn && userDropdown) {
                if (userMenuLabel) {
                    userMenuLabel.textContent = 'Login';
                    userMenuLabel.style.display = 'inline-flex';
                }
                userDropdown.innerHTML = `
                    <a href="../html/login.php" class="dropdown-item">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="../html/login.php#signup" class="dropdown-item">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                `;
            }
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            if (typeof renderCartItems === 'function') {
                renderCartItems();
            }

            if (typeof setupNotificationModal === 'function') {
                setupNotificationModal();
            }
        });
    </script>
    
    <!-- Pass PHP products data to JavaScript -->
    <script>
        // Products data from database
        const placeholderImage = '<?php echo PLACEHOLDER_IMAGE; ?>';
        const dbProducts = <?php echo json_encode($products); ?>;
        
        // Transform DB products to match JS format
        window.products = dbProducts.map(p => ({
            id: p.product_id,
            name: p.name,
            creator: p.seller_name,
            price: parseFloat(p.price),
            image: p.image_url || placeholderImage,
            category: p.category_name,
            subcategory: p.subcategory_name,
            description: p.description || '',
            inStock: p.stock_qty > 0,
            stock: p.stock_qty || 0,
            onSale: false,
            salePercent: 0
        }));

        window.productCoupons = <?php echo json_encode($productCoupons); ?>;
        
        console.log('Loaded ' + window.products.length + ' products from database');
        
    </script>

</body>
</html>
