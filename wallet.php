<?php
require_once __DIR__ . '/../includes/session.php';
require_role('buyer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Wallet | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/wallet.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display:flex; flex-direction:row; min-height:100vh; }
        main.main-content { flex:1; display:flex; flex-direction:column; margin-left:280px; width:calc(100% - 280px); transition: margin-left 0.3s ease, width 0.3s ease; }
        body:has(.sidebar.collapsed) main.main-content { margin-left:80px; width:calc(100% - 80px); }
        .page-content { flex:1; }
    </style>
</head>
<body class="dark-mode">
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar(){ const r=await fetch('../html/navbar.php'); const h=await r.text(); document.getElementById('navbarContainer').innerHTML=h; const scripts=document.getElementById('navbarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); const pageTitle=document.querySelector('.page-title-navbar'); if(pageTitle) pageTitle.innerHTML = '<i class="fas fa-wallet"></i> My Wallet'; setTimeout(()=>{ if (typeof window.initializeUserMenuGlobal === 'function') window.initializeUserMenuGlobal(); },50);} loadNavbar();
        </script>
        <script>
            async function loadSidebar(){ const r=await fetch('../html/leftsidebar.php'); const h=await r.text(); document.getElementById('sidebarContainer').innerHTML=h; const scripts=document.getElementById('sidebarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); } loadSidebar();
        </script>
        <div class="page-content">
            <div class="wallet-summary" style="padding:1rem;">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)"><i class="fas fa-wallet"></i></div>
                    <div class="stat-details"><span class="stat-label">Wallet Balance</span><span class="stat-value">₱<?php echo number_format($walletBalance, 2); ?></span><span class="stat-change"><i class="fas fa-coins"></i> Available funds</span></div>
                </div>
            </div>
        </div>
        <div id="footerContainer" class="mt-8"></div>
    </main>
    <script src="../assets/js/products_page.js"></script>
    <script>
        async function loadFooter(){ try{ const r=await fetch('../html/footer.php'); const h=await r.text(); document.getElementById('footerContainer').innerHTML=h; }catch(e){ console.error('Error loading footer:', e); } }
        loadFooter();
    </script>
</body>
