<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Our Team | QuickMart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com" defer></script>
    <style>
        @keyframes blob {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        .background-blobs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.3;
            animation: blob 7s infinite;
        }

        .blob-1 {
            width: 300px;
            height: 300px;
            background: #6366f1;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .blob-2 {
            width: 250px;
            height: 250px;
            background: #ec4899;
            top: 50%;
            right: 10%;
            animation-delay: 2s;
        }

        .blob-3 {
            width: 280px;
            height: 280px;
            background: #3b82f6;
            bottom: 10%;
            left: 40%;
            animation-delay: 4s;
        }

        .blob-4 {
            width: 220px;
            height: 220px;
            background: #f59e0b;
            top: 30%;
            right: 20%;
            animation-delay: 1s;
        }

        body.dark-mode {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
            margin: 0;
        }
        main.main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            transition: margin-left 0.3s ease, width 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        body:has(.sidebar.collapsed) main.main-content {
            margin-left: 80px;
            width: calc(100% - 80px);
        }
        .page-content {
            flex: 1;
        }
        .team-member-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-width: 3px !important;
        }
        .team-member-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .team-member-card:hover::before {
            left: 100%;
        }
        .team-member-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
        }
        .member-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-color), rgba(99, 102, 241, 0.5));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            border: 4px solid #6366f1;
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.6);
            position: relative;
            overflow: visible;
            animation: colorful-glow 3s ease-in-out infinite;
        }

        @keyframes colorful-glow {
            0% {
                border-color: #6366f1;
                box-shadow: 0 0 25px rgba(99, 102, 241, 0.6), 0 0 40px rgba(99, 102, 241, 0.3);
            }
            25% {
                border-color: #ec4899;
                box-shadow: 0 0 25px rgba(236, 72, 153, 0.6), 0 0 40px rgba(236, 72, 153, 0.3);
            }
            50% {
                border-color: #3b82f6;
                box-shadow: 0 0 25px rgba(59, 130, 246, 0.6), 0 0 40px rgba(59, 130, 246, 0.3);
            }
            75% {
                border-color: #f59e0b;
                box-shadow: 0 0 25px rgba(245, 158, 11, 0.6), 0 0 40px rgba(245, 158, 11, 0.3);
            }
            100% {
                border-color: #6366f1;
                box-shadow: 0 0 25px rgba(99, 102, 241, 0.6), 0 0 40px rgba(99, 102, 241, 0.3);
            }
        }
        .social-link {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .social-link:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        .glow-text {
            text-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        }
    </style>
</head>
<body class="dark-mode">
    <!-- Animated Background Blobs -->
    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>
    </div>

    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar() {
                const response = await fetch('../html/navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                
                const scripts = document.getElementById('navbarContainer').querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    newScript.innerHTML = script.innerHTML;
                    document.body.appendChild(newScript);
                });
                
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-users"></i> Our Team';
                
                setTimeout(() => {
                    if (typeof window.initializeUserMenuGlobal === 'function') {
                        window.initializeUserMenuGlobal();
                    }
                    
                    const userMenu = document.getElementById('userMenu');
                    const userDropdown = document.getElementById('userDropdown');
                    let userMenuTimeout;
                    
                    if (userMenu && userDropdown) {
                        userMenu.onmouseenter = function(e) {
                            clearTimeout(userMenuTimeout);
                            userDropdown.style.display = 'block';
                            userDropdown.style.opacity = '1';
                            userDropdown.style.visibility = 'visible';
                        };
                        
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
                        
                        userMenu.onclick = function(e) {
                            e.stopPropagation();
                            const isVisible = userDropdown.style.display === 'block';
                            userDropdown.style.display = isVisible ? 'none' : 'block';
                            userDropdown.style.opacity = isVisible ? '0' : '1';
                            userDropdown.style.visibility = isVisible ? 'hidden' : 'visible';
                        };
                    }
                    
                    document.onclick = function(e) {
                        const userMenu = document.getElementById('userMenu');
                        const userDropdown = document.getElementById('userDropdown');
                        if (userDropdown && userMenu && !userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                            userDropdown.style.display = 'none';
                            userDropdown.style.opacity = '0';
                            userDropdown.style.visibility = 'hidden';
                        }
                    };
                    
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
                    
                    const savedTheme = localStorage.getItem('quickmart_theme');
                    if (savedTheme === 'light') {
                        document.body.classList.remove('dark-mode');
                        const icon = themeToggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-moon');
                            icon.classList.add('fa-sun');
                        }
                    }
                    
                    if (typeof window.setupNotificationModal === 'function') {
                        window.setupNotificationModal();
                    }
                }, 50);
            }
            loadNavbar();
        </script>
        
        <div class="page-content">
            <!-- Header Section -->
            <div class="text-center mb-12" data-aos="fade-down">
                <h1 class="text-4xl font-bold mb-4 glow-text">Meet the QueryStrom Team</h1>
                <p class="text-xl" style="color:var(--text-secondary)">Passionate developers building the future of e-commerce</p>
            </div>

            <!-- Team Members -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                <!-- Team Member 1 -->
                <div class="team-member-card bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] text-center" data-aos="zoom-in">
                    <div class="member-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary-color);">
                        <img src="https://i.postimg.cc/WbSb1HtW/IMG_0993(1).jpg" alt="Shahriar Ahmed Riaz" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" decoding="async">
                    </div>
                    <h3 class="text-xl font-bold mb-1">Shahriar Ahmed Riaz</h3>
                    <p class="text-sm mb-2" style="color:var(--primary-color); font-weight: 600;">Full Stack Developer</p>
                    <p class="text-sm mb-4" style="color:var(--text-secondary)">Building robust and scalable backend systems</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://github.com/ahmed-shahriar04" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://www.linkedin.com/in/shahriarahmedriaz/" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="mailto:shahriarahmedriaz04@gmail.com" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="https://learnix-web.netlify.app/portfolio/riaz/riaz" class="social-link">
                            <i class="fa-solid fa-user-tie"></i>
                        </a>
                    </div>
                </div>

                <!-- Team Member 2 -->
                <!-- <div class="team-member-card bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] text-center" data-aos="zoom-in" data-aos-delay="100">
                    <div class="member-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary-color);">
                        <img src="https://ui-avatars.com/api/?name=Sarah+Johnson&size=200&background=ec4899&color=fff&bold=true" alt="Sarah Johnson" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 class="text-xl font-bold mb-1">Sarah Johnson</h3>
                    <p class="text-sm mb-2" style="color:var(--primary-color); font-weight: 600;">UI/UX Designer</p>
                    <p class="text-sm mb-4" style="color:var(--text-secondary)">Creating beautiful and intuitive user experiences</p>
                    <div class="flex justify-center gap-3">
                        <a href="#" class="social-link">
                            <i class="fab fa-dribbble"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div> -->

                <!-- Team Member 3 -->
                <div class="team-member-card bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] text-center" data-aos="zoom-in" data-aos-delay="200">
                    <div class="member-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary-color);">
                        <img src="https://i.postimg.cc/C1LhVW3H/anup.jpg" alt="Loknath Basak Anup" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" decoding="async">
                    </div>
                    <h3 class="text-xl font-bold mb-1">Loknath Basak Anup</h3>
                    <p class="text-sm mb-2" style="color:var(--primary-color); font-weight: 600;">Database Administrator & Backend Developer</p>
                    <p class="text-sm mb-4" style="color:var(--text-secondary)">Managing and optimizing data systems, backend development</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://github.com/Loknath-basak-Anup" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://www.linkedin.com/in/loknath-basak-anup-971698344?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="mailto:loknathbasakanup7@gmail.com" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="/html/portfolio/anup.php" class="social-link">
                            <i class="fa-solid fa-user-tie"></i>
                        </a>
                    </div>
                </div>

                <!-- Team Member 4 -->
                <div class="team-member-card bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] text-center" data-aos="zoom-in" data-aos-delay="300">
                    <div class="member-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary-color);">
                        <img src="https://lamiya0647.netlify.app/pic.jpeg" alt="Emily Davis" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" decoding="async">
                    </div>
                    <h3 class="text-xl font-bold mb-1">Lamiya Zahan Mim</h3>
                    <p class="text-sm mb-2" style="color:var(--primary-color); font-weight: 600;">DATABASE ADMINISTRATOR</p>
                    <p class="text-sm mb-4" style="color:var(--text-secondary)">Ensuring quality,  reliability and Database Administrator</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://github.com/lzmim" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="mailto:lamiyazahanmim07@gmail.com" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="https://lamiya0647.netlify.app/" class="social-link">
                            <i class="fa-solid fa-user-tie"></i>
                        </a>
                    </div>
                </div>

                <!-- Team Member 5 -->
                <div class="team-member-card bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] text-center" data-aos="zoom-in" data-aos-delay="400">
                    <div class="member-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary-color);">
                        <img src="https://i.postimg.cc/jjn4nCJ1/WhatsApp_Image_2024-10-23_at_21.32.07_9fce452a.jpg" alt="Tameem Mehedi Prince" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" decoding="async">
                    </div>
                    <h3 class="text-xl font-bold mb-1">Tameem Mehedi Prince</h3>
                    <p class="text-sm mb-2" style="color:var(--primary-color); font-weight: 600;">BACK-END DEVELOPER</p>
                    <p class="text-sm mb-4" style="color:var(--text-secondary)">Backend development and server management</p>
                    <div class="flex justify-center gap-3">
                        <a href="https://github.com/tmprince0" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://www.linkedin.com/in/md-tameem-mehedi-8b1b70299/?originalSubdomain=bd" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="mailto:psprince0403@gmail.com" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="https://tmprince.netlify.app/" class="social-link">
                            <i class="fa-solid fa-user-tie"></i>
                        </a>
                    </div>
                </div>

                <!-- Team Member 6 -->
                <!-- <div class="team-member-card bg-[var(--bg-card)] rounded-2xl p-8 border border-[var(--border-color)] text-center" data-aos="zoom-in" data-aos-delay="500">
                    <div class="member-avatar" style="width: 120px; height: 120px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary-color);">
                        <img src="https://ui-avatars.com/api/?name=Alex+Martinez&size=200&background=3b82f6&color=fff&bold=true" alt="Alex Martinez" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 class="text-xl font-bold mb-1">Alex Martinez</h3>
                    <p class="text-sm mb-2" style="color:var(--primary-color); font-weight: 600;">DevOps Engineer</p>
                    <p class="text-sm mb-4" style="color:var(--text-secondary)">Scaling infrastructure and deployment</p>
                    <div class="flex justify-center gap-3">
                        <a href="#" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div> -->
            </div>

            <!-- Company Info -->
            <div class="bg-gradient-to-r from-[var(--bg-card)] to-[var(--bg-secondary)] rounded-2xl p-12 border border-[var(--border-color)] text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl font-bold mb-4 glow-text">About QueryStrom</h2>
                <p class="text-lg mb-6 max-w-2xl mx-auto" style="color:var(--text-secondary)">
                    QueryStrom Team is a dedicated group of developers and designers committed to creating innovative e-commerce solutions. We believe in the power of technology to transform business and improve user experiences.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="/buyer_dashboard/helpCenter.php" class="bg-[var(--primary-color)] hover:opacity-90 text-white px-8 py-3 rounded-lg font-medium transition-all inline-flex items-center gap-2">
                        <i class="fas fa-question-circle"></i>
                        <span>Get Help</span>
                    </a>
                    <a href="mailto:contact@querystrom.com" class="bg-[var(--bg-secondary)] hover:bg-[var(--border-color)] px-8 py-3 rounded-lg font-medium transition-all border border-[var(--border-color)] inline-flex items-center gap-2">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Us</span>
                    </a>
                </div>
            </div>

            <!-- Footer Container -->
            <div id="footerContainer" class="mt-8"></div>
        </div>
    </main>

    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
    <script>
        async function loadFooter() {
            const response = await fetch('../html/footer.php');
            const html = await response.text();
            document.getElementById('footerContainer').innerHTML = html;
        }
        loadFooter();
        
        window.addEventListener('load', () => {
            setTimeout(() => {
                AOS.init({
                    duration: 600,
                    once: true,
                    offset: 50
                });
            }, 200);
        });
    </script>
</body>
</html>
