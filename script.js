// Loading Screen Logic
const loadingMessages = [
    "Waking up the hamsters... 🐹",
    "Brewing fresh coffee... ☕",
    "Polishing product shelves... ✨",
    "Counting inventory... 📦",
    "Warming up the servers... 🔥",
    "Charging shopping carts... 🛒",
    "Testing the magic buttons... 🪄",
    "Finding the best deals... 💰",
    "Summoning discounts... 🎉",
    "Almost there... 🚀"
];

let currentProgress = 0;
let currentMessageIndex = 0;

function updateLoadingScreen() {
    const loadingBar = document.getElementById('loading-bar');
    const loadingPercent = document.getElementById('loading-percent');
    const loadingText = document.getElementById('loading-text');
    
    if (currentProgress < 100) {
        currentProgress += Math.random() * 15 + 5; // Random increment between 5-20
        if (currentProgress > 100) currentProgress = 100;
        
        loadingBar.style.width = currentProgress + '%';
        loadingPercent.textContent = Math.floor(currentProgress) + '%';
        
        // Update funny message every 20%
        const messageIndex = Math.floor(currentProgress / 10);
        if (messageIndex !== currentMessageIndex && messageIndex < loadingMessages.length) {
            currentMessageIndex = messageIndex;
            loadingText.textContent = loadingMessages[messageIndex];
        }
        
        setTimeout(updateLoadingScreen, 300);
    } else {
        finishLoading();
    }
}

function finishLoading() {
    const loadingScreen = document.getElementById('loading-screen');
    const mainContent = document.querySelector('nav').parentElement;
    
    // Add fade out animation
    loadingScreen.classList.add('fade-out');
    
    setTimeout(() => {
        loadingScreen.style.display = 'none';
        mainContent.classList.add('page-content');
    }, 500);
}

// Start loading when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if all critical resources are loaded
    if (document.readyState === 'complete') {
        updateLoadingScreen();
    } else {
        window.addEventListener('load', updateLoadingScreen);
    }
    
    // Initialize AOS after loading
    setTimeout(() => {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }, 1000);
});

// Initialize AOS (Animate On Scroll)
// (Moved to loading screen completion)

// Mouse Follow Flare Logic with enhanced effects
const flare = document.getElementById('mouse-flare');
document.addEventListener('mousemove', (e) => {
    const x = e.clientX;
    const y = e.clientY;
    
    // Use requestAnimationFrame for smoother performance
    requestAnimationFrame(() => {
        flare.style.left = `${x}px`;
        flare.style.top = `${y}px`;
    });
});

// Theme Toggle Logic (Dark/Light)
const themeToggle = document.getElementById('theme-toggle');
const htmlElement = document.documentElement;

// Check local storage or default to dark
if (localStorage.getItem('theme') === 'light') {
    htmlElement.classList.remove('dark');
} else {
    htmlElement.classList.add('dark');
}

