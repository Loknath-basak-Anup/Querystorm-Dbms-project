<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cookie Settings | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        .card-glow {
            position: relative;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(6, 182, 212, 0.1));
            border: 1.5px solid rgba(34, 197, 94, 0.3);
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.15), inset 0 0 20px rgba(34, 197, 94, 0.05);
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
            overflow: hidden;
        }
        .card-glow::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.5), transparent);
            border-radius: 50%;
            animation: orbitalMove 10s linear infinite;
            pointer-events: none;
        }
        @keyframes orbitalMove {
            0% { transform: translate(0, 0); }
            50% { transform: translate(50px, -50px); }
            100% { transform: translate(0, 0); }
        }
        .card-glow:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(6, 182, 212, 0.2));
            box-shadow: 0 20px 50px rgba(34, 197, 94, 0.3), inset 0 0 30px rgba(34, 197, 94, 0.1);
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(34, 197, 94, 0.5);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #22c55e, #06b6d4);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(251, 191, 36, 0.3);
            margin-bottom: 1rem;
        }
        .badge-accent {
            display: inline-block;
            background: linear-gradient(135deg, #22c55e, #06b6d4);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(6, 182, 212, 0.15) 50%, rgba(14, 165, 233, 0.15) 100%);
            border: 2px solid rgba(34, 197, 94, 0.2);
            box-shadow: 0 20px 60px rgba(34, 197, 94, 0.2);
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -200px;
            right: -200px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.2), transparent);
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body class="dark-mode">
    <div id="sidebarContainer"></div>
    <main class="main-content">
        <div id="navbarContainer"></div>
        <script>
            async function loadNavbar() {
                const response = await fetch('./navbar.php');
                const html = await response.text();
                document.getElementById('navbarContainer').innerHTML = html;
                const pageTitle = document.querySelector('.page-title-navbar');
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-cookie"></i> Cookies';
            }
            loadNavbar();
        </script>

        <div class="page-content">
            <!-- Hero Section -->
            <div class="hero-section rounded-3xl p-16 mb-12 text-center relative" data-aos="zoom-in">
                <div class="flex justify-center items-center mb-8">
                    <img src="../images/svgs/cookie.svg" alt="Cookie Animation" style="width: 300px; height: 300px; display: block; margin: 0 auto;" />
                </div>
                <h1 class="text-5xl font-black mb-4 bg-gradient-to-r from-emerald-400 via-cyan-500 to-sky-600 bg-clip-text text-transparent">Cookie Preferences</h1>
                <p class="text-xl" style="color:var(--text-secondary)">Control how we enhance your experience</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Essential Cookies Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Required</div>
                                <h2 class="text-2xl font-bold mb-3">Essential Cookies</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">These cookies are mandatory for core platform functionality. They enable authentication, session management, security features, and cart operations.</p>
                                <div class="mt-4 p-4 bg-gradient-to-r from-emerald-500/10 to-cyan-500/10 rounded-lg border border-emerald-500/20">
                                    <p style="color:var(--text-secondary)" class="text-sm">🔒 Always enabled - Cannot be disabled</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Cookies Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Optional</div>
                                <h2 class="text-2xl font-bold mb-4">Analytics Cookies</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">Help us understand user behavior and improve our platform. These cookies track page views, click patterns, and navigation flow.</p>
                                <div class="mt-4 flex items-center gap-4 p-4 bg-gradient-to-r from-cyan-500/10 to-sky-500/10 rounded-lg border border-cyan-500/20">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="analytics" class="w-6 h-6 accent-cyan-500" checked>
                                        <span class="text-sm font-semibold">Enable Analytics</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personalization Cookies Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-sparkles"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Optional</div>
                                <h2 class="text-2xl font-bold mb-4">Personalization Cookies</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">Enable personalized product recommendations, saved preferences, and tailored shopping experience based on your browsing history.</p>
                                <div class="mt-4 flex items-center gap-4 p-4 bg-gradient-to-r from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="personalization" class="w-6 h-6 accent-green-500">
                                        <span class="text-sm font-semibold">Enable Personalization</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <button id="saveCookies" class="bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 text-white px-8 py-4 rounded-lg font-bold text-lg inline-flex items-center gap-3 shadow-lg hover:shadow-xl transition-all" data-aos="fade-up">
                        <i class="fas fa-check-circle text-xl"></i>
                        <span>Save My Preferences</span>
                    </button>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Cookie Info Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left">
                        <div class="text-center mb-4">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-emerald-500 to-cyan-500 flex items-center justify-center">
                                <i class="fas fa-cookie text-2xl text-white"></i>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold mb-4 text-center">About Cookies</h3>
                        <div class="space-y-3">
                            <div class="p-3 bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                                <p class="text-sm font-semibold">🍪 What are cookies?</p>
                                <p style="color:var(--text-secondary)" class="text-xs mt-1">Small files stored to remember your preferences</p>
                            </div>
                            <div class="p-3 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                                <p class="text-sm font-semibold">⏰ Expiration</p>
                                <p style="color:var(--text-secondary)" class="text-xs mt-1">Most cookies expire within 1-2 years</p>
                            </div>
                            <div class="p-3 bg-sky-500/10 rounded-lg border border-sky-500/20">
                                <p class="text-sm font-semibold">🔧 Browser Control</p>
                                <p style="color:var(--text-secondary)" class="text-xs mt-1">Manage cookies in your browser settings</p>
                            </div>
                        </div>
                    </div>

                    <!-- Related Policies Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="text-lg font-bold mb-4">Related Policies</h3>
                        <div class="space-y-2">
                            <a href="privacy.php" class="flex items-center gap-3 p-3 bg-gradient-to-r from-green-500/10 to-cyan-500/10 rounded-lg border border-green-500/20 hover:border-green-500/40 transition">
                                <i class="fas fa-shield text-green-500"></i>
                                <span class="text-sm font-semibold">Privacy Policy</span>
                            </a>
                            <a href="terms.php" class="flex items-center gap-3 p-3 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 rounded-lg border border-blue-500/20 hover:border-blue-500/40 transition">
                                <i class="fas fa-file-contract text-blue-500"></i>
                                <span class="text-sm font-semibold">Terms of Service</span>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <div id="footerContainer"></div>
    <script src="../assets/js/products_page.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        async function loadFooter() {
            const response = await fetch('./footer.php');
            const html = await response.text();
            document.getElementById('footerContainer').innerHTML = html;
        }
        loadFooter();
        window.addEventListener('load', () => {
            setTimeout(() => {
                AOS.init({ duration: 600, once: true, offset: 50 });
            }, 200);
        });

        document.getElementById('saveCookies').addEventListener('click', () => {
            const analytics = document.getElementById('analytics').checked;
            const personalization = document.getElementById('personalization').checked;
            localStorage.setItem('qm_cookie_analytics', analytics ? '1' : '0');
            localStorage.setItem('qm_cookie_personalization', personalization ? '1' : '0');
            alert('Your cookie preferences have been saved.');
        });
    </script>
</body>
</html>