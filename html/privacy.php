<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Privacy Policy | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        .card-glow {
            position: relative;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 182, 212, 0.1));
            border: 1.5px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.15), inset 0 0 20px rgba(16, 185, 129, 0.05);
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
            background: radial-gradient(circle, rgba(16, 185, 129, 0.5), transparent);
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(6, 182, 212, 0.2));
            box-shadow: 0 20px 50px rgba(16, 185, 129, 0.3), inset 0 0 30px rgba(16, 185, 129, 0.1);
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(16, 185, 129, 0.5);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            margin-bottom: 1rem;
        }
        .badge-accent {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(6, 182, 212, 0.15) 50%, rgba(59, 130, 246, 0.15) 100%);
            border: 2px solid rgba(16, 185, 129, 0.2);
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.2);
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
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2), transparent);
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
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-shield"></i> Privacy Policy';
            }
            loadNavbar();
        </script>

        <div class="page-content">
            <!-- Hero Section -->
            <div class="hero-section rounded-3xl p-16 mb-12 text-center relative" data-aos="zoom-in">
                <div class="flex justify-center mb-8">
                    <img src="../images/svgs/privacy_policy.svg" alt="Privacy Animation" style="width: 300px; height: 300px; display: block; margin: 0 auto;" />
                </div>
                <h1 class="text-5xl font-black mb-4 bg-gradient-to-r from-teal-400 via-cyan-500 to-sky-600 bg-clip-text text-transparent">Your Privacy Matters</h1>
                <p class="text-xl" style="color:var(--text-secondary)">We protect your data with transparency and trust</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Data Collection Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Collection</div>
                                <h2 class="text-2xl font-bold mb-3">Data Collection</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">We collect information you provide during signup, checkout, and support interactions. This includes name, email, address, payment details, and usage analytics.</p>
                                <div class="mt-4 p-4 bg-gradient-to-r from-green-500/10 to-cyan-500/10 rounded-lg border border-green-500/20">
                                    <p style="color:var(--text-secondary)" class="text-sm">🔐 All data transmission is encrypted</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Usage Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Usage</div>
                                <h2 class="text-2xl font-bold mb-4">How We Use Your Data</h2>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-cyan-500/10 to-blue-500/10 rounded-lg border border-cyan-500/20">
                                        <i class="fas fa-check-circle text-cyan-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Process orders and payments</span>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-teal-500/10 to-green-500/10 rounded-lg border border-teal-500/20">
                                        <i class="fas fa-check-circle text-teal-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Personalize your shopping experience</span>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-emerald-500/10 to-cyan-500/10 rounded-lg border border-emerald-500/20">
                                        <i class="fas fa-check-circle text-emerald-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Send promotional updates & support</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Security</div>
                                <h2 class="text-2xl font-bold mb-3">Data Protection</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">We implement SSL encryption, restricted access controls, regular security audits, and comply with international data protection standards.</p>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="p-3 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">Encryption</p>
                                        <p class="text-lg font-bold">SSL/TLS</p>
                                    </div>
                                    <div class="p-3 bg-gradient-to-br from-cyan-500/10 to-blue-500/10 rounded-lg border border-cyan-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">Standard</p>
                                        <p class="text-lg font-bold">GDPR</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Rights Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-green-500 to-cyan-500 flex items-center justify-center">
                                <i class="fas fa-shield-halved text-2xl text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Your Rights</h3>
                            <p class="text-sm" style="color:var(--text-secondary); margin-bottom: 1rem;">You can request data export or deletion anytime through our privacy portal.</p>
                            <a href="./helpCenter.php" class="w-full bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center gap-2 font-semibold transition-all">
                                <i class="fas fa-user-shield"></i>
                                <span>Privacy Portal</span>
                            </a>
                        </div>
                    </div>

                    <!-- Privacy Features -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="text-lg font-bold mb-4">Privacy Features</h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-green-500/10 rounded-lg border border-green-500/20">
                                <i class="fas fa-check-circle text-green-500 text-lg mt-0.5"></i>
                                <span class="text-sm font-semibold">Privacy Controls</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                                <i class="fas fa-check-circle text-cyan-500 text-lg mt-0.5"></i>
                                <span class="text-sm font-semibold">Opt-out Options</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-teal-500/10 rounded-lg border border-teal-500/20">
                                <i class="fas fa-check-circle text-teal-500 text-lg mt-0.5"></i>
                                <span class="text-sm font-semibold">Data Portability</span>
                            </div>
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
    </script>
</body>
</html>