themeToggle.addEventListener('click', () => {
    if (htmlElement.classList.contains('dark')) {
        htmlElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        htmlElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
});

// Language Toggle Logic (English/Bangla)
const langToggle = document.getElementById('lang-toggle');
let currentLang = 'en';

const translations = {
    en: {
        nav_solutions: "Shop",
        nav_resources: "Categories",
        nav_download: "Deals",
        nav_pricing: "Sellers",
        btn_get_started: "Start Shopping",
        hero_title_1: "Shop Smarter,",
        hero_title_2: "Live Better",
        hero_desc: "Discover endless possibilities at QuickMart — your one-stop destination for electronics, groceries, fashion, and more. Quality products, unbeatable prices, delivered fast.",
        hero_cta: "Explore Now",
        ui_add_task: "Add Task",
        ui_inbox: "Inbox",
        ui_today: "Today",
        task_title: "Panze web design & development",
        task_phase: "Design Phase:",
        ai_title: "Task Automation",
        ai_desc: "Automate recurring tasks with AI, saving you time by learning your habits, predicting needs, and managing routine workflows seamlessly.",
        testimonial_title: "How Our Users Enhance Their Productivity",
        testimonial_quote: "\"This app has completely transformed how I manage my tasks. With its smart reminders and automated workflows, I'm accomplishing more in less time.\"",
        pricing_title: "Buy Cupons And Save Your Money",
        pricing_bronze_title: "Bronze Ticket",
        pricing_bronze_discount: "5% OFF on 3 Items",
        pricing_bronze_feature1: "Valid for Fashion, Groceries, and Electronics",
        pricing_bronze_feature2: "Only one-time use",
        pricing_bronze_feature3: "Applicable on purchases up to 1000৳",
        pricing_bronze_feature4: "Get an additional 2% off on 5 or more items",
        pricing_bronze_btn: "Buy Now",
        pricing_silver_title: "Silver Ticket",
        pricing_silver_discount: "10% OFF on 5 Items",
        pricing_silver_feature1: "Valid for Fashion, Electronics & Groceries",
        pricing_silver_feature2: "For purchases between 1000৳ to 3000৳",
        pricing_silver_feature3: "Extra 5% off on orders of 10+ items",
        pricing_silver_feature4: "Exclusive for first-time buyers on your second purchase",
        pricing_silver_btn: "Grab Now",
        pricing_golden_title: "Golden Ticket",
        pricing_golden_discount: "15% OFF on 10+ Items",
        pricing_golden_feature1: "Valid for Fashion, Groceries, Electronics, and Home Appliances",
        pricing_golden_feature2: "For purchases above 3000৳",
        pricing_golden_feature3: "Free delivery on all orders",
        pricing_golden_feature4: "Extra 10% off for repeat customers",
        pricing_golden_feature5: "Limited time offer! Available until end of month",
        pricing_golden_btn: "Get Started",
        footer_cta_title: "Take Control of Your Shopping—No More 'Add to Cart' Regrets!",
        footer_cta_desc: "Organize your wishlist, check out fast, and get the best deals—because tomorrow is too late!",
        overview_title: "Why QuickMart Stands Out",
        overview_desc: "Experience seamless shopping with lightning-fast delivery, verified sellers, secure payments, and 24/7 customer support. Your satisfaction, guaranteed.",
        overview_objective: "Lightning Fast Delivery",
        overview_objective_desc: "Same-day delivery on thousands of items. Order before noon, receive by evening. Your time matters to us.",
        overview_scope: "Secure Payments",
        overview_scope_desc: "Shop with confidence. All transactions protected with bank-level encryption. Multiple payment options available.",
        overview_outcomes: "24/7 Support",
        overview_outcomes_desc: "Our dedicated support team is always here to help. Live chat, email, or call — reach us anytime, anywhere.",
        data_model_title: "Shop By Your Needs",
        data_model_desc: "Everything you need, all in one place. Browse by category and find exactly what you're looking for.",
        categories_title: "Popular Categories",
        categories_desc: "Sample catalog cards representing marketplace offerings.",
        featured_title: "Featured Products",
        featured_desc: "Demo cards used to illustrate product listings and pricing."
    },
    bn: {
        nav_solutions: "কেনাকাটা",
        nav_resources: "ক্যাটাগরি",
        nav_download: "অফার",
        nav_pricing: "বিক্রেতা",
        btn_get_started: "কেনাকাটা শুরু করুন",
        hero_title_1: "স্মার্ট শপিং,",
        hero_title_2: "সুন্দর জীবন",
        hero_desc: "কুইকমার্টে অসীম সম্ভাবনা আবিষ্কার করুন — ইলেকট্রনিক্স, মুদি, ফ্যাশন এবং আরও অনেক কিছুর জন্য আপনার ওয়ান-স্টপ গন্তব্য। মানসম্পন্ন পণ্য, অতুলনীয় দাম, দ্রুত ডেলিভারি।",
        hero_cta: "এখনই ঘুরে দেখুন",
        ui_add_task: "কাজ যোগ করুন",
        ui_inbox: "ইনবক্স",
        ui_today: "আজ",
        task_title: "পাঞ্জে ওয়েব ডিজাইন এবং ডেভেলপমেন্ট",
        task_phase: "ডিজাইন ধাপ:",
        ai_title: "টাস্ক অটোমেশন",
        ai_desc: "এআই এর মাধ্যমে পুনরাবৃত্তিমূলক কাজগুলি স্বয়ংক্রিয় করুন, আপনার অভ্যাস শিখে এবং প্রয়োজনগুলি অনুমান করে সময় বাঁচান।",
        testimonial_title: "আমাদের ব্যবহারকারীরা কীভাবে তাদের উৎপাদনশীলতা বৃদ্ধি করে",
        testimonial_quote: "\"এই অ্যাপটি আমার কাজ পরিচালনার পদ্ধতি পুরোপুরি বদলে দিয়েছে। স্মার্ট রিমাইন্ডার এবং অটোমেটেড ওয়ার্কফ্লোর মাধ্যমে আমি কম সময়ে বেশি কাজ করছি।\"",
        pricing_title: "কুপন কিনুন এবং টাকা বাঁচান—কারণ 'ব্যাংক ব্যালেন্স' বলছে 'আস্তে চল!'😅",
        pricing_bronze_title: "ব্রোঞ্জ টিকিট",
        pricing_bronze_discount: "৩টি পণ্যে ৫% ছাড়",
        pricing_bronze_feature1: "ফ্যাশন, মুদি, এবং ইলেকট্রনিক্সের জন্য বৈধ",
        pricing_bronze_feature2: "শুধুমাত্র একবার ব্যবহার করা যাবে",
        pricing_bronze_feature3: "১০০০৳ পর্যন্ত কেনাকাটায় প্রযোজ্য",
        pricing_bronze_feature4: "৫টি বা তার বেশি আইটেমে অতিরিক্ত ২% ছাড় পান",
        pricing_bronze_btn: "এখনই কিনুন",
        pricing_silver_title: "সিলভার টিকিট",
        pricing_silver_discount: "৫টি পণ্যে ১০% ছাড়",
        pricing_silver_feature1: "ফ্যাশন, ইলেকট্রনিক্স ও মুদির জন্য বৈধ",
        pricing_silver_feature2: "১০০০৳ থেকে ৩০০০৳ এর মধ্যে কেনাকাটায়",
        pricing_silver_feature3: "১০+ আইটেম অর্ডারে অতিরিক্ত ৫% ছাড়",
        pricing_silver_feature4: "প্রথমবার ক্রেতাদের দ্বিতীয় কেনাকাটায় এক্সক্লুসিভ",
        pricing_silver_btn: "এখনই নিন",
        pricing_golden_title: "গোল্ডেন টিকিট",
        pricing_golden_discount: "১০+ পণ্যে ১৫% ছাড়",
        pricing_golden_feature1: "ফ্যাশন, মুদি, ইলেকট্রনিক্স এবং হোম অ্যাপ্লায়েন্সের জন্য বৈধ",
        pricing_golden_feature2: "৩০০০৳ এর উপরে কেনাকাটায়",
        pricing_golden_feature3: "সব অর্ডারে ফ্রি ডেলিভারি",
        pricing_golden_feature4: "নিয়মিত গ্রাহকদের জন্য অতিরিক্ত ১০% ছাড়",
        pricing_golden_feature5: "সীমিত সময়ের অফার! মাস শেষ পর্যন্ত বৈধ",
        pricing_golden_btn: "শুরু করুন",
        footer_cta_title: "আজই আপনার শপিং নিয়ন্ত্রণে নিন—আর 'কার্টে এড' করার পর আফসোস করবেন না!",
        footer_cta_desc: "আপনার উইশলিস্ট সোজা করুন, দ্রুত চেকআউট করুন, আর দারুন ডিল পেয়ে যান—কারণ কালকে খুব দেরি!",
        overview_title: "কেন কুইকমার্ট আলাদা",
        overview_desc: "বিদ্যুৎগতি ডেলিভারি, যাচাইকৃত বিক্রেতা, নিরাপদ পেমেন্ট এবং ২৪/৭ কাস্টমার সাপোর্ট সহ নিরবচ্ছিন্ন শপিং অভিজ্ঞতা। আপনার সন্তুষ্টি, নিশ্চিত।",
        overview_objective: "বিদ্যুৎগতি ডেলিভারি",
        overview_objective_desc: "হাজারো আইটেমে একই দিনে ডেলিভারি। দুপুরের আগে অর্ডার করুন, সন্ধ্যায় পান। আপনার সময় আমাদের কাছে গুরুত্বপূর্ণ।",
        overview_scope: "নিরাপদ পেমেন্ট",
        overview_scope_desc: "আত্মবিশ্বাসের সাথে কেনাকাটা করুন। ব্যাংক-স্তরের এনক্রিপশন দিয়ে সুরক্ষিত সকল লেনদেন। একাধিক পেমেন্ট অপশন উপলব্ধ।",
        overview_outcomes: "২৪/৭ সাপোর্ট",
        overview_outcomes_desc: "আমাদের নিবেদিত সাপোর্ট টিম সর্বদা সাহায্যের জন্য এখানে। লাইভ চ্যাট, ইমেইল বা কল — যেকোনো সময়, যেকোনো জায়গায় আমাদের সাথে যোগাযোগ করুন।",
        data_model_title: "আপনার প্রয়োজন অনুযায়ী কিনুন",
        data_model_desc: "যা প্রয়োজন সবই এক জায়গায়। ক্যাটাগরি অনুসারে ব্রাউজ করুন এবং ঠিক যা খুঁজছেন তা খুঁজে নিন।",
        categories_title: "জনপ্রিয় ক্যাটাগরি",
        categories_desc: "মার্কেটপ্লেস অফারিং প্রদর্শনের জন্য স্যাম্পল কার্ড।",
        featured_title: "ফিচারড প্রোডাক্ট",
        featured_desc: "প্রোডাক্ট লিস্টিং ও প্রাইসিং বোঝাতে ডেমো কার্ডসমূহ।"
    }
};

langToggle.addEventListener('click', () => {
    currentLang = currentLang === 'en' ? 'bn' : 'en';
    langToggle.innerText = currentLang === 'en' ? 'BN' : 'EN';
    
    // Switch font family
    if (currentLang === 'bn') {
        document.body.classList.add('bengali-font');
    } else {
        document.body.classList.remove('bengali-font');
    }
    
    // Update text content based on data-i18n attributes
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[currentLang][key]) {
            element.childNodes.forEach(node => {
                // Replace only text nodes, keep icons intact
                if (node.nodeType === 3 && node.nodeValue.trim() !== '') {
                    node.nodeValue = translations[currentLang][key];
                }
                // Handle buttons where text is the only content inside tags sometimes
                if(element.tagName === 'SPAN' || element.tagName === 'P' || element.tagName === 'H1' || element.tagName === 'H2') {
                     element.innerText = translations[currentLang][key];
                }
            });
            
            // Specific handling for mixed content elements (like Download + Icon)
             if (key === 'nav_download') {
                element.innerHTML = `${translations[currentLang][key]} <i class="fa-solid fa-chevron-down text-xs"></i>`;
             }
             else if (key === 'nav_solutions' || key === 'nav_resources' || key === 'nav_pricing') {
                 element.textContent = translations[currentLang][key];
             }
             else if (element.tagName === 'BUTTON' || element.tagName === 'A') {
                 element.innerText = translations[currentLang][key];
             }
        }
    });
});

