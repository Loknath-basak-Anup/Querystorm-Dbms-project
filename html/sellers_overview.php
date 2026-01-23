<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

function delivery_progress(string $status): int {
    $status = strtolower(trim($status));
    $map = [
        'pending' => 10,
        'processing' => 35,
        'shipped' => 65,
        'in_transit' => 65,
        'out_for_delivery' => 85,
        'delivered' => 100,
        'cancelled' => 0,
        'canceled' => 0
    ];
    return $map[$status] ?? 25;
}

// Fetch all sellers (based on role = seller)
$sellers = db_fetch_all(
    "SELECT sp.seller_id, sp.shop_name, sp.shop_description, sp.verified, sp.created_at,
            u.full_name, u.email, u.phone,
            s.logo_url, s.banner_url, s.address, s.city, s.country
     FROM seller_profiles sp
     INNER JOIN users u ON u.user_id = sp.seller_id
     INNER JOIN roles r ON r.role_id = u.role_id AND r.role_name = 'seller'
     LEFT JOIN shops s ON s.seller_id = sp.seller_id
     ORDER BY sp.created_at ASC",
    []
);

$sellerCards = [];

foreach ($sellers as $seller) {
    $sellerId = (int)($seller['seller_id'] ?? 0);
    if ($sellerId <= 0) {
        continue;
    }

    // Sales + orders stats
    $statsRow = db_fetch(
        "SELECT
            COALESCE(SUM(oi.price * oi.quantity), 0) AS total_sales,
            COUNT(DISTINCT oi.order_id) AS total_orders
         FROM order_items oi
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE p.seller_id = ?",
        [$sellerId]
    );

    // Order status breakdown for delivery performance
    $statusCounts = db_fetch(
        "SELECT
            SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
            SUM(CASE WHEN o.status IN ('shipped','in_transit','out_for_delivery','processing') THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN o.status IN ('cancelled','canceled') THEN 1 ELSE 0 END) AS cancelled_count
         FROM orders o
         INNER JOIN order_items oi ON oi.order_id = o.order_id
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE p.seller_id = ?",
        [$sellerId]
    );

    // Seller rating from seller_reviews table
    $ratingRow = db_fetch(
        "SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS rating_count
         FROM seller_reviews
         WHERE seller_id = ?",
        [$sellerId]
    );

    // Recent orders for this seller
    $recentOrders = db_fetch_all(
        "SELECT
            o.order_id,
            o.status,
            o.created_at,
            SUM(oi.price * oi.quantity) AS amount
         FROM orders o
         INNER JOIN order_items oi ON oi.order_id = o.order_id
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE p.seller_id = ?
         GROUP BY o.order_id, o.status, o.created_at
         ORDER BY o.created_at DESC
         LIMIT 3",
        [$sellerId]
    );

    if (!is_array($recentOrders)) {
        $recentOrders = [];
    }

    $sellerCards[] = [
        'info' => $seller,
        'stats' => [
            'total_sales'   => (float)($statsRow['total_sales'] ?? 0),
            'total_orders'  => (int)($statsRow['total_orders'] ?? 0),
            'delivered'     => (int)($statusCounts['delivered_count'] ?? 0),
            'in_progress'   => (int)($statusCounts['in_progress_count'] ?? 0),
            'pending'       => (int)($statusCounts['pending_count'] ?? 0),
            'cancelled'     => (int)($statusCounts['cancelled_count'] ?? 0),
        ],
        'rating' => [
            'avg'   => round((float)($ratingRow['avg_rating'] ?? 0), 1),
            'count' => (int)($ratingRow['rating_count'] ?? 0),
        ],
        'recent_orders' => $recentOrders,
    ];
}

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Sellers | QuickMart</title>
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
                        primary: '#8b5cf6',
                        secondary: '#ec4899',
                        accent: '#3b82f6',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-darkBg dark:text-white min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">QuickMart Sellers</h1>
                <p class="text-gray-600 dark:text-gray-400 max-w-2xl text-sm md:text-base">
                    Discover our verified shops, track their performance in real-time, and see how they deliver to customers.
                </p>
            </div>
            <a href="../index.php" class="inline-flex items-center gap-2 text-sm text-blue-500 hover:text-blue-400">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Home
            </a>
        </div>

        <?php if (empty($sellerCards)): ?>
            <div class="bg-white/80 dark:bg-black/40 border border-gray-200 dark:border-white/10 rounded-2xl p-8 text-center">
                <p class="text-gray-600 dark:text-gray-400">No sellers found yet. Once sellers join the marketplace, their shops will appear here.</p>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 gap-6">
                <?php foreach ($sellerCards as $card):
                    $info = $card['info'];
                    $stats = $card['stats'];
                    $rating = $card['rating'];
                    $recentOrders = $card['recent_orders'];
                    $avgRating = $rating['avg'];
                    $ratingCount = $rating['count'];
                    $ordersTotal = $stats['total_orders'];
                    $delivered = $stats['delivered'];
                    $deliverySuccess = $ordersTotal > 0 ? round(($delivered / max($ordersTotal, 1)) * 100) : 0;
                    $bannerUrl = $info['banner_url'] ?? '';
                    $logoUrl = $info['logo_url'] ?? '';
                    $locationParts = array_filter([
                        trim((string)($info['address'] ?? '')),
                        trim((string)($info['city'] ?? '')),
                        trim((string)($info['country'] ?? '')),
                    ]);
                    $locationText = $locationParts ? implode(', ', $locationParts) : 'Location not set';
                ?>
                <div class="bg-white/80 dark:bg-black/40 border border-gray-200 dark:border-white/10 rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-white/10">
                        <?php if (!empty($bannerUrl)): ?>
                            <img src="<?php echo htmlspecialchars($bannerUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Shop banner" class="w-full h-32 object-cover">
                        <?php else: ?>
                            <div class="w-full h-32 bg-gray-100 dark:bg-white/5 flex items-center justify-center text-xs text-gray-400">
                                No banner image
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 bg-gray-100 dark:bg-white/5 flex items-center justify-center text-[10px] text-gray-400">
                                <?php if (!empty($logoUrl)): ?>
                                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Shop logo" class="w-full h-full object-cover">
                                <?php else: ?>
                                    Logo
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h2 class="font-bold text-lg md:text-xl">
                                        <?php echo htmlspecialchars($info['shop_name'] ?? 'Shop', ENT_QUOTES, 'UTF-8'); ?>
                                    </h2>
                                    <?php if (!empty($info['verified'])): ?>
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <i class="fa-solid fa-badge-check"></i> Verified
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    Seller: <?php echo htmlspecialchars($info['full_name'] ?? 'Seller', ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    Address: <?php echo htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <?php if (!empty($info['shop_description'])): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                        <?php echo htmlspecialchars($info['shop_description'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center justify-end gap-1 text-yellow-400 text-sm mb-1">
                                <?php
                                $filled = (int)floor(max(0, min(5, $avgRating)));
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= $filled): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star text-gray-300 dark:text-gray-600"></i>
                                    <?php endif;
                                endfor;
                                ?>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                <?php echo number_format($avgRating, 1); ?> / 5.0
                                <?php if ($ratingCount > 0): ?>
                                    (<?php echo (int)$ratingCount; ?> reviews)
                                <?php else: ?>
                                    (no reviews yet)
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs md:text-sm">
                        <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                            <p class="text-gray-500 dark:text-gray-400 mb-1">Total Sales</p>
                            <p class="font-semibold">BDT <?php echo number_format($stats['total_sales'], 2); ?></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                            <p class="text-gray-500 dark:text-gray-400 mb-1">Orders</p>
                            <p class="font-semibold"><?php echo (int)$ordersTotal; ?></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                            <p class="text-gray-500 dark:text-gray-400 mb-1">Delivered</p>
                            <p class="font-semibold"><?php echo (int)$stats['delivered']; ?></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                            <p class="text-gray-500 dark:text-gray-400 mb-1">Delivery Success</p>
                            <p class="font-semibold"><?php echo (int)$deliverySuccess; ?>%</p>
                        </div>
                    </div>

                    <div class="mt-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Recent Orders & Delivery Progress</p>
                        <?php if (empty($recentOrders)): ?>
                            <p class="text-xs text-gray-400 italic">No orders yet for this seller.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($recentOrders as $order):
                                    $progress = delivery_progress((string)($order['status'] ?? 'pending'));
                                ?>
                                    <div class="border border-gray-200 dark:border-white/10 rounded-lg p-2">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-semibold">#<?php echo htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars(date('M d, Y', strtotime((string)$order['created_at'] ?? 'now'))); ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="text-gray-500 dark:text-gray-400 capitalize">
                                                Status: <?php echo htmlspecialchars(str_replace('_', ' ', strtolower((string)$order['status'] ?? 'pending'))); ?>
                                            </span>
                                            <span class="font-semibold text-emerald-500">
                                                BDT <?php echo number_format((float)($order['amount'] ?? 0), 2); ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 bg-gradient-to-r from-blue-500 to-emerald-400" style="width: <?php echo (int)$progress; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
