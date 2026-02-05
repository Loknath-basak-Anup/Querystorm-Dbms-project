<?php
require_once __DIR__ . "/includes/session.php";
// Landing page now always shows, even for logged-in users.
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickMart - Smart Marketplace </title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@100..800&family=Baloo+Da+2:wght@400..800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0a0a0a',
                        glass: 'rgba(255, 255, 255, 0.05)',
                        glassBorder: 'rgba(255, 255, 255, 0.1)',
                        primary: '#8b5cf6', // Violet
                        secondary: '#ec4899', // Pink
                        accent: '#3b82f6', // Blue
                    },
                    fontFamily: {
                        sans: ['Baloo Da 2', 'sans-serif'],
                        bengali: ['Anek Bangla', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Lottie Animation -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-darkBg dark:text-white transition-colors duration-300 overflow-x-hidden">

    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 z-[9999] bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-purple-900 dark:to-blue-900 flex flex-col items-center justify-center">
        <div class="text-center space-y-8">
            <!-- Logo Animation -->
            <div class="animate-bounce-slow">
                <img src="images/qmart_logo2.png" alt="QuickMart" class="w-48 h-auto mx-auto drop-shadow-2xl">
            </div>
            
            <!-- Progress Bar -->
            <div class="w-80 mx-auto">
                <div class="h-2 bg-white/30 dark:bg-black/30 rounded-full overflow-hidden backdrop-blur-sm">
                    <div id="loading-bar" class="h-full bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                </div>
                <p id="loading-percent" class="text-sm font-semibold mt-3 text-gray-700 dark:text-gray-300">0%</p>
            </div>
            
            <!-- Funny Loading Messages -->
            <div class="h-8">
                <p id="loading-text" class="text-lg font-medium text-gray-600 dark:text-gray-400 animate-pulse">Waking up the hamsters...</p>
            </div>
        </div>
    </div>

    <!-- Mouse Follow Flare -->
    <div id="mouse-flare"></div>
    
    <!-- Background Blobs (Bubble Animation) -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary/30 rounded-full blur-[100px] animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-secondary/20 rounded-full blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-0 left-1/3 w-96 h-96 bg-accent/20 rounded-full blur-[100px] animate-blob animation-delay-4000"></div>
    </div>

    <!-- Navbar -->
    <nav class="fixed w-full z-50 backdrop-blur-md border-b border-gray-300 dark:border-white/10 bg-white/90 dark:bg-black/50 shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="flex items-center gap-3">
                <img class="w-36 h-auto" src="images/qmart_logo2.png" alt="QuickMart logo" loading="eager">
            </a>
            
            <div class="hidden md:flex gap-8 text-sm font-medium text-gray-700 dark:text-gray-300">
                <a href="shop.php" class="hover:text-primary hover:scale-110 transition-all duration-300 relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all after:duration-300 hover:after:w-full" data-i18n="nav_solutions">Shop</a>
                <a href="#popular-categories" class="hover:text-primary hover:scale-110 transition-all duration-300 relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all after:duration-300 hover:after:w-full" data-i18n="nav_resources">Categories</a>
                
                <a href="html/sellers_overview.php" class="hover:text-primary hover:scale-110 transition-all duration-300 relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all after:duration-300 hover:after:w-full" data-i18n="nav_pricing">Sellers</a>
            </div>

            <div class="flex items-center gap-4">
                <!-- Language Toggle -->
                <button id="lang-toggle" class="text-sm font-bold border-2 border-gray-400 dark:border-white/20 px-3 py-1 rounded-full hover:bg-gradient-to-r hover:from-blue-500 hover:to-purple-500 hover:text-white hover:border-transparent transition-all duration-300 hover:scale-110 hover:shadow-lg text-gray-700 dark:text-white">
                    BN
                </button>
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-white/10 transition-all duration-300 hover:scale-110 hover:rotate-12">
                    <i class="fa-solid fa-sun hidden dark:block text-yellow-400"></i>
                    <i class="fa-solid fa-moon block dark:hidden text-gray-600"></i>
                </button>
                <a href="html/products_page.php" class="bg-gradient-to-r from-blue-500 to-purple-600 border-0 hover:from-blue-600 hover:to-purple-700 text-white px-5 py-2 rounded-full text-sm font-medium transition-all duration-300 hover:scale-105 hover:shadow-xl shadow-lg" data-i18n="btn_get_started">
                    Start Shopping
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 relative">
        <div class="container mx-auto px-4 text-center" data-aos="fade-up">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                <span data-i18n="hero_title_1">Shop Smarter,</span><br>
                <span data-i18n="hero_title_2">Live Better</span>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-10 text-lg" data-i18n="hero_desc">
                Discover endless possibilities at QuickMart — your one-stop destination for electronics, groceries, fashion, and more. Quality products, unbeatable prices, delivered fast.
            </p>
            <a href="html/products_page.php" class="inline-block bg-gradient-to-r from-blue-400 to-pink-500 text-white px-8 py-3 rounded-full font-semibold text-lg hover:shadow-2xl hover:shadow-pink-500/50 transition-all duration-300 transform hover:scale-110 hover:-translate-y-1 animate-pulse-slow" data-i18n="hero_cta">
                Explore Now
            </a>
        </div>

        <!-- Marketplace Hero Animation -->
        <div class="container mx-auto px-4 mt-16" data-aos="zoom-in" data-aos-duration="1000">
            <lottie-player 
                src="images/marketplace.json" 
                background="transparent" 
                speed="1" 
                style="width: 100%; max-width: 800px; height: 400px; margin: 0 auto;" 
                loop 
                autoplay>
            </lottie-player>
        </div>
    </section>

    <!-- Project Overview -->
    <section class="py-20 from-blue-50 via-purple-50 to-pink-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-14">
                <div class="inline-block px-3 py-1 text-xs border border-white/20 rounded-full mb-4">WHY CHOOSE US</div>
                <h2 class="text-3xl md:text-4xl font-bold" data-i18n="overview_title">Why QuickMart Stands Out</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-3 max-w-3xl mx-auto" data-i18n="overview_desc">Experience seamless shopping with lightning-fast delivery, verified sellers, secure payments, and 24/7 customer support. Your satisfaction, guaranteed.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="p-6 rounded-xl border-2 border-blue-200 dark:border-white/10 bg-white dark:bg-[#151515] shadow-md hover:shadow-2xl hover:border-blue-400 transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up">
                    <i class="fa-solid fa-rocket text-blue-500 text-3xl group-hover:scale-125 transition-transform duration-300"></i>
                    <h3 class="font-bold mt-3 text-gray-800 dark:text-white" data-i18n="overview_objective">Lightning Fast Delivery</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2" data-i18n="overview_objective_desc">Same-day delivery on thousands of items. Order before noon, receive by evening. Your time matters to us.</p>
                </div>
                <div class="p-6 rounded-xl border-2 border-purple-200 dark:border-white/10 bg-white dark:bg-[#151515] shadow-md hover:shadow-2xl hover:border-purple-400 transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up" data-aos-delay="100">
                    <i class="fa-solid fa-shield-halved text-purple-500 text-3xl group-hover:scale-125 transition-transform duration-300"></i>
                    <h3 class="font-bold mt-3 text-gray-800 dark:text-white" data-i18n="overview_scope">Secure Payments</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2" data-i18n="overview_scope_desc">Shop with confidence. All transactions protected with bank-level encryption. Multiple payment options available.</p>
                </div>
                <div class="p-6 rounded-xl border-2 border-rose-200 dark:border-white/10 bg-white dark:bg-[#151515] shadow-md hover:shadow-2xl hover:border-rose-400 transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up" data-aos-delay="200">
                    <i class="fa-solid fa-headset text-rose-500 text-3xl group-hover:scale-125 transition-transform duration-300"></i>
                    <h3 class="font-bold mt-3 text-gray-800 dark:text-white" data-i18n="overview_outcomes">24/7 Support</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2" data-i18n="overview_outcomes_desc">Our dedicated support team is always here to help. Live chat, email, or call — reach us anytime, anywhere.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Data Model / Entities -->
    <section class="py-20 bg-white" style="background: transparent;">
        <div class="container mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-bold" data-i18n="data_model_title">Shop By Your Needs</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-3 max-w-3xl mx-auto" data-i18n="data_model_desc">Everything you need, all in one place. Browse by category and find exactly what you're looking for.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="p-5 rounded-xl border-2 border-blue-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-blue-500 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group" data-aos="zoom-in"><i class="fa-solid fa-mobile-screen-button text-blue-500 text-3xl group-hover:rotate-12 group-hover:scale-125 transition-all duration-300"></i><h4 class="font-bold mt-2 text-gray-800 dark:text-white">Electronics</h4><p class="text-xs text-gray-600 dark:text-gray-400">Latest gadgets & tech</p></div>
                <div class="p-5 rounded-xl border-2 border-emerald-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-emerald-500 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group" data-aos="zoom-in" data-aos-delay="50"><i class="fa-solid fa-cart-shopping text-emerald-500 text-3xl group-hover:rotate-12 group-hover:scale-125 transition-all duration-300"></i><h4 class="font-bold mt-2 text-gray-800 dark:text-white">Groceries</h4><p class="text-xs text-gray-600 dark:text-gray-400">Fresh daily essentials</p></div>
                <div class="p-5 rounded-xl border-2 border-purple-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-purple-500 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group" data-aos="zoom-in" data-aos-delay="100"><i class="fa-solid fa-shirt text-purple-500 text-3xl group-hover:rotate-12 group-hover:scale-125 transition-all duration-300"></i><h4 class="font-bold mt-2 text-gray-800 dark:text-white">Fashion</h4><p class="text-xs text-gray-600 dark:text-gray-400">Trending styles</p></div>
                <div class="p-5 rounded-xl border-2 border-pink-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-pink-500 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group" data-aos="zoom-in" data-aos-delay="150"><i class="fa-solid fa-couch text-pink-500 text-3xl group-hover:rotate-12 group-hover:scale-125 transition-all duration-300"></i><h4 class="font-bold mt-2 text-gray-800 dark:text-white">Home & Living</h4><p class="text-xs text-gray-600 dark:text-gray-400">Comfort & decor</p></div>
                <div class="p-5 rounded-xl border-2 border-amber-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-amber-500 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer group" data-aos="zoom-in" data-aos-delay="200"><i class="fa-solid fa-dumbbell text-amber-500 text-3xl group-hover:rotate-12 group-hover:scale-125 transition-all duration-300"></i><h4 class="font-bold mt-2 text-gray-800 dark:text-white">Sports & Fitness</h4><p class="text-xs text-gray-600 dark:text-gray-400">Stay active & healthy</p></div>
            </div>
        </div>
    </section>

    <!-- Categories Gallery -->
    <section id="popular-categories" class="py-20 from-purple-50 via-pink-50 to-blue-50" style="background: transparent;">
        <div class="container mx-auto px-6">
            <div class="flex items-end justify-between mb-12">
                <div class="text-center flex-1">
                    <h2 class="text-3xl md:text-4xl font-bold" data-i18n="categories_title">Popular Categories</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-3 max-w-3xl mx-auto" data-i18n="categories_desc">Sample catalog cards representing marketplace offerings.</p>
                </div>
                <button id="openCategoryModal" class="text-sm font-medium text-blue-400 hover:underline cursor-pointer">See all</button>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-blue-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up">
                    <img src="https://images.unsplash.com/photo-1498049794561-7780e7231661?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Electronics" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 dark:text-white">Electronics</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Phones</span> 
                            <span class="catag-prod">laptops</span> 
                            <span class="catag-prod">accessories</span>
                        </p>
                    </div>
            </div>

                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-emerald-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up" data-aos-delay="50">
                    <img src="https://images.unsplash.com/photo-1573518011645-aa7ab49d0aa6?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Groceries" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 dark:text-white">Groceries</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Rice</span> 
                            <span class="catag-prod">Dal</span> 
                            <span class="catag-prod">Fish</span>
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-purple-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up" data-aos-delay="100">
                    <img src="https://images.unsplash.com/photo-1464666495445-5a33228a808e?q=80&w=2012&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Fashion" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 dark:text-white">Fashion</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Men</span> 
                            <span class="catag-prod">Women</span> 
                            <span class="catag-prod">Kids</span>
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-pink-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="fade-up" data-aos-delay="150">
                    <img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Home & Living" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 dark:text-white">Home & Living</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Lamp</span> 
                            <span class="catag-prod">Bulb</span> 
                            <span class="catag-prod">Paints</span>
                        </p>
                    </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-20 bg-white" style="background: transparent;">
        <div class="container mx-auto px-6">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold" data-i18n="featured_title">Featured Products</h2>
                    <p class="text-gray-600 dark:text-gray-400" data-i18n="featured_desc">These is our featured products.</p>
                </div>
                <a href="html/products_page.php" class="text-sm font-medium text-blue-400 hover:underline">See all</a>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-blue-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="zoom-in">
                    <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?q=80&w=1200&auto=format&fit=crop" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Laptop">
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 dark:text-white">Ultrabook</h3>
                            <span class="text-sm text-emerald-500 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">$999</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Fast</span> 
                            <span class="catag-prod">Lightweight</span> 
                            <span class="catag-prod">Durable</span>
                        </p>
                    </div>
                </div>
                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-emerald-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="zoom-in" data-aos-delay="50">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=1200&auto=format&fit=crop" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Headphones">
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 dark:text-white">Headphones</h3>
                            <span class="text-sm text-emerald-500 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">$149</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Noise Cancelling</span>
                        </p>
                    </div>
                </div>
                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-purple-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="zoom-in" data-aos-delay="100">
                    <img src="https://images.unsplash.com/photo-1568651909298-94932bbe9026?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Grocery Basket">
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 dark:text-white">Grocery Basket</h3>
                            <span class="text-sm text-emerald-500 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">$29</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Daily essentials</span>
                        </p>
                    </div>
                </div>
                <div class="rounded-2xl overflow-hidden border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-pink-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 group cursor-pointer" data-aos="zoom-in" data-aos-delay="150">
                    <img src="https://images.unsplash.com/photo-1608231387042-66d1773070a5?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="h-40 w-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Sneakers">
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-gray-800 dark:text-white">Sneakers</h3>
                            <span class="text-sm text-emerald-500 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">$79</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 catag-prod-space">
                            <span class="catag-prod">Comfort & style</span> 
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features / AI Section -->
    <!-- <section class="py-20 bg-gray-100 dark:bg-[#080808] overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div data-aos="fade-right">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">AI-Powered</span> 
                        <span data-i18n="ai_title">Task Automation</span>
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6" data-i18n="ai_desc">
                        Automate recurring tasks with AI, saving you time by learning your habits, predicting needs, and managing routine workflows seamlessly.
                    </p>
                    <ul class="space-y-3 text-sm text-gray-500 dark:text-gray-400">
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Smart Reminders</li>
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Auto-scheduling</li>
                        <li class="flex items-center gap-3"><i class="fa-solid fa-check text-green-500"></i> Priority Sorting</li>
                    </ul>
                </div>
                <div class="relative" data-aos="fade-left">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 blur-[80px] opacity-20"></div>
                    <div class="glass-panel p-6 rounded-xl border border-white/10 bg-black/40 relative z-10">
                        <div class="flex items-start gap-4">
                            <div class="bg-purple-500/20 p-3 rounded-full"><i class="fa-solid fa-robot text-purple-400"></i></div>
                            <div>
                                <p class="text-sm text-gray-300 mb-2">"Remind me to finish the project report by 4 PM tomorrow."</p>
                                <div class="bg-white/5 p-3 rounded border border-white/10">
                                    <p class="text-xs text-green-400"><i class="fa-solid fa-check-circle"></i> Task Created: Project Report</p>
                                    <p class="text-xs text-gray-500 mt-1">Tomorrow, 4:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Testimonial Section -->
    <!-- <section class="py-20 relative bg-white dark:bg-[#0a0a0a]">
        <div class="container mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div data-aos="fade-up">
                <div class="inline-block px-3 py-1 text-xs border border-white/20 rounded-full mb-4">TESTIMONIAL</div>
                <h2 class="text-4xl font-bold mb-6" data-i18n="testimonial_title">How Our Users Enhance Their Productivity</h2>
            </div>
            <div class="bg-white/5 border border-white/10 p-8 rounded-2xl backdrop-blur-md relative" data-aos="fade-up" data-aos-delay="200">
                <i class="fa-solid fa-quote-left text-4xl text-white/10 absolute top-6 left-6"></i>
                <p class="text-gray-300 mb-6 relative z-10 italic" data-i18n="testimonial_quote">
                    "This app has completely transformed how I manage my tasks. With its smart reminders and automated workflows, I'm accomplishing more in less time."
                </p>
                <div class="flex items-center gap-3">
                    <img src="https://i.pravatar.cc/150?img=32" class="w-10 h-10 rounded-full">
                    <div>
                        <h4 class="font-bold text-sm">Emma Johnson</h4>
                        <p class="text-xs text-gray-500">Project Manager</p>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Pricing Section -->
    <section class="py-20 from-pink-50 via-blue-50 to-purple-50">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-16 text-gray-800 dark:text-white" data-i18n="pricing_title">Buy Cupons And Save Your Money</h2>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Card 1 - Bronze -->
                <div class="p-8 rounded-2xl border-2 border-gray-300 dark:border-white/10 hover:border-amber-600 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-[#151515] group cursor-pointer" data-aos="flip-left">
                    <!-- Bronze Ticket SVG -->
                    <div class="mb-4 flex justify-center">
                        <svg class="w-20 h-20 group-hover:scale-110 group-hover:rotate-12 transition-all duration-500" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="bronzeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#CD7F32;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#8B4513;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <path d="M50 10 L60 30 L80 35 L65 50 L68 70 L50 60 L32 70 L35 50 L20 35 L40 30 Z" fill="url(#bronzeGrad)" class="group-hover:animate-pulse"/>
                            <circle cx="50" cy="45" r="20" fill="#CD7F32" opacity="0.3"/>
                            <text x="50" y="52" font-size="20" font-weight="bold" fill="#FFF" text-anchor="middle">3</text>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 dark:text-gray-500 text-sm mb-2 font-bold" data-i18n="pricing_bronze_title">Bronze Ticket</h3>
                    <p class="text-3xl font-bold text-amber-700 dark:text-amber-500 mb-6" data-i18n="pricing_bronze_discount">5% OFF on 3 Items</p>
                    <ul class="text-left space-y-3 text-sm text-gray-600 dark:text-gray-400 mb-8">
                        <li><i class="fa-regular fa-circle-check mr-2 text-amber-600"></i><span data-i18n="pricing_bronze_feature1">Valid for Fashion, Groceries, and Electronics</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-amber-600"></i><span data-i18n="pricing_bronze_feature2">Only one-time use</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-amber-600"></i><span data-i18n="pricing_bronze_feature3">Applicable on purchases up to 1000৳</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-amber-600"></i><span data-i18n="pricing_bronze_feature4">Get an additional 2% off on 5 or more items</span></li>
                    </ul>
                    <button class="w-full py-2 rounded-full border-2 border-amber-600 hover:bg-amber-600 hover:text-white transition-all duration-300 text-sm font-medium text-gray-700 dark:text-white" data-i18n="pricing_bronze_btn">Buy Now</button>
                </div>


                <!-- Card 2 (Highlighted) - Golden -->
                <div class="p-8 rounded-2xl border-2 border-yellow-500 bg-gradient-to-b from-yellow-100 dark:from-yellow-900/20 to-white dark:to-black/40 relative transform md:-translate-y-4 shadow-xl hover:shadow-3xl transition-all duration-300 hover:-translate-y-6 cursor-pointer group" data-aos="flip-left" data-aos-delay="100">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 bg-gradient-to-r from-yellow-600 to-orange-600 text-white text-xs px-4 py-1 rounded-b-lg font-bold">Most Popular</div>
                    <!-- Golden Ticket SVG -->
                    <div class="mb-4 flex justify-center mt-4">
                        <svg class="w-24 h-24 group-hover:scale-125 group-hover:rotate-[360deg] transition-all duration-700" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="goldenGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#FFD700;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#FFA500;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#FFD700;stop-opacity:1" />
                                </linearGradient>
                                <filter id="glow">
                                    <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                                    <feMerge>
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>
                            <path d="M50 5 L62 28 L87 32 L68.5 50 L73 75 L50 62 L27 75 L31.5 50 L13 32 L38 28 Z" fill="url(#goldenGrad)" filter="url(#glow)" class="animate-pulse"/>
                            <circle cx="50" cy="42" r="22" fill="#FFA500" opacity="0.4"/>
                            <text x="50" y="50" font-size="18" font-weight="bold" fill="#8B4513" text-anchor="middle">10+</text>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 dark:text-gray-500 text-sm mb-2 font-bold" data-i18n="pricing_golden_title">Golden Ticket</h3>
                    <p class="text-3xl font-bold text-yellow-600 mb-6" data-i18n="pricing_golden_discount">15% OFF on 10+ Items</p>
                    <ul class="text-left space-y-3 text-sm text-gray-600 dark:text-gray-400 mb-8">
                        <li><i class="fa-regular fa-circle-check mr-2 text-yellow-500"></i><span data-i18n="pricing_golden_feature1">Valid for Fashion, Groceries, Electronics, and Home Appliances</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-yellow-500"></i><span data-i18n="pricing_golden_feature2">For purchases above 3000৳</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-yellow-500"></i><span data-i18n="pricing_golden_feature3">Free delivery on all orders</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-yellow-500"></i><span data-i18n="pricing_golden_feature4">Extra 10% off for repeat customers</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-yellow-500"></i><span data-i18n="pricing_golden_feature5">Limited time offer! Available until end of month</span></li>
                    </ul>
                    <button class="w-full py-2 rounded-full bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-lg hover:shadow-2xl hover:from-yellow-600 hover:to-orange-700 transition-all duration-300 text-sm font-medium" data-i18n="pricing_golden_btn">Get Started</button>
                </div>

                <!-- Card 3 - Silver -->
                <div class="p-8 rounded-2xl border-2 border-gray-300 dark:border-white/10 hover:border-gray-400 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white dark:bg-[#151515] group cursor-pointer" data-aos="flip-left" data-aos-delay="200">
                    <!-- Silver Ticket SVG -->
                    <div class="mb-4 flex justify-center">
                        <svg class="w-20 h-20 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-500" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="silverGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#C0C0C0;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#E8E8E8;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#A8A8A8;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <path d="M50 10 L60 30 L80 35 L65 50 L68 70 L50 60 L32 70 L35 50 L20 35 L40 30 Z" fill="url(#silverGrad)" class="group-hover:animate-pulse"/>
                            <circle cx="50" cy="45" r="20" fill="#D3D3D3" opacity="0.3"/>
                            <text x="50" y="52" font-size="20" font-weight="bold" fill="#505050" text-anchor="middle">5</text>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 dark:text-gray-500 text-sm mb-2 font-bold" data-i18n="pricing_silver_title">Silver Ticket</h3>
                    <p class="text-3xl font-bold text-gray-600 dark:text-gray-400 mb-6" data-i18n="pricing_silver_discount">10% OFF on 5 Items</p>
                    <ul class="text-left space-y-3 text-sm text-gray-600 dark:text-gray-400 mb-8">
                        <li><i class="fa-regular fa-circle-check mr-2 text-gray-500"></i><span data-i18n="pricing_silver_feature1">Valid for Fashion, Electronics & Groceries</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-gray-500"></i><span data-i18n="pricing_silver_feature2">For purchases between 1000৳ to 3000৳</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-gray-500"></i><span data-i18n="pricing_silver_feature3">Extra 5% off on orders of 10+ items</span></li>
                        <li><i class="fa-regular fa-circle-check mr-2 text-gray-500"></i><span data-i18n="pricing_silver_feature4">Exclusive for first-time buyers on your second purchase</span></li>
                    </ul>
                    <button class="w-full py-2 rounded-full border-2 border-gray-500 hover:bg-gray-500 hover:text-white transition-all duration-300 text-sm font-medium text-gray-700 dark:text-white" data-i18n="pricing_silver_btn">Grab Now</button>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer / CTA -->
    <footer class="relative pt-20 pb-10 overflow-hidden">
        <!-- Footer Glow -->
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full h-96 bg-gradient-to-t from-blue-600/20 to-transparent blur-[100px] -z-10"></div>

        <div class="container mx-auto px-6 text-center mb-16" data-aos="zoom-in">
            <h2 class="text-4xl font-bold mb-4" data-i18n="footer_cta_title">Take Control of Your Day</h2>
<p class="text-gray-400 mb-8" data-i18n="footer_cta_desc">Boost your productivity with ease—organize, prioritize, and achieve more every day.</p>

            <a href="html/products_page.php">
                <button class="bg-gradient-to-r from-blue-400 to-pink-500 text-white px-8 py-3 rounded-full font-semibold hover:shadow-lg hover:shadow-pink-500/30 transition">
                Start now - it's free!
            </button>
            </a>
        </div>

        <div class="container mx-auto px-6 border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
            <div class="flex items-center gap-2 mb-4 md:mb-0">
                <img src="images/qmart_logo2.png" class="w-50 h-6" alt="QuickMart small logo">
                <span class="ml-4">All rights reserved © QuickMart 2025</span>
            </div>
            <div class="flex gap-8">
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-300">Company</span>
                    <a href="#">Updates</a>
                    <a href="#">About</a>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-300">Legal</span>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-bold text-gray-300">Social</span>
                    <a href="#">Twitter (X)</a>
                    <a href="#">Discord</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Category Modal -->
    <div id="categoryModal" class="fixed inset-0 z-[9999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-2xl shadow-2xl border-2 border-gray-200 dark:border-white/10 w-full max-w-4xl max-h-[80vh] flex flex-col">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-200 dark:border-white/10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">All Categories</h3>
                    <button id="closeCategoryModal" class="p-2 hover:bg-gray-100 dark:hover:bg-white/10 rounded-full transition-all duration-300">
                        <i class="fa-solid fa-xmark text-2xl text-gray-600 dark:text-gray-400"></i>
                    </button>
                </div>
                <!-- Search Box -->
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input 
                        type="text" 
                        id="categorySearch" 
                        placeholder="Search categories..." 
                        class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 dark:border-white/20 rounded-xl bg-white dark:bg-[#0f0f0f] text-gray-800 dark:text-white focus:outline-none focus:border-blue-500 transition-all duration-300"
                    />
                </div>
            </div>
            
            <!-- Modal Body (Scrollable) -->
            <div class="overflow-y-auto p-6 flex-1">
                <div id="categoryList" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Categories will be inserted here -->
                </div>
                <p id="noResults" class="hidden text-center text-gray-500 dark:text-gray-400 py-8">No categories found</p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="script.js"></script>
</body>
</html>
