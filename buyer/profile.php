<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/supabase_storage.php';
require_role('buyer');

$buyerId = get_user_id() ?? 0;
$successMessage = '';
$errorMessage = '';

try {
    db_query("ALTER TABLE users ADD COLUMN profile_image_url VARCHAR(255) NULL");
} catch (Exception $e) {
    // Ignore if column exists.
}

function upload_profile_image(string $fieldName, string $folder): ?string {
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmpName = $file['tmp_name'] ?? '';
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return null;
    }
    if (!supabase_is_configured()) {
        throw new Exception('Supabase storage is not configured.');
    }
    $mimeType = $file['type'] ?? '';
    if ($mimeType === '' && function_exists('mime_content_type')) {
        $mimeType = mime_content_type($tmpName) ?: 'application/octet-stream';
    }
    return supabase_upload_image($tmpName, $file['name'] ?? 'profile', $mimeType, $folder);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $profileImageUrl = null;

        if ($fullName === '') {
            $errorMessage = 'Full name is required.';
        } else {
            try {
                try {
                    $uploaded = upload_profile_image('profile_image', 'profile-images');
                    if ($uploaded) {
                        $profileImageUrl = $uploaded;
                    }
                } catch (Exception $e) {
                    $errorMessage = $e->getMessage();
                }

                if ($errorMessage !== '') {
                    throw new Exception($errorMessage);
                }

                db_execute(
                    "UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?",
                    [$fullName, $phone, $buyerId]
                );
                if ($profileImageUrl) {
                    db_execute(
                        "UPDATE users SET profile_image_url = ? WHERE user_id = ?",
                        [$profileImageUrl, $buyerId]
                    );
                }

                $existingProfile = db_fetch(
                    "SELECT buyer_id FROM buyer_profiles WHERE buyer_id = ?",
                    [$buyerId]
                );

                if ($existingProfile) {
                    db_execute(
                        "UPDATE buyer_profiles SET address = ? WHERE buyer_id = ?",
                        [$address, $buyerId]
                    );
                } else {
                    db_execute(
                        "INSERT INTO buyer_profiles (buyer_id, address) VALUES (?, ?)",
                        [$buyerId, $address]
                    );
                }

                // Keep session name in sync for header greetings
                $_SESSION['full_name'] = $fullName;

                $successMessage = 'Profile updated successfully.';
            } catch (Exception $e) {
                $errorMessage = 'Could not update profile. Please try again.';
            }
        }
    }
}

$userRow = db_fetch(
    "SELECT u.full_name, u.email, u.phone, u.status, u.profile_image_url, COALESCE(bp.address, '') AS address
     FROM users u
     LEFT JOIN buyer_profiles bp ON bp.buyer_id = u.user_id
     WHERE u.user_id = ?",
    [$buyerId]
);

$fullName    = $userRow['full_name'] ?? ($_SESSION['full_name'] ?? 'Buyer');
$email       = $userRow['email'] ?? ($_SESSION['email'] ?? '');
$phone       = $userRow['phone'] ?? '';
$address     = $userRow['address'] ?? '';
$profileImage = $userRow['profile_image_url'] ?? '';

$statsRow = db_fetch(
    "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_spent
     FROM orders
     WHERE buyer_id = ?",
    [$buyerId]
);

$totalOrders = (int)($statsRow['total_orders'] ?? 0);
$totalSpent  = (float)($statsRow['total_spent'] ?? 0.0);
$wishlistCount = 0; // Wishlist is handled via saved items / favorites on client side

$recentOrders = db_fetch_all(
    "SELECT
        o.order_id,
        o.status,
        o.created_at,
        o.total_amount,
        (
            SELECT GROUP_CONCAT(p.name SEPARATOR ', ')
            FROM order_items oi
            INNER JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = o.order_id
        ) AS product_names
     FROM orders o
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC
     LIMIT 5",
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
?>
<?php /* Buyer profile page with live stats and editable details */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Profile | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/profile.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display:flex; flex-direction:row; min-height:100vh; }
        main.main-content { flex:1; display:flex; flex-direction:column; margin-left:280px; width:calc(100% - 280px); transition:margin-left 0.3s ease, width 0.3s ease; }
        body:has(.sidebar.collapsed) main.main-content { margin-left:80px; width:calc(100% - 80px); }
        .page-content { flex:1; }
    </style>
