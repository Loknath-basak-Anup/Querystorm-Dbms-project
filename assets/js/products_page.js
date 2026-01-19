// ===== Sound Effects =====
function playSound(type) {
    // Use Web Audio API for professional sounds
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    if (type === 'success') {
        // Success sound: pleasant ascending tones
        oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // E5
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } else if (type === 'add') {
        // Add to cart: quick pop
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.15);
    } else if (type === 'coupon') {
        // Coupon apply: cheerful chime
        oscillator.frequency.setValueAtTime(1046.50, audioContext.currentTime); // C6
        oscillator.frequency.setValueAtTime(1318.51, audioContext.currentTime + 0.08); // E6
        oscillator.frequency.setValueAtTime(1567.98, audioContext.currentTime + 0.16); // G6
        gainNode.gain.setValueAtTime(0.25, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.4);
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.4);
    }
}

// ===== Navbar Loading =====
async function loadNavbar() {
    try {
        const path = window.location.pathname;
        let navbarPath = 'navbar.php';
        if (path.includes('/html/')) {
            navbarPath = 'navbar.php';
        } else if (path.includes('/buyer_dashboard/') || path.includes('/seller_dashboard/') || path.includes('/buyer/') || path.includes('/seller/')) {
            navbarPath = '../html/navbar.php';
        } else {
            navbarPath = './html/navbar.php';
        }
        const response = await fetch(navbarPath);
        const html = await response.text();
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', html);
            initializeNavbar();
        }
    } catch (error) {
        console.error('Error loading navbar:', error);
    }
}

function initializeNavbar() {
    const menuToggle = document.getElementById('menuToggle');
    const dashboardBtn = document.querySelector('.dashboard-nav-btn');
    const cartBtn = document.getElementById('cartBtn');
    const pageTitle = document.querySelector('.page-title-navbar');
    
    // Set page title based on current page
    const path = window.location.pathname;
    const fileName = path.split('/').pop().replace('.php', '');
    const titles = {
        'products_page': 'Marketplace',
        'buyer_dashboard': 'Dashboard',
        'seller_dashboard': 'Dashboard',
        'wallet': 'Wallet',
        'cart': 'Shopping Cart',
        'saved_items': 'Saved Items',
        'settings': 'Settings',
        'history': 'Order History',
        'more': 'More',
        'profile': 'My Profile'
    };
    if (pageTitle) {
        pageTitle.textContent = titles[fileName] || 'QuickMart';
    }
    
    // Dashboard button routing
    if (dashboardBtn) {
        dashboardBtn.onclick = () => {
            const userRole = localStorage.getItem('userRole') || 'buyer';
            if (path.includes('/buyer_dashboard/') || path.includes('/buyer/')) {
                window.location.href = './buyer_dashboard.php';
            } else if (path.includes('/seller_dashboard/') || path.includes('/seller/')) {
                window.location.href = './seller_dashboard.php';
            } else {
                window.location.href = userRole === 'seller' ? './seller_dashboard/seller_dashboard.php' : './buyer_dashboard/buyer_dashboard.php';
            }
        };
    }
    
    // Cart button handler for navbar
    if (cartBtn) {
        cartBtn.onclick = () => {
            if (path.includes('/buyer_dashboard/') || path.includes('/buyer/')) {
                window.location.href = '../buyer_dashboard/cart.php';
            } else if (path.includes('/seller_dashboard/') || path.includes('/seller/')) {
                window.location.href = '../buyer_dashboard/cart.php';
            } else if (path.includes('/html/')) {
                window.location.href = '../buyer_dashboard/cart.php';
            } else {
                window.location.href = 'buyer_dashboard/cart.php';
            }
        };
    }
    
    // Menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                if (window.innerWidth <= 992) {
                    sidebar.classList.toggle('show');
                    const overlayElement = document.querySelector('.sidebar-overlay');
                    if (overlayElement) {
                        overlayElement.classList.toggle('active');
                    }
                } else {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                }
            }
        });
    }
}

// ===== Products Data =====
const existingProducts = window.products;
let products = Array.isArray(existingProducts) ? existingProducts : [];

function normalizeImageUrl(url) {
    const value = (url || '').toString().trim();
    if (!value) return '';
    const placeholderMatch = value.match(/^(?:https?:\/\/)?(\d+x\d+\?text=.+)$/i);
    if (placeholderMatch) {
        return `https://via.placeholder.com/${placeholderMatch[1]}`;
    }
    if (/^https?:\/\//i.test(value) || value.startsWith('//')) {
        return value;
    }
    if (value.startsWith('/')) {
        return value;
    }
    const baseMeta = document.querySelector('meta[name="app-base"]');
    const base = (baseMeta && baseMeta.content) ? baseMeta.content : '/QuickMart';
    const baseNormalized = base.endsWith('/') ? base : `${base}/`;
    return `${baseNormalized}${value.replace(/^\/+/, '')}`;
}

function fixImageSources(root = document) {
    const baseMeta = document.querySelector('meta[name="app-base"]');
    const base = (baseMeta && baseMeta.content) ? baseMeta.content : '/QuickMart';
    const baseNormalized = base.endsWith('/') ? base : `${base}/`;

    root.querySelectorAll('img').forEach(img => {
        const raw = (img.getAttribute('src') || '').trim();
        if (!raw) return;

        const placeholderMatch = raw.match(/^(?:https?:\/\/)?(\d+x\d+\?text=.+)$/i) ||
            raw.match(/^\/\/(\d+x\d+\?text=.+)$/i);
        if (placeholderMatch) {
            img.src = `https://via.placeholder.com/${placeholderMatch[1]}`;
            return;
        }

        if (/^\/?images\//i.test(raw) && !raw.startsWith(base)) {
            img.src = `${baseNormalized}${raw.replace(/^\/+/, '')}`;
        }
    });
}

