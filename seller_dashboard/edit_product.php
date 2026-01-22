<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

require_role('seller');

$seller_id = get_user_id();
$product_id = intval($_GET['product_id'] ?? 0);
$return = $_GET['return'] ?? '';

// Fetch product (ensure it belongs to this seller)
$product = db_fetch(
    "SELECT p.*, i.stock_qty, i.sku, 
            (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image_url
     FROM products p
     LEFT JOIN inventory i ON p.product_id = i.product_id
     WHERE p.product_id = ? AND p.seller_id = ?",
    [$product_id, $seller_id]
);

if (!$product) {
    die("Product not found or access denied");
}

// Fetch categories and subcategories for dropdowns
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");
$subcategories = db_fetch_all("SELECT subcategory_id, category_id, name FROM subcategories ORDER BY name ASC");
$category_map = [];
foreach ($categories as $cat) {
    $category_map[$cat['category_id']] = $cat['name'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);
    $stock_qty = intval($_POST['stock_qty'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name) || $price <= 0 || $category_id <= 0 || $subcategory_id <= 0) {
        $error = "Please fill all required fields";
    } else {
        try {
            $pdo->beginTransaction();
            $valid_subcategory = db_fetch(
                "SELECT subcategory_id FROM subcategories WHERE subcategory_id = ? AND category_id = ?",
                [$subcategory_id, $category_id]
            );
            if (!$valid_subcategory) {
                throw new Exception("Invalid subcategory for selected category.");
            }
            
            // Update product
            db_execute(
                "UPDATE products 
                 SET name = ?, description = ?, price = ?, category_id = ?, subcategory_id = ?, status = ?
                 WHERE product_id = ? AND seller_id = ?",
                [$name, $description, $price, $category_id, $subcategory_id, $status, $product_id, $seller_id]
            );
            
            // Update inventory
            db_execute(
                "UPDATE inventory 
                 SET stock_qty = ?, sku = ?, updated_at = NOW()
                 WHERE product_id = ?",
                [$stock_qty, $sku ?: null, $product_id]
            );
            
            // Update/insert product image
            if (!empty($image_url)) {
                $existing_image = db_fetch(
                    "SELECT image_id FROM product_images WHERE product_id = ? LIMIT 1",
                    [$product_id]
                );
                
                if ($existing_image) {
                    db_execute(
                        "UPDATE product_images SET image_url = ? WHERE product_id = ?",
                        [$image_url, $product_id]
                    );
                } else {
                    db_execute(
                        "INSERT INTO product_images (product_id, image_url, created_at) 
                         VALUES (?, ?, NOW())",
                        [$product_id, $image_url]
                    );
                }
            }
            
            $pdo->commit();
            if ($return === 'my_products') {
                header("Location: my_products.php?success=product_updated");
                exit;
            }
            if ($return === 'dashboard') {
                header("Location: seller_dashboard.php?success=product_updated");
                exit;
            }
            $success = "Product updated successfully";
            
            // Refresh product data
            $product = db_fetch(
                "SELECT p.*, i.stock_qty, i.sku, 
                        (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image_url
                 FROM products p
                 LEFT JOIN inventory i ON p.product_id = i.product_id
                 WHERE p.product_id = ? AND p.seller_id = ?",
                [$product_id, $seller_id]
            );
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update product: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | QuickMart Seller</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="mb-6">
            <a href="seller_dashboard.php" class="text-blue-400 hover:text-blue-300">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <div class="bg-gray-800 rounded-lg p-8 shadow-xl">
            <h1 class="text-3xl font-bold mb-6">Edit Product</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 rounded p-4 mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-500 rounded p-4 mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Product Name *</label>
                    <input type="text" name="name" required 
                           value="<?php echo htmlspecialchars($product['name']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Price (BDT) *</label>
                        <input type="number" name="price" step="0.01" min="0" required
                               value="<?php echo $product['price']; ?>"
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Category / Subcategory *</label>
                        <input type="hidden" name="category_id" id="category_id_hidden" value="<?php echo (int)$product['category_id']; ?>">
                        <select name="subcategory_id" id="subcategory_select" required
                                class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach ($subcategories as $sub): ?>
                                <?php $cat_name = $category_map[$sub['category_id']] ?? 'Category'; ?>
                                <option value="<?php echo $sub['subcategory_id']; ?>" data-category="<?php echo $sub['category_id']; ?>"
                                        <?php echo $sub['subcategory_id'] == $product['subcategory_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat_name . ' / ' . $sub['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Stock Quantity</label>
                        <input type="number" name="stock_qty" min="0" 
                               value="<?php echo $product['stock_qty'] ?? 0; ?>"
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">SKU</label>
                        <input type="text" name="sku"
                               value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>"
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Image URL</label>
                    <input type="url" name="image_url"
                           value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                           class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="Product" class="mt-2 h-32 object-cover rounded">
                    <?php endif; ?>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded font-semibold transition">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="delete_product.php?product_id=<?php echo $product_id; ?>" 
                       onclick="return confirm('Are you sure you want to delete this product?')"
                       class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded font-semibold transition text-center">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script>
        const subcategorySelect = document.getElementById('subcategory_select');
        const categoryHidden = document.getElementById('category_id_hidden');

        function syncCategoryFromSubcategory() {
            if (!subcategorySelect || !categoryHidden) return;
            const selected = subcategorySelect.options[subcategorySelect.selectedIndex];
            categoryHidden.value = selected ? (selected.getAttribute('data-category') || '') : '';
        }

        if (subcategorySelect) {
            subcategorySelect.addEventListener('change', syncCategoryFromSubcategory);
            syncCategoryFromSubcategory();
        }
    </script>
</body>
</html>
