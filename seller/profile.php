<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/supabase_storage.php';
require_role('seller');

$sellerId = get_user_id() ?? 0;
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
        $fullName        = trim($_POST['full_name'] ?? '');
        $shopName        = trim($_POST['shop_name'] ?? '');
        $shopDescription = trim($_POST['shop_description'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $profileImageUrl = null;

        if ($fullName === '' || $shopName === '') {
            $errorMessage = 'Full name and shop name are required.';
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
                    'UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?',
                    [$fullName, $phone, $sellerId]
                );
                if ($profileImageUrl) {
                    db_execute(
                        "UPDATE users SET profile_image_url = ? WHERE user_id = ?",
                        [$profileImageUrl, $sellerId]
                    );
                }

                $existingProfile = db_fetch(
                    'SELECT seller_id FROM seller_profiles WHERE seller_id = ?',
                    [$sellerId]
                );

                if ($existingProfile) {
                    db_execute(
                        'UPDATE seller_profiles SET shop_name = ?, shop_description = ? WHERE seller_id = ?',
                        [$shopName, $shopDescription, $sellerId]
                    );
                } else {
                    db_execute(
                        'INSERT INTO seller_profiles (seller_id, shop_name, shop_description, verified) VALUES (?, ?, ?, 0)',
                        [$sellerId, $shopName, $shopDescription]
                    );
                }

                $_SESSION['full_name'] = $fullName;

                $successMessage = 'Seller profile updated successfully.';
            } catch (Exception $e) {
                $errorMessage = 'Could not update seller profile. Please try again.';
            }
        }
    }
}

$userRow = db_fetch(
    'SELECT u.full_name, u.email, u.phone,
            u.profile_image_url,
            COALESCE(sp.shop_name, "") AS shop_name,
            COALESCE(sp.shop_description, "") AS shop_description,
            COALESCE(sp.verified, 0) AS verified
     FROM users u
     LEFT JOIN seller_profiles sp ON sp.seller_id = u.user_id
     WHERE u.user_id = ?
     LIMIT 1',
    [$sellerId]
);

$fullName        = $userRow['full_name'] ?? ($_SESSION['full_name'] ?? 'Seller');
$email           = $userRow['email'] ?? ($_SESSION['email'] ?? '');
$phone           = $userRow['phone'] ?? '';
$profileImage    = $userRow['profile_image_url'] ?? '';
$shopName        = $userRow['shop_name'] ?? '';
$shopDescription = $userRow['shop_description'] ?? '';
$isVerified      = (int)($userRow['verified'] ?? 0) === 1;

$statsRow = db_fetch(
    'SELECT
        COALESCE(SUM(oi.price * oi.quantity), 0) AS total_sales,
        COUNT(DISTINCT oi.order_id) AS total_orders
     FROM order_items oi
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?',
    [$sellerId]
);
$totalSales  = (float)($statsRow['total_sales'] ?? 0);
$totalOrders = (int)($statsRow['total_orders'] ?? 0);

$productsRow = db_fetch(
    'SELECT COUNT(*) AS total_products
     FROM products
     WHERE seller_id = ?',
    [$sellerId]
);
$totalProducts = (int)($productsRow['total_products'] ?? 0);

$customersRow = db_fetch(
    'SELECT COUNT(DISTINCT o.buyer_id) AS total_customers
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.order_id
     INNER JOIN products p ON p.product_id = oi.product_id
     WHERE p.seller_id = ?',
    [$sellerId]
);
$totalCustomers = (int)($customersRow['total_customers'] ?? 0);

function seller_percent(int $num, int $den): int {
    if ($den <= 0) return 0;
    return (int)round(($num / $den) * 100);
}

$repeatCustomersRow = db_fetch(
    'SELECT COUNT(*) AS repeat_customers
     FROM (
        SELECT o.buyer_id, COUNT(DISTINCT o.order_id) AS order_count
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.order_id
        INNER JOIN products p ON p.product_id = oi.product_id
        WHERE p.seller_id = ?
        GROUP BY o.buyer_id
        HAVING order_count > 1
     ) t',
    [$sellerId]
);
$repeatCustomers = (int)($repeatCustomersRow['repeat_customers'] ?? 0);

