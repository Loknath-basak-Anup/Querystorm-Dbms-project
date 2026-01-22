<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Returns & Refunds | QuickMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/products_page.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        .card-glow {
            position: relative;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.1));
            border: 1.5px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.15), inset 0 0 20px rgba(59, 130, 246, 0.05);
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
            background: radial-gradient(circle, rgba(59, 130, 246, 0.5), transparent);
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(99, 102, 241, 0.2));
            box-shadow: 0 20px 50px rgba(59, 130, 246, 0.3), inset 0 0 30px rgba(59, 130, 246, 0.1);
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(59, 130, 246, 0.5);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
            margin-bottom: 1rem;
        }
        .badge-accent {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.15) 50%, rgba(139, 92, 246, 0.15) 100%);
            border: 2px solid rgba(59, 130, 246, 0.2);
            box-shadow: 0 20px 60px rgba(59, 130, 246, 0.2);
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
            background: radial-gradient(circle, rgba(245, 158, 11, 0.2), transparent);
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
                if (pageTitle) pageTitle.innerHTML = '<i class="fas fa-undo"></i> Returns';
            }
            loadNavbar();
        </script>

        <div class="page-content">
            <!-- Hero Section -->
            <div class="hero-section rounded-3xl p-16 mb-12 text-center relative" data-aos="zoom-in">
                <div class="flex justify-center mb-8">
                    <img src="../images/svgs/refund.svg" alt="Returns Animation" style="width: 300px; height: 300px; display: block; margin: 0 auto;" />
                </div>
                <h1 class="text-5xl font-black mb-4 bg-gradient-to-r from-indigo-400 via-violet-500 to-blue-600 bg-clip-text text-transparent">Easy Returns & Refunds</h1>
                <p class="text-xl" style="color:var(--text-secondary)">Hassle-free process for your peace of mind</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Return Window Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-calendar-days"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Timeline</div>
                                <h2 class="text-2xl font-bold mb-3">Return Window</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">Items can be returned within 7 days of delivery if unopened and in original condition. Damaged or defective items can be reported within 30 days.</p>
                                <div class="mt-4 p-4 bg-gradient-to-r from-amber-500/10 to-pink-500/10 rounded-lg border border-amber-500/20">
                                    <p style="color:var(--text-secondary)" class="text-sm">📅 Extended returns available during sale season</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Requirements</div>
                                <h2 class="text-2xl font-bold mb-4">Eligibility Criteria</h2>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-violet-500/10 to-indigo-500/10 rounded-lg border border-violet-500/20">
                                        <i class="fas fa-box text-orange-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Original packaging must be intact</span>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-lg border border-blue-500/20">
                                        <i class="fas fa-sparkles text-purple-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">No signs of wear, damage, or use</span>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 rounded-lg border border-blue-500/20">
                                        <i class="fas fa-receipt text-blue-500 text-lg mt-0.5"></i>
                                        <span class="text-sm">Proof of purchase required</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refunds Card -->
                    <div class="card-glow rounded-2xl p-8" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-start gap-4">
                            <div class="icon-box flex-shrink-0">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="badge-accent">Payment</div>
                                <h2 class="text-2xl font-bold mb-3">Refund Process</h2>
                                <p class="text-base" style="color:var(--text-secondary); line-height: 1.8;">Refunds are issued within 5–7 business days to the original payment method after item inspection. Shipping costs are non-refundable.</p>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="p-3 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">Processing Time</p>
                                        <p class="text-lg font-bold">5-7 Days</p>
                                    </div>
                                    <div class="p-3 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 rounded-lg border border-blue-500/20">
                                        <p style="color:var(--text-secondary)" class="text-xs font-semibold">Refund Method</p>
                                        <p class="text-lg font-bold">Original</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Return Action Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center">
                                <i class="fas fa-arrow-rotate-left text-2xl text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold mb-2">Start a Return</h3>
                            <p class="text-sm" style="color:var(--text-secondary); margin-bottom: 1rem;">Go to your order history and select the item to return. We'll guide you through the process.</p>
                            <a href="../buyer_dashboard/history.php" class="w-full bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center gap-2 font-semibold transition-all">
                                <i class="fas fa-history"></i>
                                <span>My Orders</span>
                            </a>
                        </div>
                    </div>

                    <!-- FAQ Card -->
                    <div class="card-glow rounded-2xl p-6" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="text-lg font-bold mb-4">Common Questions</h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-violet-500/10 rounded-lg border border-violet-500/20">
                                <i class="fas fa-question text-violet-500 text-lg mt-0.5"></i>
                                <div class="text-sm">
                                    <p class="font-semibold">Can I return partial orders?</p>
                                    <p style="color:var(--text-secondary)" class="text-xs">Yes, you can return items individually</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                                <i class="fas fa-question text-indigo-500 text-lg mt-0.5"></i>
                                <div class="text-sm">
                                    <p class="font-semibold">Free return shipping?</p>
                                    <p style="color:var(--text-secondary)" class="text-xs">Return shipping label provided</p>
                                </div>
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