Object.defineProperty(window, 'products', {
    get() {
        return products;
    },
    set(value) {
        products = Array.isArray(value) ? value : [];
        products = products.map(product => ({
            ...product,
            image: normalizeImageUrl(product.image || product.image_url || '')
        }));
        const productsGrid = document.getElementById('productsGrid');
        if (productsGrid && typeof renderProducts === 'function') {
            renderProducts();
        }
    }
});

if (Array.isArray(existingProducts)) {
    window.products = existingProducts;
}

// ===== State Management =====
let cart = JSON.parse(localStorage.getItem('quickmart_cart') || '[]');
let favorites = JSON.parse(localStorage.getItem('quickmart_favorites') || '[]');
let currentCategory = 'all';
let currentSort = 'newest';
let priceMin = 0;
let priceMax = 100000;
let displayedProducts = 12;
let currentSearchTerm = '';
let sidebarCollapsed = false;

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', () => {
    loadSidebar();
    initializeApp();
    fixImageSources();
    
    // Initialize AOS animations after script loads
    setTimeout(() => {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 100
            });
        }
    }, 100);
});

// ===== Load Sidebar =====
async function loadSidebar() {
    try {
        // Resolve sidebar path across pages
        const path = window.location.pathname;
        let sidebarPath = 'leftsidebar.php';
        if (path.includes('/html/')) {
            sidebarPath = 'leftsidebar.php';
        } else if (path.includes('/buyer_dashboard/') || path.includes('/seller_dashboard/')) {
            sidebarPath = '../html/leftsidebar.php';
        } else if (path.includes('/buyer/') || path.includes('/seller/')) {
            sidebarPath = '../html/leftsidebar.php';
        } else {
            // fallback for root index.php or other locations
            sidebarPath = './html/leftsidebar.php';
        }
        const response = await fetch(sidebarPath);
        const html = await response.text();
        document.getElementById('sidebarContainer').innerHTML = html;
        
        // Filter sidebar based on role
        filterSidebarByRole();
        
        // Initialize sidebar after loading
        initializeSidebar();
    } catch (error) {
        console.error('Error loading sidebar:', error);
    }
}

// ===== Filter Sidebar by Role =====
function filterSidebarByRole() {
    const role = localStorage.getItem('userRole') || 'buyer';
    const sidebar = document.getElementById('sidebar');
    
    if (!sidebar) return;
    
    // Update "My Profile" section title based on role
    const profileSection = Array.from(sidebar.querySelectorAll('.menu-section')).find(section => {
        const titleElement = section.querySelector('.menu-title');
        return titleElement && titleElement.textContent.trim() === 'My Account';
    });
    
    if (profileSection) {
        const titleEl = profileSection.querySelector('.menu-title');
        if (role === 'seller') {
            titleEl.textContent = 'Seller Account';
        } else {
            titleEl.textContent = 'My Account';
        }
    }
    
    // Role-based menu filtering
    if (role === 'seller') {
        // Hide Cart and Saved for sellers (sellers don't buy)
        const cartItem = sidebar.querySelector('[data-page="cart"]');
        const savedItem = sidebar.querySelector('[data-page="saved"]');
        if (cartItem) cartItem.style.display = 'none';
        if (savedItem) savedItem.style.display = 'none';
    } else if (role === 'buyer') {
        // Hide Dashboard button for buyers
        const dashboardItem = sidebar.querySelector('[data-page="dashboard"]');
        if (dashboardItem) dashboardItem.style.display = 'none';
    }
}