$repeatRate = seller_percent($repeatCustomers, max(1, $totalCustomers));
$ratingScore = $totalOrders > 0 ? min(5, max(1, round(3 + (2 * ($totalOrders > 0 ? ($totalOrders / max($totalOrders, 1)) : 0)), 1))) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile | QuickMart</title>
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
    <script>
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'seller');
        localStorage.setItem('userEmail', <?php echo json_encode($email); ?>);
        localStorage.setItem('userName', <?php echo json_encode($fullName); ?>);
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
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-user"></i> Seller Profile';
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
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($profileImage !== '' ? $profileImage : 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png'); ?>" alt="Profile" class="profile-avatar">
            <div class="profile-info">
                <div class="profile-badge">
                    <i class="fas fa-store"></i><span>Seller Account</span>
                    <?php if ($isVerified): ?>
                        <span style="margin-left:0.5rem; padding:0.15rem 0.5rem; border-radius:999px; background:rgba(56,189,248,0.2); color:#7dd3fc; font-size:0.75rem;">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    <?php endif; ?>
                </div>
                <h1 id="userName"><?php echo htmlspecialchars($shopName !== '' ? $shopName : $fullName); ?></h1>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;" id="userEmail"><?php echo htmlspecialchars($email); ?></p>
                <p style="color: var(--text-secondary); margin-bottom: 1rem; max-width: 600px;">
                    <?php echo htmlspecialchars($shopDescription !== '' ? $shopDescription : 'Tell buyers more about your shop to build trust and increase sales.'); ?>
                </p>
                <div class="profile-stats">
                    <div class="stat-item"><div class="stat-value"><?php echo number_format($totalProducts); ?></div><div class="stat-label">Active Products</div></div>
                    <div class="stat-item"><div class="stat-value"><?php echo number_format($totalOrders); ?></div><div class="stat-label">Total Orders</div></div>
                    <div class="stat-item"><div class="stat-value"><i class="fa-solid fa-bangladeshi-taka-sign"></i> <?php echo number_format($totalSales, 2); ?></div><div class="stat-label">Total Revenue</div></div>
                </div>
            </div>
        </div>
        <div class="tabs">
            <button class="tab active" onclick="switchTab('overview')"><i class="fas fa-chart-line"></i> Store Overview</button>
            <button class="tab" onclick="switchTab('profile')"><i class="fas fa-user"></i> Profile Details</button>
            <button class="tab" onclick="switchTab('security')"><i class="fas fa-lock"></i> Security</button>
        </div>
        <div id="overview" class="tab-content active">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                Key insights about your shop performance.
            </p>
            <div class="address-card" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                <div>
                    <h4><i class="fas fa-users"></i> Customers</h4>
                    <p>Total customers: <?php echo number_format($totalCustomers); ?></p>
                    <p>Repeat customers: <?php echo number_format($repeatCustomers); ?> (<?php echo number_format($repeatRate); ?>%)</p>
                </div>
                <div>
                    <h4><i class="fas fa-star"></i> Seller Score</h4>
                    <p>Estimated rating: <?php echo number_format($ratingScore, 1); ?> / 5.0</p>
                    <p style="font-size:0.8rem;color:var(--text-secondary);">This score is based on your order activity.</p>
                </div>
                <div>
                    <h4><i class="fas fa-box-open"></i> Inventory</h4>
                    <p>Active products: <?php echo number_format($totalProducts); ?></p>
                    <button class="btn-primary" onclick="window.location.href='../seller_dashboard/my_products.php'">
                        <i class="fas fa-boxes"></i> Manage Products
                    </button>
                </div>
            </div>
        </div>
        <div id="profile" class="tab-content">
            <div class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h4><i class="fas fa-store"></i> Store Profile</h4>
                        <p class="profile-card-subtitle">
                            Update your shop identity so buyers can recognize and trust your brand.
                        </p>
                    </div>
                    <div class="profile-meta">
                        <div><strong>Owner:</strong> <?php echo htmlspecialchars($fullName); ?></div>
                        <div><strong>Shop:</strong> <?php echo htmlspecialchars($shopName ?: 'Not named yet'); ?></div>
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
                        <label for="full_name">Owner Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="shop_name">Shop Name</label>
                        <input type="text" id="shop_name" name="shop_name" value="<?php echo htmlspecialchars($shopName); ?>" required>
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
                        <label for="shop_description">Shop Description</label>
                        <textarea id="shop_description" name="shop_description" rows="5" placeholder="Tell buyers what you sell, your style and service."><?php echo htmlspecialchars($shopDescription); ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top:0.25rem;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
                </form>
            </div>
        </div>
        <div id="security" class="tab-content">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                Password changes and advanced security settings are managed from the Settings page.
            </p>
            <button class="btn-primary" onclick="window.location.href='../seller_dashboard/settings.php'">
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
