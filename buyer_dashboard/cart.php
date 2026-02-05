<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_role('buyer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Cart | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body.dark-mode {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
        }
        main.main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        body:has(.sidebar.collapsed) main.main-content {
            margin-left: 80px;
            width: calc(100% - 80px);
        }
        .page-content {
            flex: 1;
            padding: 2rem;
        }
    </style>
</head>
<body class="dark-mode">
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar() {
                const response = await fetch('../html/navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                
                // Execute scripts from loaded HTML
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    newScript.innerHTML = script.innerHTML;
                    document.body.appendChild(newScript);
                });
                
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-shopping-cart"></i> Shopping Cart';
                
                // Initialize user menu after scripts execute
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') {
                        window.initializeUserMenuGlobal();
                    }
                    
                    // Setup dropdown toggle with hover
                    const userMenu = document.getElementById('userMenu');
                    const userDropdown = document.getElementById('userDropdown');
                    let userMenuTimeout;
                    
                    if (userMenu && userDropdown) {
                        // Show on hover
                        userMenu.onmouseenter = function(e) {
                            clearTimeout(userMenuTimeout);
                            userDropdown.style.display = 'block';
                            userDropdown.style.opacity = '1';
                            userDropdown.style.visibility = 'visible';
                        };
                        
                        // Hide on leave
                        userMenu.onmouseleave = function() {
                            userMenuTimeout = setTimeout(() => {
                                userDropdown.style.display = 'none';
                                userDropdown.style.opacity = '0';
                                userDropdown.style.visibility = 'hidden';
                            }, 200);
                        };
                        
                        userDropdown.onmouseenter = function() {
                            clearTimeout(userMenuTimeout);
                        };
                        
                        userDropdown.onmouseleave = function() {
                            userMenuTimeout = setTimeout(() => {
                                userDropdown.style.display = 'none';
                                userDropdown.style.opacity = '0';
                                userDropdown.style.visibility = 'hidden';
                            }, 200);
                        };
                        
                        // Click fallback
                        userMenu.onclick = function(e) {
                            e.stopPropagation();
                            const isVisible = userDropdown.style.display === 'block';
                            userDropdown.style.display = isVisible ? 'none' : 'block';
                            userDropdown.style.opacity = isVisible ? '0' : '1';
                            userDropdown.style.visibility = isVisible ? 'hidden' : 'visible';
                        };
                    }
                    
                    // Close dropdown on outside click
                    document.onclick = function(e) {
                        const userMenu = document.getElementById('userMenu');
                        const userDropdown = document.getElementById('userDropdown');
                        if (userDropdown && userMenu && !userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                            userDropdown.style.display = 'none';
                            userDropdown.style.opacity = '0';
                            userDropdown.style.visibility = 'hidden';
                        }
                    };
                    
                    // Setup dark mode toggle
                    const themeToggle = document.getElementById('themeToggle');
                    if (themeToggle) {
                        themeToggle.onclick = function() {
                            const body = document.body;
                            const icon = themeToggle.querySelector('i');
                            body.classList.toggle('dark-mode');
                            if (body.classList.contains('dark-mode')) {
                                icon.classList.remove('fa-moon');
                                icon.classList.add('fa-sun');
                                localStorage.setItem('quickmart_theme', 'dark');
                            } else {
                                icon.classList.remove('fa-sun');
                                icon.classList.add('fa-moon');
                                localStorage.setItem('quickmart_theme', 'light');
                            }
                        };
                    }
                    
                    // Load saved theme
                    const savedTheme = localStorage.getItem('quickmart_theme');
                    if (savedTheme === 'light') {
                        document.body.classList.remove('dark-mode');
                        const icon = themeToggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-moon');
                            icon.classList.add('fa-sun');
                        }
                    }
                    
                    // Setup notification modal
                    if (typeof window.setupNotificationModal === 'function') {
                        window.setupNotificationModal();
                    }
                }, 50);
            }
            loadNavbar();
        </script>
        <div class="page-content w-full">
            <div class="w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8" data-aos="fade-up">
                <div class="lg:col-span-2">
                    <h3 class="text-2xl font-semibold mb-6">Cart Items</h3>
                    <div id="cartItemsList">
                        <div class="bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)]" data-aos="fade-right">
                            <div class="text-center py-12 px-4">
                                <i class="fas fa-shopping-cart text-5xl mb-4" style="color:var(--text-muted)"></i>
                                <h3 class="text-xl font-semibold mb-2">Your Cart is Empty</h3>
                                <p class="text-sm mb-6" style="color:var(--text-secondary)">Add items to your cart to see them here.</p>
                                <button class="bg-[var(--primary-color)] hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium transition-all inline-flex items-center gap-2" onclick="window.location.href='../html/products_page.php'">
                                    <i class="fas fa-store"></i>
                                    <span>Start Shopping</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1" data-aos="fade-left">
                    <div class="bg-[var(--bg-card)] rounded-2xl p-6 border border-[var(--border-color)] sticky top-24">
                        <h3 class="text-xl font-semibold mb-4">Order Summary</h3>
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-sm" style="color:var(--text-secondary)">Subtotal</span>
                                <span class="font-semibold" id="subtotalValue">0 BDT</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm" style="color:var(--text-secondary)">Delivery Charge</span>
                                <span class="font-semibold" id="deliveryValue">0 BDT</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm" style="color:var(--text-secondary)">Coupon Discount</span>
                                <span class="font-semibold" id="discountValue">0 BDT</span>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t" style="border-color:var(--border-color)">
                                <span class="font-bold text-base">Total</span>
                                <span class="font-bold text-lg" style="color:var(--primary-color)" id="totalValue">0 BDT</span>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h4 class="text-sm font-semibold mb-2" style="color:var(--text-secondary)">
                                <i class="fas fa-ticket-alt mr-2"></i>Apply Coupon
                            </h4>
                            <div class="flex items-center gap-2">
                                <input id="couponCodeInput" type="text" placeholder="Enter coupon code" class="w-full px-3 py-2 rounded-lg border" style="background:var(--bg-secondary); border-color:var(--border-color); color:var(--text-primary);">
                                <button id="applyCouponBtn" class="bg-[var(--primary-color)] hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium transition-all">Apply</button>
                            </div>
                            <div class="text-xs mt-2" id="appliedCouponLabel" style="color:var(--text-secondary);"></div>
                        </div>

                        <div class="mb-5">
                            <h4 class="text-sm font-semibold mb-2" style="color:var(--text-secondary)">
                                <i class="fas fa-credit-card mr-2"></i>Payment Method
                            </h4>
                            <div class="space-y-2" id="paymentMethods">
                                <label class="flex items-center justify-between px-3 py-2 rounded-lg border cursor-pointer text-sm" style="border-color:var(--border-color)">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="bkash" class="accent-[var(--primary-color)]" checked>
                                        <span>bKash</span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(236,72,153,0.15);color:#ec4899">Instant</span>
                                </label>
                                <label class="flex items-center justify-between px-3 py-2 rounded-lg border cursor-pointer text-sm" style="border-color:var(--border-color)">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="nagad" class="accent-[var(--primary-color)]">
                                        <span>Nagad</span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(249,115,22,0.15);color:#f97316">Popular</span>
                                </label>
                                <label class="flex items-center justify-between px-3 py-2 rounded-lg border cursor-pointer text-sm" style="border-color:var(--border-color)">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="card" class="accent-[var(--primary-color)]">
                                        <span>Debit / Credit Card</span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(59,130,246,0.15);color:#3b82f6">Secure</span>
                                </label>
                                <label class="flex items-center justify-between px-3 py-2 rounded-lg border cursor-pointer text-sm" style="border-color:var(--border-color)">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="payment_method" value="cod" class="accent-[var(--primary-color)]">
                                        <span>Cash on Delivery</span>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(34,197,94,0.15);color:#22c55e">Recommended</span>
                                </label>
                            </div>
                        </div>

                        <button id="payNowBtn" class="w-full bg-[var(--primary-color)] hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium transition-all inline-flex items-center justify-center gap-2">
                            <i class="fas fa-lock"></i>
                            <span>Pay Now</span>
                        </button>
                        <p class="mt-2 text-[11px] text-center" style="color:var(--text-secondary)">
                            This is a demo payment. No real money will be charged.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Container -->
        <div id="footerContainer"></div>
    </main>

    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.75rem;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease;
                font-weight: 600;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }
        
        let appliedDiscount = 0;
        let appliedCouponCode = '';

        function updateCouponLabel() {
            const label = document.getElementById('appliedCouponLabel');
            if (!label) return;
            if (appliedCouponCode) {
                label.textContent = `Applied coupon: ${appliedCouponCode}`;
            } else {
                label.textContent = 'No coupon applied.';
            }
        }

        // Render cart items using server-side buyer cart
        function renderCartItems() {
            const cartItemsList = document.getElementById('cartItemsList');
            fetch('cart_action.php?action=get', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) {
                        console.error('Failed to load cart:', data && data.message);
                        cartItemsList.innerHTML = `
                            <div class="bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)]" data-aos="fade-right">
                                <div class="text-center py-12 px-4">
                                    <i class="fas fa-triangle-exclamation text-5xl mb-4" style="color:var(--danger-color)"></i>
                                    <h3 class="text-xl font-semibold mb-2">Unable to load cart</h3>
                                    <p class="text-sm mb-6" style="color:var(--text-secondary)">Please refresh the page or try again later.</p>
                                </div>
                            </div>`;
                        appliedDiscount = 0;
                        appliedCouponCode = '';
                        updateOrderSummary(0, 0, 0);
                        updateCouponLabel();
                        return;
                    }

                    const items = data.items || [];
                    if (items.length === 0) {
                        cartItemsList.innerHTML = `
                            <div class="bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)]" data-aos="fade-right">
                                <div class="text-center py-12 px-4">
                                    <i class="fas fa-shopping-cart text-5xl mb-4" style="color:var(--text-muted)"></i>
                                    <h3 class="text-xl font-semibold mb-2">Your Cart is Empty</h3>
                                    <p class="text-sm mb-6" style="color:var(--text-secondary)">Add items to your cart to see them here.</p>
                                    <button class="bg-[var(--primary-color)] hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium transition-all inline-flex items-center gap-2" onclick="window.location.href='../html/products_page.php'">
                                        <i class="fas fa-store"></i>
                                        <span>Start Shopping</span>
                                    </button>
                                </div>
                            </div>`;
                        appliedDiscount = 0;
                        appliedCouponCode = '';
                        updateOrderSummary(0, 0, 0);
                        updateCouponLabel();
                        if (typeof AOS !== 'undefined') AOS.refresh();
                        return;
                    }

                    cartItemsList.innerHTML = items.map((item, index) => `
                        <div class="bg-[var(--bg-card)] rounded-2xl p-6 border border-[var(--border-color)] mb-4" data-aos="fade-right" data-aos-delay="${index * 50}">
                            <div class="flex gap-6 items-center">
                                <div class="flex-shrink-0 w-[120px] h-[120px] rounded-xl overflow-hidden bg-[var(--bg-secondary)]">
                                    <img src="${item.image_url || ''}" alt="${item.name}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-lg font-semibold mb-1">${item.name}</h3>
                                    <p class="text-sm mb-2" style="color:var(--text-secondary)">In stock: ${item.stock_qty ?? 0}</p>
                                    <div class="text-xl font-bold mb-3" style="color:var(--primary-color)">৳ ${Number(item.price).toLocaleString()}</div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-2 border rounded-lg p-1" style="border-color:var(--border-color)">
                                            <button onclick="updateCartItem(${item.product_id}, ${item.quantity - 1})" class="bg-transparent hover:bg-[var(--bg-secondary)] border-0 cursor-pointer px-3 py-1 text-lg rounded transition-colors" style="color:var(--text-primary)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="px-4 font-semibold min-w-[40px] text-center">${item.quantity}</span>
                                            <button onclick="updateCartItem(${item.product_id}, ${item.quantity + 1})" class="bg-transparent hover:bg-[var(--bg-secondary)] border-0 cursor-pointer px-3 py-1 text-lg rounded transition-colors" style="color:var(--text-primary)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button onclick="removeFromCart(${item.product_id})" class="bg-transparent hover:opacity-80 border-0 cursor-pointer px-3 py-2 rounded transition-opacity" style="color:var(--danger-color)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="font-bold text-2xl" style="color:var(--primary-color)">
                                    ৳ ${(Number(item.price) * item.quantity).toLocaleString()}
                                </div>
                            </div>
                        </div>
                    `).join('');

                    const subtotal = typeof data.total === 'number'
                        ? data.total
                        : items.reduce((sum, it) => sum + (Number(it.price) * it.quantity), 0);
                    const delivery = subtotal > 0 ? 100 : 0;
                    appliedDiscount = typeof data.discount === 'number' ? data.discount : 0;
                    appliedCouponCode = data.coupon_code || '';
                    updateOrderSummary(subtotal, delivery, appliedDiscount);
                    updateCouponLabel();

                    if (typeof AOS !== 'undefined') AOS.refresh();
                })
                .catch(err => {
                    console.error('Cart fetch error:', err);
                    showNotification('Could not load cart. Please try again.', 'error');
                });
        }
        
        function updateOrderSummary(subtotal, delivery, discount = 0) {
            const total = Math.max(0, subtotal + delivery - discount);
            const subtotalEl = document.getElementById('subtotalValue');
            const deliveryEl = document.getElementById('deliveryValue');
            const discountEl = document.getElementById('discountValue');
            const totalEl = document.getElementById('totalValue');
            
            if (subtotalEl) subtotalEl.textContent = subtotal.toLocaleString() + ' BDT';
            if (deliveryEl) deliveryEl.textContent = delivery.toLocaleString() + ' BDT';
            if (discountEl) discountEl.textContent = discount.toLocaleString() + ' BDT';
            if (totalEl) totalEl.textContent = total.toLocaleString() + ' BDT';
        }

        function applyCoupon() {
            const input = document.getElementById('couponCodeInput');
            const code = input ? input.value.trim() : '';
            if (!code) {
                showNotification('Enter a coupon code.', 'error');
                return;
            }

            const form = new URLSearchParams();
            form.append('action', 'apply_coupon');
            form.append('code', code);

            fetch('cart_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: form.toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) {
                        showNotification(data && data.message ? data.message : 'Coupon failed.', 'error');
                        return;
                    }
                    appliedDiscount = typeof data.discount === 'number' ? data.discount : 0;
                    appliedCouponCode = data.code || code.toUpperCase();
                    const subtotal = typeof data.total_before === 'number' ? data.total_before : 0;
                    const delivery = subtotal > 0 ? 100 : 0;
                    updateOrderSummary(subtotal, delivery, appliedDiscount);
                    updateCouponLabel();
                    showNotification(`Coupon ${appliedCouponCode} applied!`, 'success');
                })
                .catch(err => {
                    console.error('Apply coupon error:', err);
                    showNotification('Could not apply coupon.', 'error');
                });
        }
        
        function updateCartItem(productId, quantity) {
            const form = new URLSearchParams();
            form.append('action', 'update');
            form.append('product_id', String(productId));
            form.append('quantity', String(quantity));

            fetch('cart_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: form.toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) {
                        showNotification(data && data.message ? data.message : 'Could not update cart.', 'error');
                        return;
                    }
                    renderCartItems();
                })
                .catch(err => {
                    console.error('Update cart error:', err);
                    showNotification('Could not update cart.', 'error');
                });
        }

        function removeFromCart(productId) {
            const form = new URLSearchParams();
            form.append('action', 'remove');
            form.append('product_id', String(productId));

            fetch('cart_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: form.toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (!data || !data.success) {
                        showNotification(data && data.message ? data.message : 'Could not remove item.', 'error');
                        return;
                    }
                    showNotification('Item removed from cart', 'info');
                    renderCartItems();
                })
                .catch(err => {
                    console.error('Remove cart item error:', err);
                    showNotification('Could not remove item.', 'error');
                });
        }

        // Load cart from server when page is ready
        window.addEventListener('load', () => {
            renderCartItems();
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 600,
                    once: true,
                    offset: 50
                });
            }
        });
    </script>
    <script>
        // Dummy payment handler using current cart and selected payment method
        async function handlePayment() {
            const payBtn = document.getElementById('payNowBtn');
            if (!payBtn) return;

            payBtn.disabled = true;
            payBtn.classList.add('opacity-70');

            try {
                const methodInput = document.querySelector('input[name="payment_method"]:checked');
                if (!methodInput) {
                    showNotification('Please select a payment method.', 'error');
                    return;
                }

                const method = methodInput.value;

                // Get latest cart + total from server
                const res = await fetch('cart_action.php?action=get', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (!data || !data.success || !Array.isArray(data.items) || data.items.length === 0) {
                    showNotification('Your cart is empty. Add items first.', 'error');
                    return;
                }

                const subtotal = typeof data.total === 'number'
                    ? data.total
                    : data.items.reduce((sum, it) => sum + (Number(it.price) * it.quantity), 0);
                const delivery = subtotal > 0 ? 100 : 0;
                const total = subtotal + delivery;

                updateOrderSummary(subtotal, delivery, appliedDiscount);

                // Simulate short processing delay
                await new Promise(resolve => setTimeout(resolve, 800));

                // Checkout with wallet + inventory updates
                const checkoutForm = new URLSearchParams();
                checkoutForm.append('action', 'checkout');
                checkoutForm.append('payment_method', method);

                const checkoutRes = await fetch('cart_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: checkoutForm.toString()
                });
                const checkoutData = await checkoutRes.json();
                if (!checkoutData || !checkoutData.success) {
                    showNotification(checkoutData && checkoutData.message ? checkoutData.message : 'Checkout failed.', 'error');
                    return;
                }

                // Refresh cart UI and global cart badges if available
                renderCartItems();
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }

                const methodLabelMap = {
                    bkash: 'bKash',
                    nagad: 'Nagad',
                    card: 'Card',
                    cod: 'Cash on Delivery'
                };
                const label = methodLabelMap[method] || 'selected method';

                showNotification(`Order #${checkoutData.order_id} placed with ${label}. Total ${total.toLocaleString()} BDT.`, 'success');
                window.location.href = `./track_product.php?order_id=${checkoutData.order_id}`;
            } catch (err) {
                console.error('Payment simulation error:', err);
                showNotification('Something went wrong while processing payment.', 'error');
            } finally {
                const btn = document.getElementById('payNowBtn');
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-70');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const payBtn = document.getElementById('payNowBtn');
            if (payBtn) {
                payBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    handlePayment();
                });
            }
            const applyBtn = document.getElementById('applyCouponBtn');
            if (applyBtn) {
                applyBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    applyCoupon();
                });
            }
            const input = document.getElementById('couponCodeInput');
            if (input) {
                const savedCode = localStorage.getItem('quickmart_coupon_code');
                if (savedCode && !input.value) {
                    input.value = savedCode;
                }
            }
        });
    </script>
    
    <script>
        // Load global footer
        async function loadFooter() {
            try {
                const response = await fetch('../html/footer.php');
                const html = await response.text();
                document.getElementById('footerContainer').innerHTML = html;
            } catch (error) {
                console.error('Error loading footer:', error);
            }
        }
        loadFooter();
    </script>
</body>
</html>