// ===== Initialize Sidebar =====
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    
    // Highlight active page
    const pagePath = window.location.pathname;
    const fileName = pagePath.split('/').pop().replace('.php', '');
    
    // Map of file names to menu item data-page values
    const pageMap = {
        'products_page': 'market',
        'buyer_dashboard': 'dashboard',
        'seller_dashboard': 'dashboard',
        'wallet': 'wallet',
        'cart': 'cart',
        'saved_items': 'saved',
        'settings': 'settings',
        'history': 'history',
        'more': 'message',
        'buyer_chat_to_seller': 'message',
        'seller_chat_to_buyer': 'message',
        'profile': 'profile'
    };
    
    const currentPage = pageMap[fileName] || 'market';
    
    // Remove all active states first
    document.querySelectorAll('.menu-item').forEach(link => {
        link.classList.remove('active');
        link.style.background = '';
        link.style.color = '';
    });
    
    // Set active state for current page
    const activeLink = document.querySelector(`.menu-item[data-page="${currentPage}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
        activeLink.style.background = 'var(--primary-color)';
        activeLink.style.color = 'white';
    }
    
    // Create overlay for mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Menu toggle click handler
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                // Mobile: Show/hide sidebar
                sidebar.classList.toggle('show');
                overlay.classList.toggle('active');
            } else {
                // Desktop: Toggle collapsed state
                sidebar.classList.toggle('collapsed');
                sidebarCollapsed = !sidebarCollapsed;
                localStorage.setItem('sidebar_collapsed', sidebarCollapsed);
            }
        });
    }
    
    // Overlay click closes sidebar on mobile
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
    });
    
    // Load saved sidebar state
    const savedState = localStorage.getItem('sidebar_collapsed');
    if (savedState === 'true' && window.innerWidth > 992) {
        sidebar.classList.add('collapsed');
        sidebarCollapsed = true;
    }
    
    // Update sidebar cart count
    updateSidebarCartCount();

    // Wire sidebar navigation by role and current location
    const role = localStorage.getItem('userRole') || 'buyer';
    const currentPath = window.location.pathname;

    function getBasePath() {
        // Extract base path from current location
        const path = window.location.pathname;
        // Find project root by looking for known folders
        const match = path.match(/(.*\/QuickMart\/)/);
        if (match) {
            return match[1];
        }
        // Fallback: count folders up from current location
        if (path.includes('/buyer_dashboard/') || path.includes('/seller_dashboard/')) {
            return '../';
        }
        if (path.includes('/buyer/') || path.includes('/seller/')) {
            return '../';
        }
        if (path.includes('/html/')) {
            return '../';
        }
        return './';
    }

    function routeFor(pageKey) {
        const isSeller = role === 'seller';
        const base = getBasePath();
        switch (pageKey) {
            case 'dashboard':
                return isSeller ? `${base}seller_dashboard/seller_dashboard.php` : `${base}buyer_dashboard/buyer_dashboard.php`;
            case 'settings':
                return isSeller ? `${base}seller_dashboard/settings.php` : `${base}buyer_dashboard/settings.php`;
            case 'message':
                return isSeller ? `${base}seller_dashboard/seller_chat_to_buyer.php` : `${base}buyer_dashboard/buyer_chat_to_seller.php`;
            case 'market':
                return `${base}html/products_page.php`;
            case 'saved':
                return `${base}buyer_dashboard/saved_items.php`;
            case 'cart':
                return `${base}buyer_dashboard/cart.php`;
            case 'wallet':
                return isSeller ? `${base}seller_dashboard/wallet.php` : `${base}buyer_dashboard/wallet.php`;
            case 'history':
                return isSeller ? `${base}seller_dashboard/history.php` : `${base}buyer_dashboard/history.php`;
            default:
                return `${base}html/products_page.php`;
        }
    }

    document.querySelectorAll('.menu-item[data-page]').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const pageKey = item.getAttribute('data-page');
            const dest = routeFor(pageKey);
            // Guest protection: open guest modal instead of navigate
            const loggedIn = localStorage.getItem('isLoggedIn') === 'true';
            if (!loggedIn && pageKey !== 'market') {
                showGuestModal();
                // close sidebar if on mobile
                const overlay = document.querySelector('.sidebar-overlay');
                if (overlay) overlay.classList.remove('active');
                sidebar.classList.remove('show');
                return;
            }
            window.location.href = dest;
        });
    });

    // Profile button handler
    const profileBtn = document.getElementById('profileBtn');
    if (profileBtn) {
        profileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const role = localStorage.getItem('userRole') || 'buyer';
            const loggedIn = localStorage.getItem('isLoggedIn') === 'true';
            if (!loggedIn) {
                showGuestModal();
                return;
            }
            const basePath = getBasePath();
            window.location.href = `${basePath}${role}/profile.php`;
        });
    }
}

// Cart helpers for logged-in buyers (server cart)
function isBuyerLoggedIn() {
    return localStorage.getItem('isLoggedIn') === 'true' && localStorage.getItem('userRole') === 'buyer';
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

function setCartBadges(count) {
    const cartCount = document.getElementById('cartCount');
    const cartCountHeader = document.getElementById('cartCountHeader');
    const sidebarCartCount = document.getElementById('sidebarCartCount');
    if (cartCount) cartCount.textContent = count;
    if (cartCountHeader) cartCountHeader.textContent = count;
    if (sidebarCartCount) sidebarCartCount.textContent = count;
}

async function refreshCartCountFromServer() {
    try {
        const url = getCartApiUrl() + '?action=get';
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (!data || !data.success) {
            console.warn('Cart API error:', data && data.message);
            return;
        }
        const items = Array.isArray(data.items) ? data.items : [];
        const count = items.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 0), 0);
        setCartBadges(count);
    } catch (err) {
        console.error('Failed to refresh cart count from server:', err);
    }
}

// Update sidebar cart count
function updateSidebarCartCount() {
    if (isBuyerLoggedIn()) {
        refreshCartCountFromServer();
        return;
    }
    const count = cart.reduce((total, item) => total + item.quantity, 0);
    setCartBadges(count);
}

function initializeApp() {
    const productsGrid = document.getElementById('productsGrid');
    if (productsGrid) {
        renderProducts();
    }
    attachEventListeners();
    updateCartCount();
    
    // Load saved cart from localStorage
    const savedCart = localStorage.getItem('quickmart_cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartCount();
        renderCartItems();
    }
}

// ===== Event Listeners =====
function attachEventListeners() {
    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) themeToggle.addEventListener('click', toggleTheme);

    // Category Modal
    const categoryBtn = document.getElementById('categoryBtn');
    const categoryModal = document.getElementById('categoryModal');
    if (categoryBtn && categoryModal) categoryBtn.addEventListener('click', () => openModal(categoryModal));

    // Price Range Modal
    const priceRangeBtn = document.getElementById('priceRangeBtn');
    const priceRangeModal = document.getElementById('priceRangeModal');
    if (priceRangeBtn && priceRangeModal) priceRangeBtn.addEventListener('click', () => openModal(priceRangeModal));

    // Filter & Sort Modal
    const filterSortBtn = document.getElementById('filterSortBtn');
    const filterSortModal = document.getElementById('filterSortModal');
    if (filterSortBtn && filterSortModal) filterSortBtn.addEventListener('click', () => openModal(filterSortModal));

    // Cart Sidebar
    const cartBtn = document.getElementById('cartBtn');
    const cartSidebar = document.getElementById('cartSidebar');
    const closeCart = document.getElementById('closeCart');
    if (cartBtn && cartSidebar) cartBtn.addEventListener('click', () => cartSidebar.classList.add('active'));
    if (closeCart && cartSidebar) closeCart.addEventListener('click', () => cartSidebar.classList.remove('active'));

    // Modal Close Buttons
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            if (modal) closeModal(modal);
        });
    });

    // Category Selection (both modal and horizontal scroll)
    document.querySelectorAll('.category-card, .category-item').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.category-item').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            
            // Find corresponding item in other section and mark it active too
            const category = card.dataset.category;
            document.querySelectorAll(`[data-category="${category}"]`).forEach(el => {
                el.classList.add('active');
            });
            
            currentCategory = category;
            displayedProducts = 12;
            if (typeof renderProducts === 'function') renderProducts();
            if (categoryModal) closeModal(categoryModal);
        });
    });
    
    // See All Categories button
    const seeAllCategories = document.getElementById('seeAllCategories');
    if (seeAllCategories && categoryModal) {
        seeAllCategories.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(categoryModal);
        });
    }

    // Price Range Slider
    const priceSlider = document.getElementById('priceSlider');
    const sliderValue = document.getElementById('sliderValue');
    if (priceSlider && sliderValue) {
        priceSlider.addEventListener('input', (e) => {
            sliderValue.textContent = parseInt(e.target.value).toLocaleString();
        });
    }

    // Apply Price Range
    const applyPriceRange = document.getElementById('applyPriceRange');
    if (applyPriceRange) {
        applyPriceRange.addEventListener('click', () => {
            const minPriceEl = document.getElementById('minPrice');
            const maxPriceEl = document.getElementById('maxPrice');
            const sliderEl = document.getElementById('priceSlider');
            const minVal = minPriceEl ? minPriceEl.value : '';
            const maxVal = maxPriceEl ? maxPriceEl.value : '';
            const sliderVal = sliderEl ? sliderEl.value : '100000';
            priceMin = parseInt(minVal) || 0;
            priceMax = parseInt(maxVal) || parseInt(sliderVal || '100000');
            displayedProducts = 12;
            if (typeof renderProducts === 'function') renderProducts();
            if (priceRangeModal) closeModal(priceRangeModal);
        });
    }

    // Apply Filters
    const applyFilters = document.getElementById('applyFilters');
    if (applyFilters) {
        applyFilters.addEventListener('click', () => {
            const sortRadio = document.querySelector('input[name="sort"]:checked');
            if (sortRadio) {
                currentSort = sortRadio.value;
                displayedProducts = 12;
                if (typeof renderProducts === 'function') renderProducts();
                if (filterSortModal) closeModal(filterSortModal);
            }
        });
    }

    // Load More
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            displayedProducts += 12;
            if (typeof renderProducts === 'function') renderProducts();
        });
    }

    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = (e.target.value || '').toString();
            currentSearchTerm = searchTerm;
            displayedProducts = 12;
            if (typeof renderProducts === 'function') renderProducts();
        });
    }
}

// ===== Theme Toggle =====
function toggleTheme() {
    const body = document.body;
    const themeIcon = document.querySelector('#themeToggle i');
    
    body.classList.toggle('dark-mode');
    
    if (body.classList.contains('dark-mode')) {
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
        localStorage.setItem('quickmart_theme', 'dark');
    } else {
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
        localStorage.setItem('quickmart_theme', 'light');
    }
}

// Load saved theme
const savedTheme = localStorage.getItem('quickmart_theme');
if (savedTheme === 'light') {
    document.body.classList.remove('dark-mode');
    document.querySelector('#themeToggle i').classList.remove('fa-moon');
    document.querySelector('#themeToggle i').classList.add('fa-sun');
}

// ===== Modal Functions =====
function openModal(modal) {
    if (!modal) return;
    modal.style.display = 'block';
    modal.classList.add('active');
    
    // Re-attach close handlers when modal opens
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');
    
    if (closeBtn) {
        closeBtn.removeEventListener('click', handleModalClose);
        closeBtn.addEventListener('click', handleModalClose);
    }
    if (overlay) {
        overlay.removeEventListener('click', handleModalClose);
        overlay.addEventListener('click', handleModalClose);
    }
}

function closeModal(modal) {
    if (!modal) return;
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function handleModalClose(e) {
    const modal = e.target.closest('.modal');
    if (modal) {
        closeModal(modal);
    }
}

// ===== Render Products =====
function renderProducts(searchTerm = '') {
    const productsGrid = document.getElementById('productsGrid');
    
    // Reload favorites from localStorage to ensure sync
    favorites = JSON.parse(localStorage.getItem('quickmart_favorites') || '[]');
    
    const resolvedTerm = (searchTerm || currentSearchTerm || '').toString().toLowerCase().trim();
    const searchTokens = resolvedTerm === '' ? [] : resolvedTerm.split(/\s+/).filter(Boolean);

    // Filter products
    let filteredProducts = products.filter(product => {
        const matchesCategory = currentCategory === 'all' ||
            (product.category || '').toLowerCase() === currentCategory.toLowerCase() ||
            (product.subcategory || '').toLowerCase() === currentCategory.toLowerCase();
        const matchesPrice = product.price >= priceMin && product.price <= priceMax;
        const haystack = [
            product.name,
            product.creator,
            product.category,
            product.subcategory,
            product.description,
            (product.price ?? '').toString()
        ].filter(Boolean).join(' ').toLowerCase();
        const matchesSearch = searchTokens.length === 0 || searchTokens.every(token => haystack.includes(token));
        return matchesCategory && matchesPrice && matchesSearch;
    });

    // Sort products
    filteredProducts = sortProducts(filteredProducts);

    // Limit displayed products
    const productsToShow = filteredProducts.slice(0, displayedProducts);

    // Check if no products available
    if (productsToShow.length === 0) {
        productsGrid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
                <i class="fas fa-box-open" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-secondary); font-size: 1.5rem; margin-bottom: 0.5rem;">No Products Available</h3>
                <p style="color: var(--text-muted);">Try adjusting your filters or search terms</p>
            </div>
        `;
        document.getElementById('loadMoreBtn').style.display = 'none';
        return;
    }

    // Render
    productsGrid.innerHTML = productsToShow.map((product, index) => `
        <div class="product-card" data-aos="zoom-in-up" data-aos-delay="${index % 12 * 50}" data-product-id="${product.id}" style="cursor:pointer">
            <div class="product-image-container">
                <img src="${product.image}" alt="${product.name}" class="product-image">
                ${product.onSale ? `<div class="sale-badge">${product.salePercent}% OFF</div>` : ''}
                ${!product.inStock ? '<div class="stock-badge out-of-stock">Out of Stock</div>' : ''}
                <button class="product-favorite ${favorites.includes(product.id) ? 'active' : ''}" onclick="toggleFavorite(${product.id})">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            <div class="product-info">
                <h3 class="product-title">${product.name}</h3>
                <p class="product-creator">by ${product.creator}</p>
                <div class="product-footer">
                    <div class="product-price">
                        <span class="price-label">Current Price</span>
                        <div class="price-value">
                            <i class="fas fa-tag"></i>
                            <span>${product.price.toLocaleString()} BDT</span>
                        </div>
                    </div>
                    <button class="btn-add-cart" onclick="addToCart(${product.id})">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Add to Cart</span>
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    // Show/Hide Load More button
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (displayedProducts >= filteredProducts.length) {
        loadMoreBtn.style.display = 'none';
    } else {
        loadMoreBtn.style.display = 'block';
    }
    
    // Refresh AOS animations for dynamically loaded products
    if (typeof AOS !== 'undefined') {
        AOS.refresh();
    }
    
    // Add event delegation for product cards
    productsGrid.querySelectorAll('.product-card').forEach(card => {
        card.removeEventListener('click', handleProductCardClick);
        card.addEventListener('click', handleProductCardClick.bind(card));
    });

    fixImageSources(productsGrid);
}

function handleProductCardClick(e) {
    // Don't trigger if clicking on buttons
    if (e.target.closest('.btn-add-cart') || e.target.closest('.product-favorite')) {
        return;
    }
    const productId = parseInt(this.getAttribute('data-product-id'));
    if (productId) {
        showProductDetails(productId);
    }
}

// ===== Sort Products =====
function sortProducts(products) {
    switch(currentSort) {
        case 'newest':
            return [...products].reverse();
        case 'oldest':
            return [...products];
        case 'price-low':
            return [...products].sort((a, b) => a.price - b.price);
        case 'price-high':
            return [...products].sort((a, b) => b.price - a.price);
        case 'popular':
            return [...products].sort(() => Math.random() - 0.5);
        default:
            return products;
    }
}

// ===== Cart Functions =====
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ ...product, quantity: 1 });
    }

    saveCart();
    updateCartCount();
    renderCartItems();
    playSound('add');
    showNotification('Added to cart!', 'success');
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    updateCartCount();
    renderCartItems();
}

function updateQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();
            renderCartItems();
        }
    }
}

function updateCartCount() {
    if (isBuyerLoggedIn()) {
        refreshCartCountFromServer();
        return;
    }
    const count = cart.reduce((total, item) => total + item.quantity, 0);
    setCartBadges(count);
}

function saveCart() {
    localStorage.setItem('quickmart_cart', JSON.stringify(cart));
}

function renderCartItems() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');

    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
        cartTotal.textContent = '0 BDT';
        return;
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
            <div class="cart-item-info">
                <h4 class="cart-item-title">${item.name}</h4>
                <p class="cart-item-price">${item.price.toLocaleString()} BDT</p>
                <div class="cart-item-actions">
                    <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span class="cart-item-qty">${item.quantity}</span>
                    <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="btn-remove" onclick="removeFromCart(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    cartTotal.textContent = `${total.toLocaleString()} BDT`;
}

// ===== Product Details Modal =====
function showProductDetails(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    const modal = document.getElementById('productDetailsModal');
    const modalBody = document.getElementById('productDetailsBody');
    
    // Calculate discount price
    const discountedPrice = product.onSale ? product.price * (1 - product.salePercent / 100) : product.price;
    const savings = product.price - discountedPrice;
    
    // Sample reviews data
    const reviews = [
        { name: "আহমেদ খান", rating: 5, comment: "Excellent product! Worth the price.", date: "Nov 28, 2025", sellerReply: "Thank you for your feedback!" },
        { name: "Fatima Rahman", rating: 4, comment: "Good quality but delivery was a bit slow.", date: "Nov 25, 2025", sellerReply: "We apologize for the delay. We're working on faster delivery." },
        { name: "Karim Ali", rating: 5, comment: "খুব ভালো পণ্য। সবাইকে রিকমেন্ড করছি।", date: "Nov 20, 2025", sellerReply: "" }
    ];
    
    // Sample coupons
    const coupons = [
        { code: "SAVE10", discount: 10, minPurchase: 500 },
        { code: "FIRST20", discount: 20, minPurchase: 1000 },
        { code: "WINTER15", discount: 15, minPurchase: 800 }
    ];
    
    // Get similar products (same category)
    const similarProducts = products.filter(p => p.category === product.category && p.id !== product.id).slice(0, 3);
    
    modalBody.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem">
            <div>
                <img src="${product.image}" alt="${product.name}" style="width:100%;border-radius:1rem;margin-bottom:1rem">
                ${product.onSale ? `<div style="background:var(--danger-color);color:white;padding:0.5rem;text-align:center;border-radius:0.5rem;font-weight:600">${product.salePercent}% OFF - Limited Time!</div>` : ''}
            </div>
            <div>
                <h2 style="font-size:1.75rem;margin-bottom:0.5rem">${product.name}</h2>
                <p style="color:var(--text-muted);margin-bottom:1rem">by ${product.creator}</p>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem">
                    <div style="color:var(--warning-color)">
                        ${'<i class="fas fa-star"></i>'.repeat(5)}
                    </div>
                    <span>(${reviews.length} reviews)</span>
                </div>
                <div style="margin-bottom:1.5rem">
                    ${product.onSale ? `<div style="font-size:1.25rem;text-decoration:line-through;color:var(--text-muted)">${product.price.toLocaleString()} BDT</div>` : ''}
                    <div style="font-size:2rem;font-weight:700;color:var(--primary-color)">${Math.round(discountedPrice).toLocaleString()} BDT</div>
                    ${product.onSale ? `<div style="color:var(--success-color);font-weight:600;margin-top:0.5rem">You save: ${Math.round(savings).toLocaleString()} BDT</div>` : ''}
                </div>
                <div style="margin-bottom:1.5rem">
                    <span style="padding:0.5rem 1rem;background:${product.inStock ? 'var(--success-color)' : 'var(--danger-color)'};color:white;border-radius:0.5rem;font-size:0.875rem">
                        <i class="fas fa-${product.inStock ? 'check-circle' : 'times-circle'}"></i> ${product.inStock ? 'In Stock' : 'Out of Stock'}
                    </span>
                </div>
                <button class="btn-add-cart" onclick="event.stopPropagation();addToCart(${product.id});closeModal(document.getElementById('productDetailsModal'))" style="width:100%;padding:1rem;font-size:1.1rem;margin-bottom:1rem">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Add to Cart</span>
                </button>
                <button class="btn-add-cart" onclick="event.stopPropagation();toggleFavorite(${product.id})" style="width:100%;background:var(--warning-color)">
                    <i class="fas fa-heart"></i>
                    <span>Add to Favorites</span>
                </button>
            </div>
        </div>
        
        <!-- Coupons Section -->
        <div style="background:var(--bg-secondary);padding:1.5rem;border-radius:1rem;margin-bottom:2rem">
            <h3 style="font-size:1.25rem;margin-bottom:1rem"><i class="fas fa-ticket-alt" style="color:var(--warning-color)"></i> Available Coupons</h3>
            <div style="display:grid;gap:1rem">
                ${coupons.map(coupon => `
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem;background:var(--bg-primary);border-radius:0.5rem;border:2px dashed var(--primary-color)">
                        <div>
                            <div style="font-weight:700;font-size:1.1rem;color:var(--primary-color)">${coupon.code}</div>
                            <div style="font-size:0.875rem;color:var(--text-muted)">Get ${coupon.discount}% off on orders above ${coupon.minPurchase} BDT</div>
                        </div>
                        <button onclick="event.stopPropagation();applyCoupon('${coupon.code}')" style="padding:0.5rem 1rem;background:var(--primary-color);color:white;border:none;border-radius:0.5rem;cursor:pointer;font-weight:600">
                            Apply
                        </button>
                    </div>
                `).join('')}
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div style="margin-bottom:2rem">
            <h3 style="font-size:1.25rem;margin-bottom:1rem"><i class="fas fa-comments" style="color:var(--primary-color)"></i> Customer Reviews</h3>
            ${reviews.map(review => `
                <div style="padding:1.5rem;background:var(--bg-secondary);border-radius:1rem;margin-bottom:1rem">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:1rem">
                        <div>
                            <div style="font-weight:600;margin-bottom:0.25rem">${review.name}</div>
                            <div style="color:var(--warning-color);margin-bottom:0.5rem">
                                ${'<i class="fas fa-star"></i>'.repeat(review.rating)}${'<i class="far fa-star"></i>'.repeat(5 - review.rating)}
                            </div>
                        </div>
                        <span style="color:var(--text-muted);font-size:0.875rem">${review.date}</span>
                    </div>
                    <p style="margin-bottom:1rem">${review.comment}</p>
                    ${review.sellerReply ? `
                        <div style="margin-left:2rem;padding:1rem;background:var(--bg-primary);border-left:3px solid var(--primary-color);border-radius:0.5rem">
                            <div style="font-weight:600;margin-bottom:0.5rem;color:var(--primary-color)"><i class="fas fa-store"></i> Seller's Reply:</div>
                            <p>${review.sellerReply}</p>
                        </div>
                    ` : ''}
                </div>
            `).join('')}
        </div>
        
        <!-- Similar Products -->
        <div>
            <h3 style="font-size:1.25rem;margin-bottom:1rem"><i class="fas fa-th-large" style="color:var(--success-color)"></i> Similar Products</h3>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
                ${similarProducts.map(p => `
                    <div onclick="showProductDetails(${p.id})" style="cursor:pointer;padding:1rem;background:var(--bg-secondary);border-radius:1rem;transition:transform 0.2s" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="${p.image}" alt="${p.name}" style="width:100%;height:150px;object-fit:cover;border-radius:0.5rem;margin-bottom:0.5rem">
                        <div style="font-weight:600;font-size:0.9rem;margin-bottom:0.25rem">${p.name}</div>
                        <div style="color:var(--primary-color);font-weight:700">${p.price.toLocaleString()} BDT</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    openModal(modal);
}