// Category Modal Logic
const categoryData = [
    { name: "Accessories", subcats: ["Bags", "Belts", "Watches", "Jewelry", "Sunglasses"] },
    { name: "Appliances", subcats: ["Refrigerators", "Washers", "Microwaves", "Air Conditioners"] },
    { name: "Baby Products", subcats: ["Diapers", "Toys", "Strollers", "Baby Food"] },
    { name: "Books", subcats: ["Fiction", "Non-fiction", "Comics", "Magazines"] },
    { name: "Electronics", subcats: ["Phones", "Laptops", "Tablets", "Cameras", "Headphones"] },
    { name: "Fashion", subcats: ["Men", "Women", "Kids", "Footwear"] },
    { name: "Furniture", subcats: ["Sofas", "Beds", "Tables", "Chairs"] },
    { name: "Groceries", subcats: ["Rice", "Dal", "Fish", "Vegetables", "Fruits"] },
    { name: "Health & Beauty", subcats: ["Skincare", "Makeup", "Haircare", "Vitamins"] },
    { name: "Home & Living", subcats: ["Lamps", "Bulbs", "Paints", "Decor"] },
    { name: "Jewelry", subcats: ["Rings", "Necklaces", "Bracelets", "Earrings"] },
    { name: "Kitchen", subcats: ["Cookware", "Utensils", "Storage", "Appliances"] },
    { name: "Music", subcats: ["Instruments", "Speakers", "Vinyl", "Accessories"] },
    { name: "Office Supplies", subcats: ["Stationery", "Printers", "Desks", "Chairs"] },
    { name: "Pet Supplies", subcats: ["Food", "Toys", "Beds", "Grooming"] },
    { name: "Sports & Fitness", subcats: ["Gym Equipment", "Yoga", "Running", "Cycling"] },
    { name: "Toys & Games", subcats: ["Action Figures", "Board Games", "Puzzles", "LEGO"] },
    { name: "Vehicles", subcats: ["Cars", "Bikes", "Parts", "Accessories"] }
].sort((a, b) => a.name.localeCompare(b.name)); // Sort A-Z

