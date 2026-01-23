<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifications.php';
$navLoggedIn = is_logged_in();
$navRole = $navLoggedIn ? get_user_role() : null;
$navEmail = $navLoggedIn ? ($_SESSION['email'] ?? '') : '';
$navName = $navLoggedIn ? ($_SESSION['full_name'] ?? '') : '';
$navWalletBalance = 0.0;
$navNotifications = [];
$navNotificationCount = 0;
$navProfileImage = "";

if ($navLoggedIn) {
    ensure_notifications_table();
    $userId = get_user_id() ?? 0;
    if ($userId > 0) {
        $profileRow = db_fetch("SELECT profile_image_url FROM users WHERE user_id = ?", [$userId]);
        $navProfileImage = $profileRow['profile_image_url'] ?? '';

        $navNotifications = fetch_notifications($userId, 8);
        if (empty($navNotifications) && $navRole === 'buyer') {
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
            $navWalletBalance = (float)($walletRow['balance'] ?? 0);
            if ($navWalletBalance < 0) $navWalletBalance = 0;

            $navNotifications = db_fetch_all(
                "SELECT o.order_id, o.status, o.created_at,
                        SUM(oi.price * oi.quantity) AS total_amount
                 FROM orders o
                 INNER JOIN order_items oi ON oi.order_id = o.order_id
                 WHERE o.buyer_id = ?
                 GROUP BY o.order_id, o.status, o.created_at
                 ORDER BY o.created_at DESC
                 LIMIT 5",
                [$userId]
            );
        } elseif (empty($navNotifications) && $navRole === 'seller') {
            $navNotifications = db_fetch_all(
                "SELECT o.order_id, o.created_at, p.product_id, p.name AS product_name,
                        u.full_name AS buyer_name
                 FROM orders o
                 INNER JOIN order_items oi ON oi.order_id = o.order_id
                 INNER JOIN products p ON p.product_id = oi.product_id
                 INNER JOIN users u ON u.user_id = o.buyer_id
                 WHERE p.seller_id = ?
                 ORDER BY o.created_at DESC
                 LIMIT 5",
                [$userId]
            );
        }
        $navNotificationCount = is_array($navNotifications) ? count($navNotifications) : 0;
    }
}
?>
<header class="top-header" style="background:var(--bg-secondary)">
    <button class="btn-icon menu-toggle" id="menuToggle" style="display:flex">
        <i class="fas fa-bars"></i>
    </button>
    <h2 class="page-title-navbar" style="font-weight:700;flex:1"></h2>
    <div class="header-actions">
        <?php if ($navLoggedIn && $navRole === 'buyer'): ?>
            <div class="wallet-info" style="margin-right: 0.5rem;">
                <i class="fas fa-wallet"></i>
                <span id="walletBalance"><?php echo number_format($navWalletBalance, 2); ?> BDT</span>
            </div>
        <?php endif; ?>
        <button class="btn-icon" id="notificationBtn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge"><?php echo (int)$navNotificationCount; ?></span>
        </button>

        <button class="btn-icon" id="cartBtn" title="Shopping Cart">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-badge-header" id="cartCountHeader">0</span>
        </button>

        <button class="btn-icon dashboard-nav-btn" id="dashboardBtn" title="Dashboard" style="display: none;">
            <i class="fas fa-gauge"></i>
        </button>
        
        <button class="btn-icon theme-toggle" id="themeToggle" title="Toggle Theme">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="user-menu" id="userMenu" style="position: relative; cursor: pointer;">
            <img src="<?php echo htmlspecialchars($navProfileImage !== '' ? $navProfileImage : 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png'); ?>" alt="User" class="user-avatar-small">
            <i class="fas fa-chevron-down"></i>
            <div class="user-dropdown" id="userDropdown" style="display: none;">
                <!-- Populated by JavaScript -->
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
        <div class="notification-modal-body" id="notificationList">
            <!-- Notifications will be populated here -->
        </div>
    </div>
</div>