function applyCoupon(code) {
    playSound('coupon');
    showNotification(`Coupon ${code} applied successfully!`, 'success');
}

// ===== Favorites =====
function toggleFavorite(productId) {
    const favorites = JSON.parse(localStorage.getItem('quickmart_favorites') || '[]');
    const index = favorites.indexOf(productId);
    if (index > -1) {
        favorites.splice(index, 1);
        showNotification('Removed from favorites', 'info');
    } else {
        favorites.push(productId);
        showNotification('Added to favorites!', 'success');
    }
    localStorage.setItem('quickmart_favorites', JSON.stringify(favorites));
    renderProducts();
}

// ===== Notification System =====
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

// Add notification animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===== Modal Closing (Overlay and Close Buttons) =====
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('modal-close')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
    }
});

// Global ESC key to close any open modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
    }
});

// ===== Top Toast Notification =====
const topToast = document.getElementById('topToast');
const closeToast = document.getElementById('closeToast');

function setToastOffset() {
    if (!topToast) return;
    const height = topToast.offsetHeight || 0;
    document.body.style.setProperty('--toast-height', `${height}px`);
}

function showToastBar() {
    if (!topToast) return;
    topToast.style.removeProperty('animation');
    topToast.style.display = 'block';
    // Next frame to ensure layout is ready
    requestAnimationFrame(() => {
        setToastOffset();
        document.body.classList.add('toast-visible');
    });
}

