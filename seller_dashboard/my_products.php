<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

require_role('seller');

$seller_id = get_user_id();
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countRow = db_fetch(
    "SELECT COUNT(*) AS total_products FROM products WHERE seller_id = ?",
    [$seller_id]
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
    WHERE p.seller_id = ?
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
", [$seller_id]);
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
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="seller_dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold mt-4">My Products</h1>
            </div>
            <a href="add_product.php" 
               class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Add New Product
            </a>
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
                <div class="bg-gray-800 rounded-lg overflow-hidden shadow-xl">
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
                        
                        <div class="absolute top-2 right-2">
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                <?php echo $product['status'] === 'active' ? 'bg-green-600' : 'bg-gray-600'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
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
                               class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-center transition">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <a href="delete_product.php?product_id=<?php echo $product['product_id']; ?>&return=my_products" 
                               onclick="return confirm('Are you sure you want to delete this product?')"
                               class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition">
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
            <div class="mt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-gray-400">
                    Showing <?php echo min($offset + count($products), $totalProducts); ?> of <?php echo $totalProducts; ?> products
                </div>
                <div class="flex gap-3">
                    <a href="?page=<?php echo max(1, $page - 1); ?>" 
                       class="px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                        <i class="fas fa-arrow-left mr-1"></i>Previous
                    </a>
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>" 
                       class="px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">
                        Next<i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
