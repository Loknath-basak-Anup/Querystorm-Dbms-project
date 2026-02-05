<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/supabase_storage.php';

require_role('seller');

$sellerId = get_user_id() ?? 0;
$successMessage = '';
$errorMessage = '';
$roleNotice = '';
if (($_GET['role_request'] ?? '') === 'sent') {
    $roleNotice = 'Your role change request has been submitted. We will notify you after review.';
}
$shopRow = null;
$profileRow = db_fetch("SELECT shop_name, shop_description FROM seller_profiles WHERE seller_id = ?", [$sellerId]) ?: [];

try {
    $shopRow = db_fetch("SELECT * FROM shops WHERE seller_id = ?", [$sellerId]);
} catch (Throwable $e) {
    $shopRow = null;
}

function upload_shop_asset(string $fieldName, string $folder): ?string {
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
    return supabase_upload_image($tmpName, $file['name'] ?? 'image', $mimeType, $folder);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopName = trim($_POST['shop_name'] ?? '');
    $shopDescription = trim($_POST['shop_description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $logoUrl = trim($_POST['logo_url'] ?? '');
    $bannerUrl = trim($_POST['banner_url'] ?? '');

    if ($shopName === '') {
        $errorMessage = 'Shop name is required.';
    }

    if ($errorMessage === '') {
        try {
            $uploadedLogo = upload_shop_asset('logo_file', 'shop-assets');
            if ($uploadedLogo) $logoUrl = $uploadedLogo;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    }
    if ($errorMessage === '') {
        try {
            $uploadedBanner = upload_shop_asset('banner_file', 'shop-assets');
            if ($uploadedBanner) $bannerUrl = $uploadedBanner;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    }

    if ($errorMessage === '') {
        try {
            if ($shopRow) {
                db_query(
                    "UPDATE shops SET shop_name = ?, shop_description = ?, phone = ?, email = ?, address = ?, city = ?, country = ?, logo_url = ?, banner_url = ?, updated_at = NOW()
                     WHERE seller_id = ?",
                    [$shopName, $shopDescription, $phone, $email, $address, $city, $country, $logoUrl, $bannerUrl, $sellerId]
                );
            } else {
                db_execute(
                    "INSERT INTO shops (seller_id, shop_name, shop_description, phone, email, address, city, country, logo_url, banner_url, verified, rating, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())",
                    [$sellerId, $shopName, $shopDescription, $phone, $email, $address, $city, $country, $logoUrl, $bannerUrl]
                );
            }
            db_query("UPDATE seller_profiles SET shop_name = ?, shop_description = ? WHERE seller_id = ?", [$shopName, $shopDescription, $sellerId]);
            $successMessage = 'Shop settings saved successfully.';
            $shopRow = db_fetch("SELECT * FROM shops WHERE seller_id = ?", [$sellerId]);
        } catch (Exception $e) {
            $errorMessage = 'Unable to save shop settings. Please try again.';
        }
    }
}

$shopNameValue = $shopRow['shop_name'] ?? ($profileRow['shop_name'] ?? '');
$shopDescValue = $shopRow['shop_description'] ?? ($profileRow['shop_description'] ?? '');
$logoValue = $shopRow['logo_url'] ?? '';
$bannerValue = $shopRow['banner_url'] ?? '';
$phoneValue = $shopRow['phone'] ?? '';
$emailValue = $shopRow['email'] ?? ($_SESSION['email'] ?? '');
$addressValue = $shopRow['address'] ?? '';
$cityValue = $shopRow['city'] ?? '';
$countryValue = $shopRow['country'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seller Settings | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display:flex; flex-direction:row; min-height:100vh; }
        main.main-content { flex:1; display:flex; flex-direction:column; margin-left:280px; width:calc(100% - 280px); transition: margin-left 0.3s ease, width 0.3s ease; }
        body:has(.sidebar.collapsed) main.main-content { margin-left:80px; width:calc(100% - 80px); }
        .page-content { flex:1; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.65); display:none; align-items:center; justify-content:center; z-index:2000; }
        .modal-overlay.show { display:flex; }
        .modal-box { background:#0f172a; border:1px solid #1f2937; border-radius:12px; padding:1.5rem; width:min(480px,90vw); box-shadow:0 20px 40px rgba(0,0,0,0.35); color:#e2e8f0; }
        .modal-box h3 { margin:0 0 1rem; font-size:1.1rem; color:#fff; }
        .modal-box label { display:block; margin-bottom:0.35rem; color:#cbd5e1; font-size:0.9rem; }
        .modal-box input, .modal-box textarea { width:100%; padding:0.65rem 0.75rem; border-radius:8px; border:1px solid #334155; background:#0b1324; color:#e2e8f0; margin-bottom:0.75rem; }
        .modal-actions { display:flex; gap:0.75rem; justify-content:flex-end; }
        .btn-secondary { background:#1f2937; color:#e2e8f0; }
        .btn-primary { background:#00d4ff; color:#0b1324; }
        .btn-secondary, .btn-primary { border:none; padding:0.65rem 1rem; border-radius:8px; cursor:pointer; font-weight:600; }
    </style>
</head>
<body class="dark-mode">
    <script>
        // Ensure role/state for sidebar & navbar routing
        localStorage.setItem('isLoggedIn', 'true');
        localStorage.setItem('userRole', 'seller');
        localStorage.setItem('userEmail', <?php echo json_encode($_SESSION['email'] ?? ''); ?>);
        localStorage.setItem('userName', <?php echo json_encode($_SESSION['full_name'] ?? 'Seller'); ?>);
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
                scripts.forEach(script => {
                    const s = document.createElement('script');
                    s.innerHTML = script.innerHTML;
                    document.body.appendChild(s);
                });
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-cog"></i> Settings';
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal();
                }, 50);
            }
            loadNavbar();
        </script>
        <script>
            async function loadSidebar(){
                try {
                    const r = await fetch('../html/leftsidebar.php');
                    const h = await r.text();
                    document.getElementById('sidebarContainer').innerHTML = h;
                    const scripts = document.getElementById('sidebarContainer').querySelectorAll('script');
                    scripts.forEach(script => {
                        const s = document.createElement('script');
                        s.innerHTML = script.innerHTML;
                        document.body.appendChild(s);
                    });
                } catch(e) {
                    console.error('Error loading sidebar:', e);
                }
            }
            loadSidebar();
        </script>
        <div class="page-content">
            <div class="product-card" style="padding:2rem;">
                <div class="product-info" style="padding:0;">
                    <h2 class="product-title">Shop Settings</h2>
                    <p class="product-creator">Manage your storefront details, branding, and contact info.</p>
                </div>
                <?php if ($roleNotice): ?>
                    <div style="margin:1rem 0; padding:0.75rem 1rem; border-radius:0.75rem; background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.4); color:#bfdbfe;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($roleNotice); ?>
                    </div>
                <?php endif; ?>
                <?php if ($successMessage): ?>
                    <div style="margin:1rem 0; padding:0.75rem 1rem; border-radius:0.75rem; background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.4); color:#6ee7b7;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php elseif ($errorMessage): ?>
                    <div style="margin:1rem 0; padding:0.75rem 1rem; border-radius:0.75rem; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.4); color:#fecaca;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" style="display:grid; gap:1.5rem; margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Shop Name</label>
                            <input type="text" name="shop_name" required value="<?php echo htmlspecialchars($shopNameValue); ?>" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Contact Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($phoneValue); ?>" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Contact Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($emailValue); ?>" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Shop Description</label>
                        <textarea name="shop_description" rows="4" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;"><?php echo htmlspecialchars($shopDescValue); ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:1rem;">
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Shop Logo</label>
                            <?php if ($logoValue): ?>
                                <img src="<?php echo htmlspecialchars($logoValue); ?>" alt="Shop logo" style="width:120px; height:120px; object-fit:cover; border-radius:12px; border:1px solid #334155; margin-bottom:0.6rem;">
                            <?php endif; ?>
                            <input type="file" name="logo_file" accept="image/*" style="width:100%; color:#e2e8f0;">
                            <input type="text" name="logo_url" placeholder="Or paste logo URL" value="<?php echo htmlspecialchars($logoValue); ?>" style="width:100%; margin-top:0.5rem; padding:0.65rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Cover Banner</label>
                            <?php if ($bannerValue): ?>
                                <img src="<?php echo htmlspecialchars($bannerValue); ?>" alt="Shop banner" style="width:100%; height:120px; object-fit:cover; border-radius:12px; border:1px solid #334155; margin-bottom:0.6rem;">
                            <?php endif; ?>
                            <input type="file" name="banner_file" accept="image/*" style="width:100%; color:#e2e8f0;">
                            <input type="text" name="banner_url" placeholder="Or paste banner URL" value="<?php echo htmlspecialchars($bannerValue); ?>" style="width:100%; margin-top:0.5rem; padding:0.65rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($addressValue); ?>" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($cityValue); ?>" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:0.4rem; color:#cbd5e1;">Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($countryValue); ?>" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;">
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn-add-cart" style="padding:0.75rem 1.5rem;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <div class="product-card" style="padding:2rem; margin-top:1.5rem;">
                <div class="product-info" style="padding:0;">
                    <h2 class="product-title">Request Role Change</h2>
                    <p class="product-creator">Apply to switch from seller to buyer. All seller data will be removed after approval.</p>
                </div>
                <form method="post" action="../actions/role_change_request.php" style="display:grid; gap:1rem; margin-top:1rem;">
                    <input type="hidden" name="requested_role" value="buyer">
                    <label style="display:block; color:#cbd5e1;">Reason for switching roles</label>
                    <textarea name="reason" required rows="3" style="width:100%; padding:0.75rem 0.9rem; border-radius:0.75rem; border:1px solid #334155; background:#0b1324; color:#e2e8f0;"></textarea>
                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn-add-cart" style="padding:0.75rem 1.5rem;">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div id="footerContainer" class="mt-8"></div>
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
        // Settings form uses standard submit now.
    </script>
</body>
</html>
