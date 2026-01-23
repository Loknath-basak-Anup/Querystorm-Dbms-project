<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

require_role('seller');
require_verified_seller();

$seller_id = get_user_id();
$product_id = intval($_GET['product_id'] ?? 0);
$return = $_GET['return'] ?? '';

// Verify the product belongs to this seller
$product = db_fetch(
    "SELECT product_id FROM products WHERE product_id = ? AND seller_id = ?",
    [$product_id, $seller_id]
);

if (!$product) {
    die("Product not found or access denied");
}

try {
    // Delete product (cascade will handle related records)
    db_execute(
        "DELETE FROM products WHERE product_id = ? AND seller_id = ?",
        [$product_id, $seller_id]
    );
    
    if ($return === 'my_products') {
        header("Location: my_products.php?success=product_deleted");
    } else {
        header("Location: seller_dashboard.php?success=product_deleted");
    }
    exit;
} catch (Exception $e) {
    die("Failed to delete product: " . $e->getMessage());
}