</head>
<body class="dark-mode">
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadSidebar() {
                const response = await fetch('../html/leftsidebar.php');
                const html = await response.text();
                document.getElementById('sidebarContainer').innerHTML = html;
                const scripts = document.getElementById('sidebarContainer').querySelectorAll('script');
                scripts.forEach(script => { const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
            }
            loadSidebar();
            async function loadNavbar() {
                const response = await fetch('../html/navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => { const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); });
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-user"></i> My Profile';
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal();
                    const userMenu = document.getElementById('userMenu');
                    const userDropdown = document.getElementById('userDropdown');
                    let userMenuTimeout;
                    if (userMenu && userDropdown) {
                        userMenu.onmouseenter = function(){ clearTimeout(userMenuTimeout); userDropdown.style.display='block'; userDropdown.style.opacity='1'; userDropdown.style.visibility='visible'; };
                        userMenu.onmouseleave = function(){ userMenuTimeout=setTimeout(()=>{ userDropdown.style.display='none'; userDropdown.style.opacity='0'; userDropdown.style.visibility='hidden'; },200); };
                        userDropdown.onmouseenter = function(){ clearTimeout(userMenuTimeout); };
                        userDropdown.onmouseleave = function(){ userMenuTimeout=setTimeout(()=>{ userDropdown.style.display='none'; userDropdown.style.opacity='0'; userDropdown.style.visibility='hidden'; },200); };
                        userMenu.onclick = function(e){ e.stopPropagation(); const v=userDropdown.style.display==='block'; userDropdown.style.display=v?'none':'block'; userDropdown.style.opacity=v?'0':'1'; userDropdown.style.visibility=v?'hidden':'visible'; };
                    }
                    document.onclick = function(e){ if (userDropdown && userMenu && !userMenu.contains(e.target) && !userDropdown.contains(e.target)) { userDropdown.style.display='none'; userDropdown.style.opacity='0'; userDropdown.style.visibility='hidden'; } };
                    const themeToggle=document.getElementById('themeToggle');
                    if (themeToggle) themeToggle.onclick=function(){ const body=document.body; const icon=themeToggle.querySelector('i'); body.classList.toggle('dark-mode'); if (body.classList.contains('dark-mode')) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); localStorage.setItem('quickmart_theme','dark'); } else { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); localStorage.setItem('quickmart_theme','light'); } };
                    const savedTheme=localStorage.getItem('quickmart_theme');
                    if (savedTheme==='light') { document.body.classList.remove('dark-mode'); const icon = themeToggle ? themeToggle.querySelector('i') : null; if (icon) { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); } }
                    if (typeof window.setupNotificationModal === 'function') window.setupNotificationModal();
                },50);
            }
            loadNavbar();
        </script>
    <div class="container">
        <?php if ($successMessage): ?>
            <div style="margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.5);font-size:0.9rem;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php elseif ($errorMessage): ?>
            <div style="margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:rgba(239,68,68,0.12);color:#fecaca;border:1px solid rgba(239,68,68,0.5);font-size:0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <!-- Profile Header -->
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($profileImage !== '' ? $profileImage : 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png'); ?>" alt="Profile" class="profile-avatar">
            <div class="profile-info">
                <div class="profile-badge"><i class="fas fa-shopping-bag"></i><span>Buyer Account</span></div>
                <h1 id="userName"><?php echo htmlspecialchars($fullName); ?></h1>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;" id="userEmail"><?php echo htmlspecialchars($email); ?></p>
                <div class="profile-stats">
                    <div class="stat-item"><div class="stat-value"><?php echo number_format($totalOrders); ?></div><div class="stat-label">Total Orders</div></div>
                    <div class="stat-item"><div class="stat-value"><?php echo number_format($wishlistCount); ?></div><div class="stat-label">Saved Items</div></div>
                    <div class="stat-item"><div class="stat-value"><i class="fa-solid fa-bangladeshi-taka-sign"></i> <?php echo number_format($totalSpent, 2); ?></div><div class="stat-label">Total Spent</div></div>
                </div>
            </div>
        </div>
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('orders')"><i class="fas fa-box"></i> My Orders</button>
            <button class="tab" onclick="switchTab('profile')"><i class="fas fa-user"></i> Profile Details</button>
            <button class="tab" onclick="switchTab('addresses')"><i class="fas fa-map-marker-alt"></i> Addresses</button>
            <button class="tab" onclick="switchTab('security')"><i class="fas fa-lock"></i> Security</button>
        </div>
        <!-- Orders Tab -->
        <div id="orders" class="tab-content active">
            <?php if (empty($recentOrders)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">No orders yet</div>
                            <small style="color: var(--text-secondary);">Start shopping to place your first order.</small>
                        </div>
                    </div>
                    <button class="btn-primary" onclick="window.location.href='../html/products_page.php'">
                        <i class="fas fa-shopping-cart"></i> Browse Products
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                                <small style="color: var(--text-secondary);">
                                    Placed on <?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?>
                                </small>
                            </div>
                            <span class="order-status status-delivered">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars(format_order_status($order['status'])); ?>
                            </span>
                        </div>
                        <div class="order-items">
                            <div class="order-item">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($order['product_names'] ?: 'Multiple items'); ?></h4>
                                    <p style="color: var(--text-secondary); font-size: 0.875rem;">
                                        Items: <?php echo number_format($order['total_amount'] > 0 ? 1 : 0); ?>
                                    </p>
                                    <div class="item-price">
                                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                                        <?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="btn-primary" onclick="window.location.href='../buyer_dashboard/history.php'">
                            <i class="fas fa-list"></i> View All Orders
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Profile Details Tab -->
        <div id="profile" class="tab-content">
            <div class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h4><i class="fas fa-id-card"></i> Profile Details</h4>
                        <p class="profile-card-subtitle">
                            Keep your contact information and default address up to date for faster checkout.
                        </p>
                    </div>
                    <div class="profile-meta">
                        <div><strong>Name:</strong> <?php echo htmlspecialchars($fullName); ?></div>
                        <div><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></div>
                    </div>
                </div>
                <form method="post" action="profile.php" class="profile-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label for="profileImage">Profile Image</label>
                            <input type="file" id="profileImage" name="profile_image" accept="image/*">
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                                <small style="color: var(--text-secondary); font-size: 0.8rem;">Email changes are managed from Settings.</small>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="address">Default Shipping Address</label>
                                <textarea id="address" name="address" rows="5" placeholder="House / Road / Area / City "><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                            <button type="submit" class="btn-primary" style="margin-top:0.25rem;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                </form>
            </div>
        </div>

        <!-- Addresses Tab -->
        <div id="addresses" class="tab-content">
            <div class="address-card">
                <div class="profile-card-header">
                    <div>
                        <h4><i class="fas fa-map-marker-alt"></i> Default Address</h4>
                        <p class="profile-card-subtitle">
                            Your primary shipping address is used for faster checkout.
                        </p>
                    </div>
                </div>
                <p><?php echo $address ? nl2br(htmlspecialchars($address)) : 'No address saved yet.'; ?></p>
                <button class="btn-primary" onclick="switchTab('profile', document.querySelector('.tab[data-tab-target=\'profile\']'))">
                    <i class="fas fa-edit"></i> Edit in Profile Details
                </button>
            </div>
        </div>

        <!-- Security Tab -->
        <div id="security" class="tab-content">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                Password changes and advanced security settings are managed from the Settings page.
            </p>
            <button class="btn-primary" onclick="window.location.href='../buyer_dashboard/settings.php'">
                <i class="fas fa-shield-alt"></i> Open Security Settings
            </button>
        </div>
    </div>
        <div id="footerContainer" class="mt-8"></div>
    </main>
    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        function switchTab(tabName, el) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            const target = el || event.currentTarget || event.target;
            if (target && target.classList.contains('tab')) {
                target.classList.add('active');
            }

            const contentEl = document.getElementById(tabName);
            if (contentEl) {
                contentEl.classList.add('active');
            }
        }

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
    <script>
        const profileImageUrl = <?php echo json_encode($profileImage); ?>;
        if (profileImageUrl) {
            localStorage.setItem('userImage', profileImageUrl);
        }
    </script>
</body>
</html>