function hideToastBar(withAnimation = true) {
    if (!topToast) return;
    if (withAnimation) {
        topToast.style.animation = 'slideUp 0.5s ease-out forwards';
        setTimeout(() => {
            topToast.style.display = 'none';
            document.body.classList.remove('toast-visible');
        }, 500);
    } else {
        topToast.style.display = 'none';
        document.body.classList.remove('toast-visible');
    }
}

if (closeToast) {
    closeToast.addEventListener('click', () => {
        hideToastBar(true);
        try { sessionStorage.setItem('toastClosed', 'true'); } catch {}
    });
}

// Check if toast was previously closed
// Show toast by default on refresh (unless explicitly closed this session)
try {
    if (sessionStorage.getItem('toastClosed') === 'true') {
        hideToastBar(false);
    } else {
        showToastBar();
    }
} catch {}

// Keep offset in sync on resize while visible
window.addEventListener('resize', () => {
    if (document.body.classList.contains('toast-visible')) {
        setToastOffset();
    }
});

// ===== Banner Carousel =====
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const dotsContainer = document.getElementById('carouselDots');
const prevBtn = document.getElementById('carouselPrev');
const nextBtn = document.getElementById('carouselNext');

if (slides.length && dotsContainer) {
    // Create dots
    slides.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.className = `carousel-dot ${index === 0 ? 'active' : ''}`;
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });
}

