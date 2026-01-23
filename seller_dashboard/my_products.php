<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

require_role('seller');

$seller_id = get_user_id();
$isVerified = is_seller_verified($seller_id);
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$searchTerm = trim($_GET['q'] ?? '');
$searchSql = '';
$searchParams = [$seller_id];
if ($searchTerm !== '') {
    $searchSql = " AND (p.name LIKE ? OR c.name LIKE ? OR sc.name LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $searchParams[] = $like;
    $searchParams[] = $like;
    $searchParams[] = $like;
}

$statsRow = db_fetch("
    SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) AS active_products,
        SUM(CASE WHEN COALESCE(i.stock_qty, 0) <= 5 THEN 1 ELSE 0 END) AS low_stock
    FROM products p
    LEFT JOIN inventory i ON p.product_id = i.product_id
    WHERE p.seller_id = ?
", [$seller_id]);

$countRow = db_fetch(
    "SELECT COUNT(*) AS total_products
     FROM products p
     INNER JOIN categories c ON p.category_id = c.category_id
     INNER JOIN subcategories sc ON p.subcategory_id = sc.subcategory_id
     WHERE p.seller_id = ?$searchSql",
    $searchParams
);
$totalProducts = (int)($countRow['total_products'] ?? 0);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));

// Fetch seller's products
$products = db_fetch_all("
    SELECT 
        p.product_id,
        p.name,
        p.price,
        p.status,
        c.name as category_name,
        sc.name as subcategory_name,
        i.stock_qty,
        (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image_url
    FROM products p
    INNER JOIN categories c ON p.category_id = c.category_id
    INNER JOIN subcategories sc ON p.subcategory_id = sc.subcategory_id
    LEFT JOIN inventory i ON p.product_id = i.product_id
    WHERE p.seller_id = ?$searchSql
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
", $searchParams);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products | QuickMart Seller</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <a href="seller_dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold mt-4">My Products</h1>
                <p class="text-gray-400 mt-1">Track stock, update listings, and ship faster.</p>
            </div>
            <?php if ($isVerified): ?>
                <a href="add_product.php" 
                   class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold transition shadow-lg shadow-blue-600/20">
                    <i class="fas fa-plus mr-2"></i>Add New Product
                </a>
            <?php else: ?>
                <a href="verify_seller.php" 
                   class="bg-blue-600 px-6 py-3 rounded-lg font-semibold transition shadow-lg shadow-blue-600/20"
                   style="opacity:0.6; cursor:not-allowed;" onclick="return false;">
                    <i class="fas fa-lock mr-2"></i>Add New Product
                </a>
            <?php endif; ?>
        </div>

        <?php if (!$isVerified): ?>
            <div class="bg-amber-500/10 border border-amber-400/50 rounded-xl p-4 mb-6">
                <i class="fas fa-hourglass-half mr-2"></i>
                Your seller account is pending verification. Product actions are locked until approval.
                <a href="verify_seller.php" class="text-amber-300 underline ml-2">Review verification</a>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-800/70 border border-gray-700 rounded-xl p-4">
                <div class="text-gray-400 text-sm">Total Products</div>
                <div class="text-2xl font-bold"><?php echo (int)($statsRow['total_products'] ?? 0); ?></div>
            </div>
            <div class="bg-gray-800/70 border border-gray-700 rounded-xl p-4">
                <div class="text-gray-400 text-sm">Active Listings</div>
                <div class="text-2xl font-bold text-green-400"><?php echo (int)($statsRow['active_products'] ?? 0); ?></div>
            </div>
            <div class="bg-gray-800/70 border border-gray-700 rounded-xl p-4">
                <div class="text-gray-400 text-sm">Low Stock (<=5)</div>
                <div class="text-2xl font-bold text-amber-400"><?php echo (int)($statsRow['low_stock'] ?? 0); ?></div>
            </div>
        </div>

        <div class="bg-gray-800/60 border border-gray-700 rounded-xl p-4 mb-6">
            <form method="get" class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex-1">
                    <label for="productSearch" class="sr-only">Search products</label>
                    <input
                        id="productSearch"
                        type="text"
                        name="q"
                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                        placeholder="Search by product, category, or subcategory"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-5 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-magnifying-glass mr-2"></i>Search
                </button>
                <?php if ($searchTerm !== ''): ?>
                    <a href="my_products.php" class="px-5 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 transition text-center">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
            <div class="text-gray-400 text-sm mt-3">
                Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                <?php if ($searchTerm !== ''): ?>
                    <span class="text-blue-300">for "<?php echo htmlspecialchars($searchTerm); ?>"</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-500 bg-opacity-20 border border-green-500 rounded p-4 mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php 
                    $msg = $_GET['success'];
                    echo $msg === 'product_added' ? 'Product added successfully!' : '';
                    echo $msg === 'product_deleted' ? 'Product deleted successfully!' : '';
                    echo $msg === 'product_updated' ? 'Product updated successfully!' : '';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-gray-800/80 border border-gray-700 rounded-2xl overflow-hidden shadow-xl transition hover:-translate-y-1 hover:shadow-2xl">
                    <div class="relative h-48 bg-gray-700">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-image text-5xl text-gray-600"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-3 right-3">
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                <?php echo $product['status'] === 'active' ? 'bg-green-600' : 'bg-gray-600'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <?php if ((int)($product['stock_qty'] ?? 0) <= 5): ?>
                                <span class="text-xs px-2 py-1 rounded-full bg-amber-500/20 text-amber-300">Low Stock</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-400 text-sm mb-2">
                            <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($product['category_name']); ?> / <?php echo htmlspecialchars($product['subcategory_name'] ?? ''); ?>
                        </p>
                        <p class="text-2xl font-bold text-blue-400 mb-2">
                            <?php echo number_format($product['price'], 2); ?> BDT
                        </p>
                        <p class="text-sm text-gray-400 mb-4">
                            <i class="fas fa-boxes mr-1"></i>Stock: <?php echo $product['stock_qty'] ?? 0; ?>
                        </p>
                        
                        <div class="flex gap-2">
                            <a href="edit_product.php?product_id=<?php echo $product['product_id']; ?>&return=my_products" 
                               class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-center transition">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <a href="delete_product.php?product_id=<?php echo $product['product_id']; ?>&return=my_products" 
                               onclick="return confirm('Are you sure you want to delete this product?')"
                               class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($products)): ?>
                <div class="col-span-full text-center py-16">
                    <i class="fas fa-box-open text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold mb-2">No Products Yet</h3>
                    <p class="text-gray-400 mb-6">Start selling by adding your first product</p>
                    <a href="add_product.php" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Add Your First Product
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalProducts > 0): ?>
            <?php $queryBase = $searchTerm !== '' ? '&q=' . urlencode($searchTerm) : ''; ?>
            <div class="mt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-gray-400">
                    Showing <?php echo min($offset + count($products), $totalProducts); ?> of <?php echo $totalProducts; ?> products
                </div>
                <div class="flex gap-3">
                    <a href="?page=<?php echo max(1, $page - 1); ?><?php echo $queryBase; ?>" 
                       class="px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                        <i class="fas fa-arrow-left mr-1"></i>Previous
                    </a>
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?><?php echo $queryBase; ?>" 
                       class="px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">
                        Next<i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
