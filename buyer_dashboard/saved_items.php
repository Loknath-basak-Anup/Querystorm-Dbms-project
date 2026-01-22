<?php
require_once __DIR__ . '/../includes/session.php';
require_role('buyer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Saved Items | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <link rel="stylesheet" href="../assets/css/quickmart-fixes.css" />
    <style>
        body.dark-mode { display:flex; flex-direction:row; min-height:100vh; }
        main.main-content { margin-left:280px; width:calc(100% - 280px); transition: margin-left 0.3s ease, width 0.3s ease; min-height:100vh; }
        body:has(.sidebar.collapsed) main.main-content { margin-left:80px; width:calc(100% - 80px); }
    </style>
</head>
<body class="dark-mode">
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar(){ const r=await fetch('../html/navbar.php'); const h=await r.text(); document.getElementById('navbarContainer').innerHTML=h; const scripts=document.getElementById('navbarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); const pageTitle=document.querySelector('.page-title-navbar'); if(pageTitle) pageTitle.innerHTML='<i class="fas fa-bookmark"></i> Saved Items'; setTimeout(()=>{ if(typeof window.initializeUserMenuGlobal==='function') window.initializeUserMenuGlobal(); },50);} loadNavbar();
        </script>
        <script>
            async function loadSidebar(){ try{ const r=await fetch('../html/leftsidebar.php'); const h=await r.text(); document.getElementById('sidebarContainer').innerHTML=h; const scripts=document.getElementById('sidebarContainer').querySelectorAll('script'); scripts.forEach(script=>{ const s=document.createElement('script'); s.innerHTML=script.innerHTML; document.body.appendChild(s); }); }catch(e){ console.error('Error loading sidebar:', e); } } loadSidebar();
        </script>
        <div class="page-content">
            <div class="products-grid" id="savedItemsGrid" data-aos="fade-up">
                <!-- Saved items will be rendered by JavaScript using quickmart_favorites -->
            </div>
        </div>
        <div id="footerContainer" class="mt-8"></div>
    </main>
    <div id="toastContainer" style="position:fixed; right:18px; bottom:18px; display:flex; flex-direction:column; gap:10px; z-index:10000;"></div>
    <script src="../assets/js/products_page.js"></script>
    <script>
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

        function renderSavedItems() {
            const grid = document.getElementById('savedItemsGrid');
            if (!grid) return;

            const favorites = JSON.parse(localStorage.getItem('quickmart_favorites') || '[]');
            if (!Array.isArray(favorites) || favorites.length === 0) {
                grid.innerHTML = `
                    <div class="product-card" data-aos="zoom-in">
                        <div class="product-info" style="text-align:center;padding:3rem 1rem">
                            <i class="fas fa-heart" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem"></i>
                            <h3 class="product-title">No Saved Items Yet</h3>
                            <p class="product-creator">Start adding your favorite items to see them here.</p>
                            <div class="product-footer" style="justify-content:center;margin-top:1.5rem">
                                <button class="btn-add-cart" onclick="window.location.href='../html/products_page.php'">
                                    <i class="fas fa-store"></i>
                                    <span>Browse Products</span>
                                </button>
                            </div>
                        </div>
                    </div>`;
                if (typeof AOS !== 'undefined') AOS.refresh();
                return;
            }

            if (!Array.isArray(window.products) || window.products.length === 0) {
                grid.innerHTML = `
                    <div class="product-card" data-aos="zoom-in">
                        <div class="product-info" style="text-align:center;padding:3rem 1rem">
                            <i class="fas fa-circle-exclamation" style="font-size:3rem;color:var(--danger-color);margin-bottom:1rem"></i>
                            <h3 class="product-title">Could not load saved items</h3>
                            <p class="product-creator">Please go back to the marketplace and try again.</p>
                            <div class="product-footer" style="justify-content:center;margin-top:1.5rem">
                                <button class="btn-add-cart" onclick="window.location.href='../html/products_page.php'">
                                    <i class="fas fa-store"></i>
                                    <span>Go to Marketplace</span>
                                </button>
                            </div>
                        </div>
                    </div>`;
                if (typeof AOS !== 'undefined') AOS.refresh();
                return;
            }

            const savedProducts = window.products.filter(p => favorites.includes(p.id));

            if (savedProducts.length === 0) {
                grid.innerHTML = `
                    <div class="product-card" data-aos="zoom-in">
                        <div class="product-info" style="text-align:center;padding:3rem 1rem">
                            <i class="fas fa-heart" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem"></i>
                            <h3 class="product-title">No Saved Items Found</h3>
                            <p class="product-creator">Some items may no longer be available.</p>
                            <div class="product-footer" style="justify-content:center;margin-top:1.5rem">
                                <button class="btn-add-cart" onclick="window.location.href='../html/products_page.php'">
                                    <i class="fas fa-store"></i>
                                    <span>Browse Products</span>
                                </button>
                            </div>
                        </div>
                    </div>`;
                if (typeof AOS !== 'undefined') AOS.refresh();
                return;
            }

            grid.innerHTML = savedProducts.map((product, index) => `
                <div class="product-card" data-aos="zoom-in-up" data-aos-delay="${index * 40}" data-product-id="${product.id}">
                    <div class="product-image-container">
                        <img src="${product.image}" alt="${product.name}" class="product-image">
                        <button class="product-favorite active" onclick="toggleFavorite(${product.id}); renderSavedItems(); event.stopPropagation();">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">${product.name}</h3>
                        <p class="product-creator">by ${product.creator}</p>
                        <div class="product-footer">
                            <div class="product-price">
                                <span class="price-label">Price</span>
                                <div class="price-value">
                                    <i class="fas fa-tag"></i>
                                    <span>${product.price.toLocaleString()} BDT</span>
                                </div>
                            </div>
                            <button class="btn-add-cart" onclick="addToCart(${product.id}); event.stopPropagation();">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Add to Cart</span>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');

            // Click on card opens product details if available
            grid.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', () => {
                    const id = parseInt(card.getAttribute('data-product-id'));
                    if (id && typeof showProductDetails === 'function') {
                        showProductDetails(id);
                    }
                });
            });

            if (typeof AOS !== 'undefined') AOS.refresh();
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Wait a bit to ensure products_page.js has normalized window.products
            setTimeout(renderSavedItems, 150);
        });
    </script>
</body>
</html>
