<?php
require_once __DIR__ . '/../includes/session.php';
$base = defined('BASE_URL') ? BASE_URL : '/QuickMart';

?>
<!-- Sidebar Component -->
<aside class="sidebar" id="sidebar">
    <!-- User Profile -->
    <a href="<?php echo htmlspecialchars($base . '/index.php'); ?>" class="market-logo-container">
        <div>
            <img src="../images/qmart_logo2.png" alt="QuickMart Logo" class="market-logo">
        </div>
    </a>

    <!-- General Menu -->
    <div class="menu-section">
        <h4 class="menu-title">General</h4>
        <nav class="menu-nav">
            <a href="<?php echo htmlspecialchars($base . '/buyer_dashboard/buyer_dashboard.php'); ?>" class="menu-item" data-page="dashboard">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="#" class="menu-item" data-page="message">
                <i class="fa-regular fa-comments"></i>
                <span class="menu-text">Message</span>
            </a>
            <a href="<?php echo htmlspecialchars($base . '/buyer_dashboard/settings.php'); ?>" class="menu-item" data-page="settings">
                <i class="fas fa-cog"></i>
                <span class="menu-text">Settings</span>
            </a>
        </nav>
    </div>

    <!-- My Profile Menu (moved to top) -->
    <div class="menu-section">
        <h4 class="menu-title">My Account</h4>
        <nav class="menu-nav">
            <a href="<?php echo htmlspecialchars($base . '/buyer/profile.php'); ?>" class="menu-item" id="profileBtn" data-page="profile">
                <i class="fas fa-user-circle"></i>
                <span class="menu-text">My Profile</span>
            </a>
        </nav>
    </div>

    <!-- Marketplace Menu -->
    <div class="menu-section">
        <h4 class="menu-title">Market</h4>
        <nav class="menu-nav">
            <a href="<?php echo htmlspecialchars($base . '/html/products_page.php'); ?>" class="menu-item active" data-page="market">
                <i class="fas fa-store"></i>
                <span class="menu-text">Market</span>
            </a>
            <a href="<?php echo htmlspecialchars($base . '/buyer_dashboard/saved_items.php'); ?>" class="menu-item" data-page="saved">
                <i class="fas fa-bookmark"></i>
                <span class="menu-text">Saved</span>
            </a>
        </nav>
    </div>

    <!-- My Items Menu -->
    <div class="menu-section">
        <h4 class="menu-title">My Items</h4>
        <nav class="menu-nav">
            <a href="<?php echo htmlspecialchars($base . '/buyer_dashboard/wallet.php'); ?>" class="menu-item" data-page="wallet">
                <i class="fas fa-wallet"></i>
                <span class="menu-text">Wallet</span>
            </a>
            <a href="<?php echo htmlspecialchars($base . '/buyer_dashboard/cart.php'); ?>" class="menu-item" data-page="cart" id="sidebarCartBtn">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Cart</span>
                <span class="cart-badge" id="sidebarCartCount">0</span>
            </a>
            <a href="<?php echo htmlspecialchars($base . '/buyer_dashboard/history.php'); ?>" class="menu-item" data-page="history">
                <i class="fas fa-history"></i>
                <span class="menu-text">History</span>
            </a>
        </nav>
    </div>



    <!-- Help Center -->
    <div class="help-center">
        <div class="help-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <h4 class="help-title">Help Center</h4>
        <p class="help-text">Having trouble in QuickMart? Please contact us for more questions</p>
        <a href="<?php echo htmlspecialchars($base . '/html/help_center.php'); ?>" class="btn-help" style="cursor: pointer; border: none; background: none; text-decoration: none; display: block; color:aliceblue; border: 2px solid aliceblue; padding: 0.5rem 1rem; border-radius: 4px;">Go To Help Center</a>
    </div>
</aside>
