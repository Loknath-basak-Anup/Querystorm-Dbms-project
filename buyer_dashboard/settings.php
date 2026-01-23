<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('buyer');

$buyerId = get_user_id() ?? 0;
$roleNotice = '';
if (($_GET['role_request'] ?? '') === 'sent') {
    $roleNotice = 'Your role change request has been submitted. We will notify you after review.';
}
$roleRequest = null;
try {
    $roleRequest = db_fetch(
        "SELECT requested_role, status, created_at
         FROM role_change_requests
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 1",
        [$buyerId]
    );
} catch (Exception $e) {
    $roleRequest = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buyer Settings | QuickMart</title>
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
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar(){ const r=await fetch('../html/navbar.php'); const h=await r.text(); document.getElementById('navbarContainer').innerHTML=h; const scripts=document.getElementById('navbarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); const pageTitle=document.querySelector('.page-title-navbar'); if(pageTitle) pageTitle.innerHTML='<i class="fas fa-cog"></i> Settings'; setTimeout(()=>{ if(typeof window.initializeUserMenuGlobal==='function') window.initializeUserMenuGlobal(); },50);} loadNavbar();
        </script>
        <script>
            async function loadSidebar(){ try{ const r=await fetch('../html/leftsidebar.php'); const h=await r.text(); document.getElementById('sidebarContainer').innerHTML=h; const scripts=document.getElementById('sidebarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); }catch(e){ console.error('Error loading sidebar:', e); } } loadSidebar();
        </script>
        <div class="page-content">
            <?php if ($roleNotice !== ''): ?>
                <div style="margin-bottom:1rem; padding:0.75rem 1rem; border-radius:0.75rem; background:rgba(59,130,246,0.12); border:1px solid rgba(59,130,246,0.4); color:#bfdbfe;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($roleNotice); ?>
                </div>
            <?php endif; ?>
            <div class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
                <div class="product-card"><div class="product-info"><h3 class="product-title">Profile</h3><p class="product-creator">Update your name, phone and more</p><div class="product-footer"><button class="btn-add-cart" onclick="openModal('profile')"><i class="fas fa-user"></i><span>Edit Profile</span></button></div></div></div>
                <div class="product-card"><div class="product-info"><h3 class="product-title">Security</h3><p class="product-creator">Change password and 2FA</p><div class="product-footer"><button class="btn-add-cart" onclick="openModal('security')"><i class="fas fa-shield-alt"></i><span>Manage Security</span></button></div></div></div>
                <div class="product-card"><div class="product-info"><h3 class="product-title">Notifications</h3><p class="product-creator">Email & SMS preferences</p><div class="product-footer"><button class="btn-add-cart" onclick="openModal('notifications')"><i class="fas fa-bell"></i><span>Configure</span></button></div></div></div>
                <div class="product-card"><div class="product-info"><h3 class="product-title">Addresses</h3><p class="product-creator">Manage shipping addresses</p><div class="product-footer"><button class="btn-add-cart" onclick="openModal('addresses')"><i class="fas fa-map-marker-alt"></i><span>Manage</span></button></div></div></div>
                <div class="product-card">
                    <div class="product-info">
                        <h3 class="product-title">Become a Seller</h3>
                        <p class="product-creator">
                            Apply to change your role and start selling on QuickMart.
                            <?php if ($roleRequest): ?>
                                <span style="display:block; margin-top:6px; color:#94a3b8;">Status: <?php echo htmlspecialchars($roleRequest['status']); ?></span>
                            <?php endif; ?>
                        </p>
                        <div class="product-footer">
                            <a class="btn-add-cart" href="../seller/signup.php?apply=buyer">
                                <i class="fas fa-store"></i><span>Start Application</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-overlay" id="settingsModal"><div class="modal-box"><h3 id="modalTitle">Update Setting</h3><label id="modalLabel">Field</label><input id="modalInput" /><div class="modal-actions"><button class="btn-secondary" onclick="closeModal()">Cancel</button><button class="btn-primary" onclick="saveSetting()">Save</button></div></div></div>
        </div>
        <div id="footerContainer" class="mt-8"></div>
    </main>
    <script src="../assets/js/products_page.js"></script>
    <script>
        async function loadFooter(){ try{ const r=await fetch('../html/footer.php'); const h=await r.text(); document.getElementById('footerContainer').innerHTML=h; }catch(e){ console.error('Error loading footer:', e); } } loadFooter();
        function openModal(type){ const modal=document.getElementById('settingsModal'); modal.classList.add('show'); const title=document.getElementById('modalTitle'); const label=document.getElementById('modalLabel'); if(type==='profile'){ title.textContent='Edit Profile'; label.textContent='Full Name'; } else if(type==='security'){ title.textContent='Change Password'; label.textContent='New Password'; } else if(type==='notifications'){ title.textContent='Notification Settings'; label.textContent='Email'; } else if(type==='addresses'){ title.textContent='Manage Address'; label.textContent='Address'; } }
        function closeModal(){ const modal=document.getElementById('settingsModal'); modal.classList.remove('show'); }
        function saveSetting(){ closeModal(); alert('Saved!'); }
    </script>
</body>
</html>