<script>
    window.quickmartSession = {
        loggedIn: <?php echo json_encode($navLoggedIn); ?>,
        role: <?php echo json_encode($navRole); ?>,
        email: <?php echo json_encode($navEmail); ?>,
        name: <?php echo json_encode($navName); ?>
    };
    window.navNotifications = <?php echo json_encode($navNotifications); ?>;

    if (window.quickmartSession.loggedIn) {
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', window.quickmartSession.role || '');
        localStorage.setItem('userEmail', window.quickmartSession.email || '');
        localStorage.setItem('userName', window.quickmartSession.name || '');
        localStorage.setItem('userImage', <?php echo json_encode($navProfileImage); ?>);
    } else {
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('userRole');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userName');
        localStorage.removeItem('userImage');
    }

    function getCartApiUrl() {
        const path = window.location.pathname;
        const lower = path.toLowerCase();
        const marker = '/quickmart/';
        const idx = lower.indexOf(marker);
        if (idx !== -1) {
            const base = path.substring(0, idx + marker.length);
            return base + 'buyer_dashboard/cart_action.php';
        }
        if (path.includes('/buyer_dashboard/')) return 'cart_action.php';
        if (path.includes('/html/')) return '../buyer_dashboard/cart_action.php';
        return 'buyer_dashboard/cart_action.php';
    }

    function refreshNavbarCartCount() {
        if (!window.quickmartSession.loggedIn || window.quickmartSession.role !== 'buyer') {
            return;
        }
        fetch(getCartApiUrl() + '?action=get', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success) return;
                const items = Array.isArray(data.items) ? data.items : [];
                const count = items.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 0), 0);
                const cartCountHeader = document.getElementById('cartCountHeader');
                if (cartCountHeader) cartCountHeader.textContent = count;
                const sidebarCartCount = document.getElementById('sidebarCartCount');
                if (sidebarCartCount) sidebarCartCount.textContent = count;
            })
            .catch(err => {
                console.error('Navbar cart count error:', err);
            });
    }

    // Global user menu initialization function
    window.initializeUserMenuGlobal = function() {
        const userMenu = document.getElementById('userMenu');
        const userDropdown = document.getElementById('userDropdown');
        if (!userMenu || !userDropdown) return;
        
        const isLoggedIn = localStorage.getItem('isLoggedIn');
        const userEmail = localStorage.getItem('userEmail');
        const userRole = localStorage.getItem('userRole');
        
        // Hide cart button for sellers
        const cartBtn = document.getElementById('cartBtn');
        if (cartBtn && userRole === 'seller') {
            cartBtn.style.display = 'none';
        } else if (cartBtn) {
            cartBtn.style.display = 'flex';
        }
        
        // Show dashboard button for buyers and sellers
        const dashboardBtn = document.getElementById('dashboardBtn');
        if (dashboardBtn && (userRole === 'buyer' || userRole === 'seller')) {
            dashboardBtn.style.display = 'flex';
            dashboardBtn.onclick = function() {
                window.location.href = userRole === 'seller'
                    ? '../seller_dashboard/seller_dashboard.php'
                    : '../buyer_dashboard/buyer_dashboard.php';
            };
        } else if (dashboardBtn) {
            dashboardBtn.style.display = 'none';
        }
        
        if (isLoggedIn && userEmail) {
            const profilePath = userRole === 'seller' ? '../seller/profile.php' : '../buyer/profile.php';
            const dashPath = userRole === 'seller' ? '../seller_dashboard/seller_dashboard.php' : '../buyer_dashboard/buyer_dashboard.php';
            const settingsPath = userRole === 'seller' ? '../seller_dashboard/settings.php' : '../buyer_dashboard/settings.php';
            const roleLabel = userRole === 'seller' ? '<i class="fa-solid fa-truck-fast"></i> Seller' : '<i class="fa-solid fa-basket-shopping"></i> Buyer';
            
            let menuItems = '';
            if (userRole === 'buyer') {
                menuItems = `
                    <a href="../buyer_dashboard/wallet.php" class="dropdown-item">
                        <i class="fas fa-wallet"></i> My Wallet
                    </a>
                    <a href="../buyer_dashboard/cart.php" class="dropdown-item">
                        <i class="fas fa-shopping-cart"></i> My Cart
                    </a>
                `;
            } else {
                menuItems = `
                    <a href="../seller_dashboard/wallet.php" class="dropdown-item">
                        <i class="fas fa-dollar-sign"></i> Earnings
                    </a>
                    <a href="../seller_dashboard/history.php" class="dropdown-item">
                        <i class="fas fa-list"></i> Sales History
                    </a>
                `;
            }
            
            const userName = localStorage.getItem('userName') || userEmail;
            const userImage = localStorage.getItem('userImage') || 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png';
            
            // Update avatar in menu button
            const userAvatar = userMenu.querySelector('.user-avatar-small');
            if (userAvatar) {
                userAvatar.src = userImage;
            }
            
            userDropdown.innerHTML = `
                <div style="padding: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; display: flex; align-items: center; gap: 0.75rem;">
                    <img src="${userImage}" alt="User" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">${userName}</div>
                        <div style="color: var(--text-secondary); font-size: 0.8rem;">${roleLabel}</div>
                    </div>
                </div>
                <a href="${profilePath}" class="dropdown-item">
                    <i class="fas fa-user"></i> My Profile
                </a>
                ${menuItems}
                <a href="${dashPath}" class="dropdown-item">
                    <i class="fas fa-gauge"></i> Dashboard
                </a>
                <a href="${settingsPath}" class="dropdown-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <div style="border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">
                    <a href="#" onclick="window.logout(); return false;" class="dropdown-item" style="color: #ef4444;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            `;
        } else {
            userDropdown.innerHTML = `
                <a href="../html/login.php" class="dropdown-item">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="../html/login.php#signup" class="dropdown-item">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            `;
        }
        
        };
    
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
        localStorage.removeItem('userImage');
        const basePath = getAppBasePath();
        window.location.href = basePath + '/actions/logout.php';
    };
    
    // Notification handler
    window.setupNotificationModal = function() {
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationModal = document.getElementById('notificationModal');
        const closeNotificationBtn = document.getElementById('closeNotificationBtn');
        const notificationList = document.getElementById('notificationList');
        
        if (!notificationBtn || !notificationModal) return;
        
        // Open notification modal on click
        notificationBtn.onclick = function(e) {
            e.stopPropagation();
            const userRole = localStorage.getItem('userRole');
            const notifications = getNotifications(userRole);
            displayNotifications(notifications, notificationList, userRole);
            notificationModal.style.display = notificationModal.style.display === 'none' ? 'flex' : 'none';
        };
        
        // Close notification modal button
        closeNotificationBtn.onclick = function() {
            notificationModal.style.display = 'none';
        };
        
        // Close when clicking outside
        notificationModal.onclick = function(e) {
            if (e.target === notificationModal) {
                notificationModal.style.display = 'none';
            }
        };
    };
    
    // Get notifications based on role
    function getNotifications(userRole) {
        const rows = Array.isArray(window.navNotifications) ? window.navNotifications : [];
        if (rows.length === 0) return [];

        if (rows[0] && rows[0].title && rows[0].message) {
            return rows.map((row, index) => ({
                id: row.notification_id || index + 1,
                type: row.type || 'system',
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
                type: 'order',
                title: 'Product Sold',
                message: `${row.buyer_name} bought ${row.product_name} (ID ${row.product_id}) in order #${row.order_id}`,
                time: new Date(row.created_at).toLocaleString(),
                icon: 'fa-shopping-bag',
                color: '#3b82f6'
            }));
        }
        return rows.map((row, index) => ({
            id: index + 1,
            type: 'order',
            title: `Order #${row.order_id}`,
            message: `Status: ${row.status} ? Total ${Number(row.total_amount || 0).toLocaleString()} BDT`,
            time: new Date(row.created_at).toLocaleString(),
            icon: 'fa-receipt',
            color: '#10b981'
        }));
    }

    // Display notifications
    function displayNotifications(notifications, container, userRole) {
        if (notifications.length === 0) {
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
                    <button class="notification-action" onclick="markNotificationRead(${notif.id})">
                        <i class="fas fa-check"></i>
                    </button>
                `}
            </div>
        `).join('');
    }
    
    // Mark notification as read
    window.markNotificationRead = function(notificationId) {
        console.log('Notification ' + notificationId + ' marked as read');
        // Here you can add logic to update the notification status in database
    };

    refreshNavbarCartCount();
</script>