const modal = document.getElementById('categoryModal');
const openBtn = document.getElementById('openCategoryModal');
const closeBtn = document.getElementById('closeCategoryModal');
const searchInput = document.getElementById('categorySearch');
const categoryList = document.getElementById('categoryList');
const noResults = document.getElementById('noResults');

// Render categories
function renderCategories(filter = '') {
    const filtered = categoryData.filter(cat => 
        cat.name.toLowerCase().includes(filter.toLowerCase()) ||
        cat.subcats.some(sub => sub.toLowerCase().includes(filter.toLowerCase()))
    );

    categoryList.innerHTML = '';
    
    if (filtered.length === 0) {
        categoryList.classList.add('hidden');
        noResults.classList.remove('hidden');
        return;
    }

    categoryList.classList.remove('hidden');
    noResults.classList.add('hidden');

    filtered.forEach(category => {
        const categoryCard = document.createElement('div');
        categoryCard.className = 'p-4 rounded-xl border-2 border-gray-200 dark:border-white/10 bg-white dark:bg-[#151515] hover:border-blue-400 hover:shadow-xl transition-all duration-300 cursor-pointer';
        
        categoryCard.innerHTML = `
            <h4 class="font-bold text-gray-800 dark:text-white mb-2">${category.name}</h4>
            <div class="flex flex-wrap gap-1">
                ${category.subcats.map(sub => `<span class="catag-prod text-xs text-gray-600 dark:text-gray-400">${sub}</span>`).join('')}
            </div>
        `;
        
        categoryList.appendChild(categoryCard);
    });
}

// Open modal
openBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
    renderCategories();
    document.body.style.overflow = 'hidden';
});

// Close modal
closeBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
    searchInput.value = '';
    document.body.style.overflow = 'auto';
});

// Close on outside click
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.classList.add('hidden');
        searchInput.value = '';
        document.body.style.overflow = 'auto';
    }
});

// Search functionality
searchInput.addEventListener('input', (e) => {
    renderCategories(e.target.value);
});
