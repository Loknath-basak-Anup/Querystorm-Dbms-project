<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms of Service | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        .card-glow {
            position: relative;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border: 1.5px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.15), inset 0 0 20px rgba(99, 102, 241, 0.05);
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
            background: radial-gradient(circle, rgba(99, 102, 241, 0.5), transparent);
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
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.3), inset 0 0 30px rgba(99, 102, 241, 0.1);
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(99, 102, 241, 0.5);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
            margin-bottom: 1rem;
        }
        .badge-accent {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 50%, rgba(168, 85, 247, 0.15) 100%);
            border: 2px solid rgba(99, 102, 241, 0.2);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.2);
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
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2), transparent);
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
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-file-contract"></i> Terms';
            }
            loadNavbar();
        </script>

        <div class="page-content">
            <!-- Hero Section -->
            <div class="hero-section rounded-3xl p-16 mb-12 text-center relative" data-aos="zoom-in">
                <div class="flex justify-center mb-8">
                    <img src="../images/svgs/terms.svg" alt="Terms Animation" style="width: 300px; height: 300px; display: block; margin: 0 auto;" />
                </div>
                <h1 class="text-5xl font-black mb-4 bg-gradient-to-r from-indigo-400 via-violet-500 to-purple-600 bg-clip-text text-transparent">Terms of Service</h1>
                <p class="text-xl" style="color:var(--text-secondary)">Clear guidelines for a safe shopping experience</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Acceptance Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Agreement</div>
                                <h2 class="text-2xl font-bold mb-3">Acceptance of Terms</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">By accessing and using QuickMart, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service and all applicable laws and regulations.</p>
                                <div class="mt-4 p-4 bg-gradient-to-r from-blue-500/10 to-purple-500/10 rounded-lg border border-blue-500/20">
                                    <p style="color:var(--text-secondary)" class="text-sm">📋 Last updated: December 2024</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Responsibilities Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Responsibilities</div>
                                <h2 class="text-2xl font-bold mb-4">Your Responsibilities</h2>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-purple-500/10 to-blue-500/10 rounded-lg border border-purple-500/20">
                                        <i class="fas fa-check-circle text-purple-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Provide accurate & complete information</span>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-lg border border-indigo-500/20">
                                        <i class="fas fa-check-circle text-indigo-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Use platform lawfully & responsibly</span>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 rounded-lg border border-blue-500/20">
                                        <i class="fas fa-check-circle text-blue-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Comply with applicable laws</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Limitations Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Liability</div>
                                <h2 class="text-2xl font-bold mb-3">Limitation of Liability</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">QuickMart is not liable for indirect, incidental, or consequential damages arising from service use. We provide the platform "as is" without warranties.</p>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="p-3 bg-gradient-to-br from-red-500/10 to-orange-500/10 rounded-lg border border-red-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">No Indirect Damages</p>
                                    </div>
                                    <div class="p-3 bg-gradient-to-br from-pink-500/10 to-red-500/10 rounded-lg border border-pink-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">As-Is Service</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Related Policies Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left">
                        <div class="text-center mb-4">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center">
                                <i class="fas fa-file-lines text-2xl text-white"></i>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold mb-4 text-center">Related Policies</h3>
                        <div class="space-y-2">
                            <a href="privacy.php" class="flex items-center gap-3 p-3 bg-gradient-to-r from-green-500/10 to-cyan-500/10 rounded-lg border border-green-500/20 hover:border-green-500/40 transition">
                                <i class="fas fa-shield text-green-500"></i>
                                <span>Privacy Policy</span>
                            </a>
                            <a href="returns.php" class="flex items-center gap-3 p-3 bg-gradient-to-r from-orange-500/10 to-red-500/10 rounded-lg border border-orange-500/20 hover:border-orange-500/40 transition">
                                <i class="fas fa-undo text-orange-500"></i>
                                <span>Returns & Refunds</span>
                            </a>
                            <a href="shipping.php" class="flex items-center gap-3 p-3 bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-lg border border-purple-500/20 hover:border-purple-500/40 transition">
                                <i class="fas fa-truck text-purple-500"></i>
                                <span>Shipping Info</span>
                            </a>
                        </div>
                    </div>

                    <!-- Support Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left" data-aos-delay="100">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                                <i class="fas fa-headset text-2xl text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Questions?</h3>
                            <p class="text-sm" style="color:var(--text-secondary); margin-bottom: 1rem;">Our support team is here to help clarify any terms.</p>
                            <a href="./helpCenter.php" class="w-full bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center gap-2 font-semibold transition-all">
                                <i class="fas fa-comment-dots"></i>
                                <span>Contact Us</span>
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
    </script>
</body>
</html>