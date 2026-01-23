<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shipping Information | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        .card-glow {
            position: relative;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(6, 182, 212, 0.1));
            border: 1.5px solid rgba(102, 126, 234, 0.3);
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15), inset 0 0 20px rgba(102, 126, 234, 0.05);
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
            background: radial-gradient(circle, rgba(102, 126, 234, 0.5), transparent);
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
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(6, 182, 212, 0.2));
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3), inset 0 0 30px rgba(102, 126, 234, 0.1);
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(102, 126, 234, 0.5);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #06b6d4);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            margin-bottom: 1rem;
        }
        .badge-accent {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #06b6d4);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(6, 182, 212, 0.15) 50%, rgba(79, 172, 254, 0.15) 100%);
            border: 2px solid rgba(102, 126, 234, 0.2);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.2);
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
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2), transparent);
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
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-truck"></i> Shipping';
            }
            loadNavbar();
        </script>

        <div class="page-content">
            <!-- Hero Section -->
            <div class="hero-section rounded-3xl p-16 mb-12 text-center relative" data-aos="zoom-in">
                <div class="flex justify-center mb-8">
                    <img src="../images/svgs/shipping.svg" alt="Shipping Animation" style="width: 300px; height: 300px; display: block; margin: 0 auto;" />
                </div>
                <h1 class="text-5xl font-black mb-4 bg-gradient-to-r from-indigo-400 via-cyan-500 to-teal-400 bg-clip-text text-transparent">Shipping Made Simple</h1>
                <p class="text-xl" style="color:var(--text-secondary)">Fast, reliable delivery to your doorstep</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Delivery Areas Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Service Areas</div>
                                <h2 class="text-2xl font-bold mb-3">Coverage Across Bangladesh</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">We deliver across major cities and metropolitan areas. Coverage includes Dhaka, Chittagong, Sylhet, Rajshahi, and other key regions. Availability depends on your exact location during checkout.</p>
                                <div class="mt-4 p-4 bg-gradient-to-r from-indigo-500/10 to-cyan-500/10 rounded-lg border border-indigo-500/20">
                                    <p style="color:var(--text-secondary)" class="text-sm">✨ Express delivery available in major metro areas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timelines Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Timeline</div>
                                <h2 class="text-2xl font-bold mb-4">Delivery Timelines</h2>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-3 bg-gradient-to-r from-indigo-500/10 to-cyan-500/10 rounded-lg border border-indigo-500/20">
                                        <span class="font-semibold">Standard Delivery</span>
                                        <span class="px-3 py-1 bg-indigo-500 text-white rounded-full text-sm">2-3 days</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-gradient-to-r from-cyan-500/10 to-teal-500/10 rounded-lg border border-cyan-500/20">
                                        <span class="font-semibold">Express Delivery</span>
                                        <span class="px-3 py-1 bg-cyan-500 text-white rounded-full text-sm">1-2 days</span>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-gradient-to-r from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                                        <span class="font-semibold">Pre-Orders</span>
                                        <span class="px-3 py-1 bg-green-500 text-white rounded-full text-sm">Varies</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charges Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Pricing</div>
                                <h2 class="text-2xl font-bold mb-3">Transparent Shipping Charges</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">Charges vary based on weight, distance, and delivery speed. All costs are calculated at checkout with full transparency. Free shipping available on orders above 2,500 BDT.</p>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="p-3 bg-gradient-to-br from-purple-500/10 to-blue-500/10 rounded-lg border border-purple-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">Standard Rate</p>
                                        <p class="text-lg font-bold">৳ 60-150</p>
                                    </div>
                                    <div class="p-3 bg-gradient-to-br from-pink-500/10 to-red-500/10 rounded-lg border border-pink-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">Express Rate</p>
                                        <p class="text-lg font-bold">৳ 150-300</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Support Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center">
                                <i class="fas fa-headset text-2xl text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Need Assistance?</h3>
                            <p class="text-sm" style="color:var(--text-secondary); margin-bottom: 1rem;">Our support team is ready to help with any shipping questions.</p>
                            <a href="./helpCenter.php" class="w-full bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center gap-2 font-semibold transition-all">
                                <i class="fas fa-question-circle"></i>
                                <span>Get Help</span>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="text-lg font-bold mb-4">Quick Stats</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-blue-500/10 rounded-lg border border-blue-500/20">
                                <i class="fas fa-check-circle text-blue-500 text-xl"></i>
                                <span class="text-sm">99% On-time Delivery</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-green-500/10 rounded-lg border border-green-500/20">
                                <i class="fas fa-globe text-green-500 text-xl"></i>
                                <span class="text-sm">Nationwide Coverage</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                                <i class="fas fa-star text-cyan-500 text-xl"></i>
                                <span class="text-sm">4.8/5 Rating</span>
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