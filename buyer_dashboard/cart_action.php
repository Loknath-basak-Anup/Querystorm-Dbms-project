<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

require_role('buyer');

$buyer_id = get_user_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Get or create cart for buyer
function get_or_create_cart($buyer_id) {
    global $pdo;
    
    $cart = db_fetch("SELECT cart_id FROM carts WHERE buyer_id = ?", [$buyer_id]);
    
    if (!$cart) {
        $cart_id = db_execute(
            "INSERT INTO carts (buyer_id, created_at) VALUES (?, NOW())",
            [$buyer_id]
        );
        return $cart_id;
    }
    
    return $cart['cart_id'];
}

try {
    switch ($action) {
        case 'add':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($product_id <= 0 || $quantity <= 0) {
                if ($is_ajax) {
                    json_out(['success' => false, 'message' => 'Invalid input'], 400);
                } else {
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../html/products_page.php') . "?err=invalid");
                    exit;
                }
            }
            
            // Check if product exists and has stock
            $product = db_fetch(
                "SELECT p.product_id, p.name, p.price, i.stock_qty 
                 FROM products p 
                 LEFT JOIN inventory i ON p.product_id = i.product_id 
                 WHERE p.product_id = ? AND p.status = 'active'",
                [$product_id]
            );
            
            if (!$product) {
                if ($is_ajax) {
                    json_out(['success' => false, 'message' => 'Product not found'], 404);
                } else {
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../html/products_page.php') . "?err=notfound");
                    exit;
                }
            }
            
            if ($product['stock_qty'] < $quantity) {
                if ($is_ajax) {
                    json_out(['success' => false, 'message' => 'Insufficient stock'], 400);
                } else {
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../html/products_page.php') . "?err=stock");
                    exit;
                }
            }
            
            $cart_id = get_or_create_cart($buyer_id);
            
            // Check if item already in cart
            $existing = db_fetch(
                "SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?",
                [$cart_id, $product_id]
            );
            
            if ($existing) {
                // Update quantity
                $new_quantity = $existing['quantity'] + $quantity;
                if ($new_quantity > $product['stock_qty']) {
                    if ($is_ajax) {
                        json_out(['success' => false, 'message' => 'Exceeds available stock'], 400);
                    } else {
                        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../html/products_page.php') . "?err=exceeds");
                        exit;
                    }
                }
                
                db_execute(
                    "UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?",
                    [$new_quantity, $cart_id, $product_id]
                );
            } else {
                // Insert new item
                db_execute(
                    "INSERT INTO cart_items (cart_id, product_id, quantity, created_at) 
                     VALUES (?, ?, ?, NOW())",
                    [$cart_id, $product_id, $quantity]
                );
            }
            
            if ($is_ajax) {
                json_out(['success' => true, 'message' => 'Added to cart']);
            } else {
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../html/products_page.php') . "?msg=added");
                exit;
            }
            break;
            
        case 'update':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            $cart_id = get_or_create_cart($buyer_id);
            
            if ($quantity <= 0) {
                // Remove item if quantity is 0
                db_execute(
                    "DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?",
                    [$cart_id, $product_id]
                );
            } else {
                db_execute(
                    "UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?",
                    [$quantity, $cart_id, $product_id]
                );
            }
            
            if ($is_ajax) {
                json_out(['success' => true, 'message' => 'Cart updated']);
            } else {
                header("Location: cart.php");
                exit;
            }
            break;
            
        case 'remove':
            $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
            $cart_id = get_or_create_cart($buyer_id);
            
            db_execute(
                "DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?",
                [$cart_id, $product_id]
            );
            
            if ($is_ajax) {
                json_out(['success' => true, 'message' => 'Item removed']);
            } else {
                header("Location: cart.php?msg=removed");
                exit;
            }
            break;
            
        case 'get':
            $cart_id = get_or_create_cart($buyer_id);
            
            $items = db_fetch_all("
                SELECT 
                    ci.cart_item_id,
                    ci.product_id,
                    ci.quantity,
                    p.name,
                    p.price,
                    i.stock_qty,
                    (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image_url
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.product_id
                LEFT JOIN inventory i ON p.product_id = i.product_id
                WHERE ci.cart_id = ?
            ", [$cart_id]);
            
            $total = 0;
            foreach ($items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            json_out([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'count' => count($items)
            ]);
            break;
            
        case 'clear':
            $cart_id = get_or_create_cart($buyer_id);
            db_execute("DELETE FROM cart_items WHERE cart_id = ?", [$cart_id]);
            
            if ($is_ajax) {
                json_out(['success' => true, 'message' => 'Cart cleared']);
            } else {
                header("Location: cart.php?msg=cleared");
                exit;
            }
            break;
            
        default:
            if ($is_ajax) {
                json_out(['success' => false, 'message' => 'Invalid action'], 400);
            } else {
                header("Location: cart.php");
                exit;
            }
    }
} catch (Exception $e) {
    if ($is_ajax) {
        json_out(['success' => false, 'message' => $e->getMessage()], 500);
    } else {
        header("Location: cart.php?err=" . urlencode($e->getMessage()));
        exit;
    }
}