const dots = document.querySelectorAll('.carousel-dot');

function goToSlide(n) {
    if (!slides.length || !dots.length) return;
    const prevSlideIndex = currentSlide;
    slides[prevSlideIndex].classList.add('exiting');
    slides[prevSlideIndex].classList.remove('active');
    dots[prevSlideIndex].classList.remove('active');
    
    currentSlide = (n + slides.length) % slides.length;
    
    // Reset all slides positioning
    slides.forEach((slide, index) => {
        if (index !== currentSlide) {
            slide.classList.remove('active', 'exiting');
        }
    });
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
    
    // Clean up exiting class after animation
    setTimeout(() => {
        slides[prevSlideIndex].classList.remove('exiting');
    }, 600);
}

function nextSlide() {
    if (!slides.length) return;
    goToSlide(currentSlide + 1);
}

function prevSlide() {
    if (!slides.length) return;
    goToSlide(currentSlide - 1);
}

if (nextBtn && slides.length) nextBtn.addEventListener('click', nextSlide);
if (prevBtn && slides.length) prevBtn.addEventListener('click', prevSlide);

if (slides.length) {
    // Auto-advance carousel
    setInterval(nextSlide, 5000);
}

// ===== Dashboard (redirect to role pages) =====
const dashboardBtn = document.getElementById('dashboardBtn');
const dashboardPanel = document.getElementById('dashboardPanel'); // kept for backward compatibility

