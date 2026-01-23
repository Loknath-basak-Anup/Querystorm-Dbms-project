// Profile Page JavaScript

// Load navbar
async function loadNavbar() {
    try {
        const response = await fetch('../html/navbar.php');
        const html = await response.text();
        const navContainer = document.getElementById('navbarContainer');
        if (navContainer) {
            navContainer.innerHTML = html;
            
            // Set page title based on role
            const pageTitle = document.querySelector('.page-title-navbar');
            const userRole = localStorage.getItem('userRole') || 'buyer';
            if (pageTitle) {
                pageTitle.innerHTML = userRole === 'seller' 
                    ? '<i class="fas fa-store"></i> Seller Profile' 
                    : '<i class="fas fa-user-circle"></i> My Profile';
            }
            
            // Initialize navbar handlers
            initializeNavbarHandlers();
        }
    } catch (error) {
        console.error('Error loading navbar:', error);
    }
}

// Initialize navbar handlers
function initializeNavbarHandlers() {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const icon = themeToggle.querySelector('i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
    }
}

// Load user data
function loadUserData() {
    const userEmail = localStorage.getItem('userEmail') || 'user@quickmart.com';
    const userEmailEl = document.getElementById('userEmail');
    if (userEmailEl) {
        userEmailEl.textContent = userEmail;
    }
}

// Switch tabs
function switchTab(tabName) {
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    contents.forEach(content => content.classList.remove('active'));
    
    event.target.closest('.tab').classList.add('active');
    const targetContent = document.getElementById(tabName);
    if (targetContent) {
        targetContent.classList.add('active');
    }
}

// Logout function
function logout() {
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userRole');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('userName');
    localStorage.removeItem('userImage');
    const parts = window.location.pathname.split('/').filter(Boolean);
    const basePath = parts.length > 0 ? '/' + parts[0] : '';
    window.location.href = basePath + '/actions/logout.php';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadNavbar();
    loadUserData();
});
