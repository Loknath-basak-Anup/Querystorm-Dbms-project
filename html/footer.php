<!-- Animated Glowing Footer -->
<footer class="footer-wrapper" style="width: 100%; position: relative; left: 0; right: 0;">
    <!-- CSS Styles -->
    <style>
        :root {
            --f-bg-dark: #0f172a;
            --f-bg-darker: #020617;
            --f-primary: #6366f1; /* Indigo */
            --f-secondary: #ec4899; /* Pink */
            --f-text-main: #f8fafc;
            --f-text-muted: #94a3b8;
            --f-border: rgba(255, 255, 255, 0.1);
            --f-glow: 0 0 20px rgba(99, 102, 241, 0.5);
        }

        .footer-wrapper {
            position: relative;
            background-color: var(--f-bg-darker);
            color: var(--f-text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            border-top: 1px solid var(--f-border);
            box-sizing: border-box;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Sidebar collapsed state */
        body:has(.sidebar.collapsed) .footer-wrapper {
            margin-left: 0;
            width: calc(100% - 80px);
        }

        /* No sidebar - full width */
        body:not(:has(.sidebar)) .footer-wrapper {
            margin-left: 0;
            width: 100%;
        }

        /* Mobile - full width */
        @media (max-width: 768px) {
            .footer-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* Animated Background Blobs */
        .footer-bg-glow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: floatBlob 10s infinite alternate;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: var(--f-primary);
        }

        .blob-2 {
            bottom: -10%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: var(--f-secondary);
            animation-delay: -5s;
        }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(50px, 50px) scale(1.1); }
        }

        /* Glass Container */
        .footer-container {
            position: relative;
            z-index: 1;
            background: rgba(15, 23, 42, 0.6); /* Semi-transparent dark */
            backdrop-filter: blur(10px);
            padding: 4rem 2rem 1rem;
        }

        /* Animated Top Line */
        .footer-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--f-primary), 
                var(--f-secondary), 
                var(--f-primary), 
                transparent
            );
            background-size: 200% 100%;
            animation: gradientMove 3s linear infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        /* Layout */
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.8s ease;
        }

        .footer-section.animate-in {
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo & Branding */
        .footer-logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to right, #fff, var(--f-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .footer-desc {
            color: var(--f-text-muted);
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        /* Headings */
        .footer-heading {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #fff;
            position: relative;
            display: inline-block;
        }

        .footer-heading::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 40px;
            height: 3px;
            background: var(--f-primary);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .footer-section:hover .footer-heading::after {
            width: 100%;
            background: linear-gradient(90deg, var(--f-primary), var(--f-secondary));
        }

        /* Links List */
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            text-decoration: none;
            color: var(--f-text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            position: relative;
            width: fit-content;
        }

        .footer-links a i {
            font-size: 0.8rem;
            transition: 0.3s;
            color: var(--f-primary);
        }

        .footer-links a:hover {
            color: #fff;
            text-shadow: 0 0 10px var(--f-primary);
            transform: translateX(8px);
        }

        .footer-links a:hover i {
            color: var(--f-secondary);
        }

        /* Contact Info */
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: var(--f-text-muted);
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .contact-item i {
            color: var(--f-secondary);
            margin-top: 4px;
        }

        /* Glowing Button */
        .footer-team-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 24px;
            background: transparent;
            color: white;
            border: 1px solid var(--f-primary);
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.1);
            margin-top: 1rem;
        }

        .footer-team-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .footer-team-btn:hover {
            background: var(--f-primary);
            box-shadow: 0 0 20px var(--f-primary);
            border-color: var(--f-primary);
            transform: translateY(-3px);
        }

        .footer-team-btn:hover::before {
            left: 100%;
        }

        /* Social Icons */
        .social-container {
            display: flex;
            gap: 1rem;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 1px solid transparent;
        }

        .social-btn:hover {
            background: #fff;
            transform: translateY(-5px) scale(1.1);
        }

        .social-btn:hover i {
            background: -webkit-linear-gradient(45deg, var(--f-primary), var(--f-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Bottom Bar */
        .footer-bottom {
            border-top: 1px solid var(--f-border);
            padding-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .copyright {
            font-size: 0.9rem;
            color: var(--f-text-muted);
        }

        .copyright strong {
            color: var(--f-primary);
        }

        .bottom-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .bottom-links a {
            font-size: 0.85rem;
            color: var(--f-text-muted);
            text-decoration: none;
            transition: 0.3s;
        }

        .bottom-links a:hover {
            color: var(--f-secondary);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            .social-container {
                justify-content: flex-start;
            }
        }
    </style>

    <!-- Animated Background -->
    <div class="footer-bg-glow">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <!-- Animated Line -->
    <div class="footer-line"></div>

    <div class="footer-container">
        <div class="footer-content">
            
            <!-- Brand Section -->
            <div class="footer-section">
                <div class="footer-logo">
                    <i class="fas fa-bolt" style="color: var(--f-secondary);"></i> QuickMart
                </div>
                <p class="footer-desc">
                    Experience the future of shopping. Premium products, lightning-fast delivery, and a marketplace built for you.
                </p>
                <div class="social-container">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h4 class="footer-heading">Discover</h4>
                <ul class="footer-links">
                    <li><a href="../html/products_page.php"><i class="fas fa-chevron-right"></i> Shop Now</a></li>
                    <li><a href="../buyer_dashboard/cart.php"><i class="fas fa-chevron-right"></i> My Cart</a></li>
                    <li><a href="../buyer_dashboard/profile.php"><i class="fas fa-chevron-right"></i> User Profile</a></li>
                    <li><a href="../buyer_dashboard/wishlist.php"><i class="fas fa-chevron-right"></i> Wishlist</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="footer-section">
                <h4 class="footer-heading">Help Desk</h4>
                <ul class="footer-links">
                    <li><a href="../buyer_dashboard/helpCenter.php"><i class="fa-solid fa-circle-question"></i> Help Center</a></li>
                    <li><a href="#"><i class="fas fa-shield-alt"></i> Trust & Safety</a></li>
                    <li><a href="./shipping.php"><i class="fas fa-truck"></i> Shipping Info</a></li>
                    <li><a href="./returns.php"><i class="fas fa-undo"></i> Returns</a></li>
                </ul>
            </div>

            <!-- Contact / Developer -->
            <div class="footer-section">
                <h4 class="footer-heading">Contact Us</h4>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Dhaka,<br>Bangladesh</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>support@quickmart.com</span>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <p style="font-size: 0.85rem; color: var(--f-text-muted);">Built with ❤️ by</p>
                    <a href="../html/developer.php" class="footer-team-btn">
                        <i class="fas fa-code"></i> QueryStrom Team
                    </a>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            <div class="copyright">
                &copy; 2025 QuickMart. Designed by <strong>QueryStrom Team</strong>.
            </div>
            <div class="bottom-links">
                <a href="../html/products_page.php">Marketplace</a>
                <a href="/html/shipping.php">Shipping Info</a>
                <a href="/html/returns.php">Returns & Refunds</a>
                <a href="/html/privacy.php">Privacy Policy</a>
                <a href="/html/terms.php">Terms of Service</a>
                <a href="/html/cookies.php">Cookie Settings</a>
            </div>
        </div>
    </div>
</footer>

<script>
    (function() {
        const currentPath = window.location.pathname;
        const isInHtmlFolder = currentPath.includes('/html/');
        const footerLinks = document.querySelectorAll('.footer-wrapper a[href^="../"]');
        footerLinks.forEach(link => {
            let href = link.getAttribute('href');
            if (isInHtmlFolder) {
                href = href.replace('../html/', './');
            }
            link.setAttribute('href', href);
        });
    })();

    document.addEventListener('DOMContentLoaded', () => {
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        const sections = document.querySelectorAll('.footer-section');
        sections.forEach((section, index) => {
            section.style.animationDelay = `${index * 100}ms`;
            observer.observe(section);
        });
    });
</script>