if (dashboardBtn) {
    dashboardBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const loggedIn = localStorage.getItem('isLoggedIn') === 'true';
        if (!loggedIn) {
            showGuestModal();
            return;
        }
        const role = localStorage.getItem('userRole') || 'buyer';
        // Route to role-based dashboard instead of profile
        const dest = role === 'seller' 
            ? '../seller_dashboard/seller_dashboard.php' 
            : '../buyer_dashboard/buyer_dashboard.php';
        window.location.href = dest;
    });
}

// ===== Live Chat =====
const chatIcon = document.getElementById('chatIcon');
const chatModal = document.getElementById('chatModal');
const chatInput = document.getElementById('chatInput');
const sendChatBtn = document.getElementById('sendChatBtn');
const chatBody = document.getElementById('chatBody');
let chatStep = 0;
const supportOptions = [
    [
        'Order issue',
        'Payment or refund',
        'Product question'
    ],
    [
        'Delivery delay',
        'Wrong / damaged item',
        'Need invoice/receipt'
    ],
    [
        'Need live agent',
        'Escalate to support',
        'Update my request'
    ]
];

// Set chat avatar from stored user image
const chatAvatar = document.querySelector('#chatModal .chat-avatar');
try {
    const storedUserImage = localStorage.getItem('userImage');
    if (chatAvatar && storedUserImage) {
        chatAvatar.src = storedUserImage;
    }
} catch {}

