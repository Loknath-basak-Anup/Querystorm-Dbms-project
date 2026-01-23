<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/supabase_storage.php";

require_role('seller');
require_verified_seller();

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
    $newImageUrl = null;
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

            $existingImageRow = db_fetch(
                "SELECT image_id, image_url FROM product_images WHERE product_id = ? LIMIT 1",
                [$product_id]
            );

            if (!empty($_FILES['product_image']['name'] ?? '')) {
                $file = $_FILES['product_image'];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $newImageUrl = supabase_upload_image(
                        $file['tmp_name'],
                        $file['name'] ?? 'product',
                        $file['type'] ?? 'application/octet-stream',
                        'products'
                    );
                    if (!empty($existingImageRow['image_url'])) {
                        try { supabase_delete_image($existingImageRow['image_url']); } catch (Exception $e) {}
                    }
                }
            } elseif ($image_url !== '') {
                $newImageUrl = $image_url;
            }

            if ($newImageUrl !== null) {
                if ($existingImageRow) {
                    db_execute(
                        "UPDATE product_images SET image_url = ? WHERE product_id = ?",
                        [$newImageUrl, $product_id]
                    );
                } else {
                    db_execute(
                        "INSERT INTO product_images (product_id, image_url, created_at) VALUES (?, ?, NOW())",
                        [$product_id, $newImageUrl]
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
            if ($pdo->inTransaction()) $pdo->rollBack();
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

    <style>
        /* Extra polish on top of Tailwind */
        .glass {
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .ring-aurora:focus {
            box-shadow: 0 0 0 4px rgba(45, 212, 191, .18);
            border-color: rgba(45, 212, 191, .55) !important;
        }
        .field {
            background: rgba(2, 6, 23, 0.40);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .field:focus {
            outline: none;
            border-color: rgba(167, 139, 250, .65);
            box-shadow: 0 0 0 4px rgba(167, 139, 250, .18);
        }
        .soft-shadow {
            box-shadow: 0 20px 60px rgba(0,0,0,.45);
        }
    </style>
</head>

<body class="min-h-screen text-slate-100 bg-slate-950">
    <!-- Aurora background -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-slate-950"></div>
        <div class="absolute -top-32 left-0 w-[680px] h-[420px] rounded-full blur-3xl opacity-30"
             style="background: radial-gradient(circle at 30% 30%, rgba(45,212,191,.55), transparent 60%);"></div>
        <div class="absolute -top-20 right-0 w-[680px] h-[420px] rounded-full blur-3xl opacity-25"
             style="background: radial-gradient(circle at 60% 30%, rgba(167,139,250,.60), transparent 60%);"></div>
        <div class="absolute bottom-0 left-1/3 w-[820px] h-[520px] rounded-full blur-3xl opacity-20"
             style="background: radial-gradient(circle at 50% 60%, rgba(56,189,248,.55), transparent 60%);"></div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Top bar -->
        <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-2xl grid place-items-center text-slate-950 font-black soft-shadow"
                     style="background: linear-gradient(135deg, rgba(45,212,191,1), rgba(167,139,250,1));">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">Edit Product</h1>
                    <p class="text-slate-300 text-sm mt-1">Update details, stock, and image with a premium dashboard feel.</p>
                </div>
            </div>

            <div class="flex gap-2 flex-wrap">
                <a href="seller_dashboard.php"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl glass hover:border-teal-400/40 transition">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Main card -->
        <div class="glass rounded-3xl p-6 md:p-8 soft-shadow">
            <?php if (isset($error)): ?>
                <div class="mb-6 rounded-2xl border border-rose-400/40 bg-rose-500/10 px-4 py-3 text-rose-100">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-triangle-exclamation mt-0.5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="mb-6 rounded-2xl border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-emerald-100">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-circle-check mt-0.5"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-7" enctype="multipart/form-data">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">
                        Product Name <span class="text-teal-300">*</span>
                    </label>
                    <input type="text" name="name" required
                           value="<?php echo htmlspecialchars($product['name']); ?>"
                           class="w-full px-4 py-3 rounded-2xl field placeholder:text-slate-400">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-3 rounded-2xl field placeholder:text-slate-400"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <!-- Price + Category/Subcategory -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">
                            Price (BDT) <span class="text-teal-300">*</span>
                        </label>
                        <input type="number" name="price" step="0.01" min="0" required
                               value="<?php echo $product['price']; ?>"
                               class="w-full px-4 py-3 rounded-2xl field placeholder:text-slate-400">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">
                            Category / Subcategory <span class="text-teal-300">*</span>
                        </label>
                        <input type="hidden" name="category_id" id="category_id_hidden"
                               value="<?php echo (int)$product['category_id']; ?>">
                        <select name="subcategory_id" id="subcategory_select" required
                                class="w-full px-4 py-3 rounded-2xl field">
                            <?php foreach ($subcategories as $sub): ?>
                                <?php $cat_name = $category_map[$sub['category_id']] ?? 'Category'; ?>
                                <option value="<?php echo $sub['subcategory_id']; ?>"
                                        data-category="<?php echo $sub['category_id']; ?>"
                                    <?php echo $sub['subcategory_id'] == $product['subcategory_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat_name . ' / ' . $sub['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-2">
                            Tip: selecting a subcategory automatically syncs the category id.
                        </p>
                    </div>
                </div>

                <!-- Stock + SKU -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">Stock Quantity</label>
                        <input type="number" name="stock_qty" min="0"
                               value="<?php echo $product['stock_qty'] ?? 0; ?>"
                               class="w-full px-4 py-3 rounded-2xl field placeholder:text-slate-400">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-200 mb-2">SKU</label>
                        <input type="text" name="sku"
                               value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>"
                               class="w-full px-4 py-3 rounded-2xl field placeholder:text-slate-400">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold text-slate-200 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-3 rounded-2xl field">
                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <!-- Images -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border border-slate-700/60 bg-slate-900/30 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-link text-teal-300"></i>
                            <h3 class="font-bold text-slate-100">Image URL (optional)</h3>
                        </div>
                        <input type="url" name="image_url"
                               value="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                               class="w-full px-4 py-3 rounded-2xl field placeholder:text-slate-400">
                        <p class="text-xs text-slate-400 mt-2">
                            Leave empty if you upload a new image from your device.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-700/60 bg-slate-900/30 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-cloud-arrow-up text-purple-300"></i>
                            <h3 class="font-bold text-slate-100">Upload New Image</h3>
                        </div>

                        <label for="productImageInput"
                               class="block rounded-2xl border border-dashed border-slate-600/70 bg-slate-950/30 hover:border-teal-400/50 transition p-4 cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-2xl grid place-items-center"
                                     style="background: linear-gradient(135deg, rgba(56,189,248,.25), rgba(167,139,250,.25)); border:1px solid rgba(148,163,184,.18);">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                                <div class="text-sm text-slate-200">
                                    <div class="font-semibold">Click to choose an image</div>
                                    <div class="text-xs text-slate-400">PNG/JPG recommended • preview updates instantly</div>
                                </div>
                            </div>
                        </label>

                        <input type="file" name="product_image" id="productImageInput" accept="image/*" class="hidden">

                        <div class="mt-4 flex items-center gap-4">
                            <img id="productImagePreview"
                                 src="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                                 alt="Product preview"
                                 class="h-24 w-24 object-cover rounded-2xl border border-slate-700/70 <?php echo !empty($product['image_url']) ? '' : 'hidden'; ?>">
                            <div>
                                <p id="productImagePlaceholder"
                                   class="text-xs text-slate-400 <?php echo !empty($product['image_url']) ? 'hidden' : ''; ?>">
                                    No image selected.
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Upload overrides URL if both are provided.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col md:flex-row gap-4 pt-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl font-extrabold text-slate-950 transition"
                            style="background: linear-gradient(135deg, rgba(45,212,191,1), rgba(167,139,250,1));">
                        <i class="fas fa-save"></i> Save Changes
                    </button>

                    <a href="delete_product.php?product_id=<?php echo $product_id; ?>"
                       onclick="return confirm('Are you sure you want to delete this product?')"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl font-extrabold bg-rose-600 hover:bg-rose-700 transition text-white">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>

                <p class="text-xs text-slate-500">
                    By saving, you confirm the product details are accurate and comply with marketplace rules.
                </p>
            </form>
        </div>
    </div>

    <script>
        const subcategorySelect = document.getElementById('subcategory_select');
        const categoryHidden = document.getElementById('category_id_hidden');
        const productImageInput = document.getElementById('productImageInput');
        const productImagePreview = document.getElementById('productImagePreview');
        const productImagePlaceholder = document.getElementById('productImagePlaceholder');

        function syncCategoryFromSubcategory() {
            if (!subcategorySelect || !categoryHidden) return;
            const selected = subcategorySelect.options[subcategorySelect.selectedIndex];
            categoryHidden.value = selected ? (selected.getAttribute('data-category') || '') : '';
        }

        if (subcategorySelect) {
            subcategorySelect.addEventListener('change', syncCategoryFromSubcategory);
            syncCategoryFromSubcategory();
        }

        if (productImageInput) {
            productImageInput.addEventListener('change', () => {
                const file = productImageInput.files && productImageInput.files[0];
                if (!productImagePreview || !productImagePlaceholder) return;

                if (file) {
                    const url = URL.createObjectURL(file);
                    productImagePreview.src = url;
                    productImagePreview.classList.remove('hidden');
                    productImagePlaceholder.classList.add('hidden');
                    productImagePreview.onload = () => URL.revokeObjectURL(url);
                } else {
                    productImagePreview.classList.add('hidden');
                    productImagePlaceholder.classList.remove('hidden');
                }
            });
        }
    </script>
</body>
</html>
