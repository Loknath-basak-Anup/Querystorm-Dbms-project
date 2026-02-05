<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/supabase_storage.php";

$is_ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
if ($is_ajax) {
    ob_start();
    ini_set('display_errors', '0');
    ini_set('html_errors', '0');
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $error['message']
            ]);
        }
    });
}

if ($is_ajax) {
    if (!is_logged_in()) {
        ob_clean();
        json_out(['success' => false, 'message' => 'Please log in again.'], 401);
    }
    if (get_user_role() !== 'seller') {
        ob_clean();
        json_out(['success' => false, 'message' => 'Access denied. Seller role required.'], 403);
    }
} else {
    require_role('seller');
}

$seller_id = get_user_id();
if (!is_seller_verified($seller_id)) {
    if ($is_ajax) {
        if (ob_get_length()) ob_clean();
        json_out(['success' => false, 'message' => 'Seller verification pending.'], 403);
    }
    $base = defined('BASE_URL') ? BASE_URL : '/QuickMart';
    header("Location: {$base}/seller_dashboard/verify_seller.php");
    exit;
}

// Fetch categories and subcategories for dropdowns
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");
$subcategories = db_fetch_all("SELECT subcategory_id, category_id, name FROM subcategories ORDER BY name ASC");
$category_map = [];
foreach ($categories as $cat) {
    $category_map[$cat['category_id']] = $cat['name'];
}