function playChatSound(kind = 'receive') {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        const now = ctx.currentTime;
        if (kind === 'send') {
            osc.frequency.setValueAtTime(750, now);
            gain.gain.setValueAtTime(0.1, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
            osc.start(now);
            osc.stop(now + 0.1);
        } else {
            osc.frequency.setValueAtTime(520, now);
            osc.frequency.setValueAtTime(680, now + 0.06);
            gain.gain.setValueAtTime(0.12, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
            osc.start(now);
            osc.stop(now + 0.22);
        }
    } catch (e) {
        // fail silently if audio blocked
    }
}

function showTypingIndicator() {
    if (!chatBody) return null;
    const div = document.createElement('div');
    div.className = 'chat-message received typing';
    div.innerHTML = `
        <div class="message-content">
            <div class="typing-dots"><span></span><span></span><span></span></div>
            <span class="message-time">Typing...</span>
        </div>
    `;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
    return div;
}

if (chatIcon) {
    chatIcon.addEventListener('click', () => {
        chatModal.style.display = 'flex';
    });
}

if (sendChatBtn) {
    sendChatBtn.addEventListener('click', sendMessage);
}

if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

function sendMessage() {
    const message = chatInput.value.trim();
    if (message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message sent';
        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${message}</p>
                <span class="message-time">Just now</span>
            </div>
        `;
        chatBody.appendChild(messageDiv);
        chatInput.value = '';
        chatBody.scrollTop = chatBody.scrollHeight;
        playChatSound('send');
        handleAutoSupportFlow(message);
    }
}

function appendSupportMessage(html) {
    const replyDiv = document.createElement('div');
    replyDiv.className = 'chat-message received';
    replyDiv.innerHTML = `
        <div class="message-content">
            ${html}
            <span class="message-time">Just now</span>
        </div>
    `;
    chatBody.appendChild(replyDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
    playChatSound('receive');
}

function renderOptions(stepIndex) {
    const opts = supportOptions[stepIndex] || [];
    if (!opts.length) return '';
    return `
        <div style="margin-top:0.5rem; display:flex; flex-wrap:wrap; gap:0.5rem;">
            ${opts.map(opt => `<button class="quick-reply" data-reply="${opt}" style="padding:0.5rem 0.75rem; border-radius:14px; border:1px solid var(--border-color, #e5e7eb); background:var(--bg-secondary, #111827); color:var(--text-primary, #e5e7eb); cursor:pointer; font-weight:600;">${opt}</button>`).join('')}
        </div>
    `;
}

function handleAutoSupportFlow(userText) {
    // Three guided steps, then handoff
    if (chatStep < 3) {
        const promptText = chatStep === 0
            ? 'Got it! Which area best matches your issue?'
            : chatStep === 1
                ? 'Thanks. Can you specify the issue type?'
                : 'Almost done. What do you want next?';
        const indicator = showTypingIndicator();
        setTimeout(() => {
            if (indicator) indicator.remove();
            appendSupportMessage(`
                <p>${promptText}</p>
                ${renderOptions(chatStep)}
            `);
        }, 500);
        chatStep += 1;
    } else {
        const indicator = showTypingIndicator();
        setTimeout(() => {
            if (indicator) indicator.remove();
            appendSupportMessage('<p>We have collected your details and sent them to our customer support team. A person will chat with you in a few minutes.</p>');
        }, 500);
        chatStep = 0; // reset flow for next time
    }
}

// Quick reply delegation
if (chatBody) {
    chatBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.quick-reply');
        if (!btn) return;
        const value = btn.dataset.reply;
        chatInput.value = value;
        sendMessage();
    });
}

// Typing dots styles
const typingStyle = document.createElement('style');
typingStyle.textContent = `
    .typing-dots {
        display: inline-flex;
        gap: 4px;
        align-items: center;
        margin-bottom: 4px;
    }
    .typing-dots span {
        width: 7px;
        height: 7px;
        background: var(--text-muted, #cbd5e1);
        border-radius: 50%;
        display: inline-block;
        animation: typing-bounce 1s infinite ease-in-out;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.15s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes typing-bounce {
        0%, 80%, 100% { transform: translateY(0); opacity: 0.6; }
        40% { transform: translateY(-4px); opacity: 1; }
    }
`;
document.head.appendChild(typingStyle);

// ===== Guest User Modal =====
const guestModal = document.getElementById('guestModal');
const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';

function showGuestModal() {
    if (!isLoggedIn) {
        guestModal.style.display = 'flex';
    }
}

// Add guest check to sidebar buttons, notification, and cart
document.addEventListener('click', (e) => {
    if (!isLoggedIn) {
        const target = e.target.closest('#notificationBtn, #cartBtn, .nav-item');
        if (target && !target.closest('.modal')) {
            e.preventDefault();
            e.stopPropagation();
            showGuestModal();
        }
    }
});

// Add slideUp animation
const slideUpStyle = document.createElement('style');
slideUpStyle.textContent = `
    @keyframes slideUp {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(slideUpStyle);
