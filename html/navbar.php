<header class="top-header" style="background:var(--bg-secondary)">
    <button class="btn-icon menu-toggle" id="menuToggle" style="display:flex">
        <i class="fas fa-bars"></i>
    </button>
    <h2 class="page-title-navbar" style="font-weight:700;flex:1"></h2>
    <div class="header-actions">
        <button class="btn-icon" id="notificationBtn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
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
            <img src="https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png" alt="User" class="user-avatar-small">
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
        
        // Hide dashboard button for buyers
        const dashboardBtn = document.getElementById('dashboardBtn');
        if (dashboardBtn && userRole === 'buyer') {
            dashboardBtn.style.display = 'none';
        } else if (dashboardBtn && userRole === 'seller') {
            dashboardBtn.style.display = 'flex';
            // Add click handler for dashboard
            dashboardBtn.onclick = function() {
                window.location.href = '../seller_dashboard/seller_dashboard.php';
            };
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
                <!-- Dashboard link hidden for buyers -->
                <!-- <a href="${dashPath}" class="dropdown-item">
                    <i class="fas fa-gauge"></i> Dashboard
                </a> -->
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
                    <i class="fas fa-sign-in-alt"></i> Sign In
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
        if (userRole === 'seller') {
            return [
                {
                    id: 1,
                    type: 'order',
                    title: 'New Order Received',
                    message: 'You received a new order #ORD-2024-156 from a buyer',
                    time: '5 minutes ago',
                    icon: 'fa-shopping-bag',
                    color: '#3b82f6'
                },
                {
                    id: 2,
                    type: 'message',
                    title: 'New Message',
                    message: 'John Doe sent you a message: "Is this item available?"',
                    time: '15 minutes ago',
                    icon: 'fa-envelope',
                    color: '#10b981'
                },
                {
                    id: 3,
                    type: 'payment',
                    title: 'Payment Received',
                    message: 'Payment of ₹5,500 received for order #ORD-2024-153',
                    time: '1 hour ago',
                    icon: 'fa-money-bill',
                    color: '#8b5cf6'
                }
            ];
        } else {
            // Buyer notifications
            return [
                {
                    id: 1,
                    type: 'order',
                    title: 'Order Shipped',
                    message: 'Your order #ORD-2024-145 has been shipped. Track it now!',
                    time: '2 hours ago',
                    icon: 'fa-truck',
                    color: '#3b82f6'
                },
                {
                    id: 2,
                    type: 'discount',
                    title: 'Special Offer',
                    message: 'Get 30% off on Electronics! Limited time offer',
                    time: '4 hours ago',
                    icon: 'fa-tag',
                    color: '#f59e0b'
                },
                {
                    id: 3,
                    type: 'review',
                    title: 'Review Request',
                    message: 'Please review "iPhone 14 Pro Max" you purchased last week',
                    time: '1 day ago',
                    icon: 'fa-star',
                    color: '#ef4444'
                }
            ];
        }
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
                <button class="notification-action" onclick="markNotificationRead(${notif.id})">
                    <i class="fas fa-check"></i>
                </button>
            </div>
        `).join('');
    }
    
    // Mark notification as read
    window.markNotificationRead = function(notificationId) {
        console.log('Notification ' + notificationId + ' marked as read');
        // Here you can add logic to update the notification status in database
    };
</script>