// Handle category/subcategory creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'add_category') {
            $new_category = trim($_POST['new_category'] ?? '');
            if ($new_category === '') {
                throw new Exception('Category name required');
            }
            $new_category_id = (int)db_execute("INSERT INTO categories (name) VALUES (?)", [$new_category]);
            db_query("INSERT INTO subcategories (category_id, name) VALUES (?, ?)", [$new_category_id, 'General']);
            $category_notice = "Category added. Default subcategory created.";
        } elseif ($action === 'add_subcategory') {
            $parent_category_id = (int)($_POST['parent_category_id'] ?? 0);
            $new_subcategory = trim($_POST['new_subcategory'] ?? '');
            if ($parent_category_id <= 0 || $new_subcategory === '') {
                throw new Exception('Category and subcategory name required');
            }
            $existing = db_fetch(
                "SELECT subcategory_id FROM subcategories WHERE category_id = ? AND name = ? LIMIT 1",
                [$parent_category_id, $new_subcategory]
            );
            if ($existing) {
                throw new Exception('Subcategory already exists for this category');
            }
            db_query("INSERT INTO subcategories (category_id, name) VALUES (?, ?)", [$parent_category_id, $new_subcategory]);
            $category_notice = "Subcategory added.";
        }
    } catch (Throwable $e) {
        $category_error = $e->getMessage();
    }
    $categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");
    $subcategories = db_fetch_all("SELECT subcategory_id, category_id, name FROM subcategories ORDER BY name ASC");
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
    
    if (empty($name) || $price <= 0 || $category_id <= 0 || $subcategory_id <= 0) {
        $error = "Please fill all required fields";
        if ($is_ajax) {
            json_out(['success' => false, 'message' => $error], 400);
        }
    } else {
        try {
            $valid_subcategory = db_fetch(
                "SELECT subcategory_id FROM subcategories WHERE subcategory_id = ? AND category_id = ?",
                [$subcategory_id, $category_id]
            );
            if (!$valid_subcategory) {
                throw new Exception("Invalid subcategory for selected category.");
            }
            $uploaded_image = '';
            if (!empty($_FILES['product_image']) && is_array($_FILES['product_image'])) {
                $file = $_FILES['product_image'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $file['tmp_name'];
                    $image_info = @getimagesize($tmp_name);
                    if ($image_info === false) {
                        throw new Exception("Uploaded file is not a valid image.");
                    }
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($extension, $allowed, true)) {
                        throw new Exception("Unsupported image format.");
                    }
                    if (!supabase_is_configured()) {
                        throw new Exception("Supabase storage is not configured.");
                    }
                    $uploaded_image = supabase_upload_image(
                        $tmp_name,
                        $file['name'],
                        $image_info['mime'] ?? 'application/octet-stream',
                        'products/' . (int)$seller_id
                    );
                } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception("Image upload failed.");
                }
            }
            if ($uploaded_image !== '') {
                $image_url = $uploaded_image;
            }

            $pdo->beginTransaction();
            
            // Insert product
            $product_id = db_execute(
                "INSERT INTO products (seller_id, category_id, subcategory_id, name, description, price, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
                [$seller_id, $category_id, $subcategory_id, $name, $description, $price]
            );
            
            // Insert inventory
            db_execute(
                "INSERT INTO inventory (product_id, stock_qty, sku, updated_at) 
                 VALUES (?, ?, ?, NOW())",
                [$product_id, $stock_qty, $sku ?: null]
            );
            
            // Insert product image if provided
            if (!empty($image_url)) {
                db_execute(
                    "INSERT INTO product_images (product_id, image_url, created_at) 
                     VALUES (?, ?, NOW())",
                    [$product_id, $image_url]
                );
            }
            
            $pdo->commit();
            if ($is_ajax) {
                json_out(['success' => true, 'product_id' => $product_id, 'image_url' => $image_url]);
            } else {
                header("Location: seller_dashboard.php?success=product_added");
                exit;
            }
        } catch (Throwable $e) {
            if ($is_ajax) {
                ob_clean();
            }
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to add product: " . $e->getMessage();
            if ($is_ajax) {
                json_out(['success' => false, 'message' => $error], 500);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | QuickMart Seller</title>
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
            <h1 class="text-3xl font-bold mb-6">Add New Product</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 rounded p-4 mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($category_notice)): ?>
                <div class="bg-green-500 bg-opacity-20 border border-green-500 rounded p-4 mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($category_notice); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($category_error)): ?>
                <div class="bg-red-500 bg-opacity-20 border border-red-500 rounded p-4 mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($category_error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-900 bg-opacity-60 border border-gray-700 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold mb-3">Manage Categories</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="add_category">
                        <label class="block text-sm font-medium">New Category</label>
                        <input type="text" name="new_category" required
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="e.g., Wearables">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded font-semibold transition">
                            <i class="fas fa-plus mr-2"></i>Add Category
                        </button>
                    </form>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="add_subcategory">
                        <label class="block text-sm font-medium">New Subcategory</label>
                        <select name="parent_category_id" required
                                class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_subcategory" required
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="e.g., Smartwatches">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded font-semibold transition">
                            <i class="fas fa-layer-group mr-2"></i>Add Subcategory
                        </button>
                    </form>
                </div>
            </div>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Product Name *</label>
                    <input type="text" name="name" required 
                           class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="Enter product name">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                              placeholder="Enter product description"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Price (BDT) *</label>
                        <input type="number" name="price" step="0.01" min="0" required
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Category / Subcategory *</label>
                        <input type="hidden" name="category_id" id="category_id_hidden" value="">
                        <select name="subcategory_id" id="subcategory_select" required
                                class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select subcategory</option>
                            <?php foreach ($subcategories as $sub): ?>
                                <?php $cat_name = $category_map[$sub['category_id']] ?? 'Category'; ?>
                                <option value="<?php echo $sub['subcategory_id']; ?>" data-category="<?php echo $sub['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat_name . ' / ' . $sub['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Stock Quantity</label>
                        <input type="number" name="stock_qty" min="0" value="0"
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="0">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">SKU (optional)</label>
                        <input type="text" name="sku"
                               class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="Product SKU">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Image URL</label>
                    <input type="url" name="image_url"
                           class="w-full px-4 py-3 bg-gray-700 rounded focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="https://example.com/image.jpg">
                    <p class="text-sm text-gray-400 mt-1">Enter a direct image URL</p>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Add Product
                    </button>
                    <a href="seller_dashboard.php" 
                       class="px-6 py-3 bg-gray-700 hover:bg-gray-600 rounded font-semibold transition text-center">
                        Cancel
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
