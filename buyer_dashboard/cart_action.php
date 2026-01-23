<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/coupons.php";
require_once __DIR__ . "/../includes/notifications.php";

require_role('buyer');
ensure_coupon_tables();
ensure_notifications_table();

$buyer_id = get_user_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Ensure wallet_transactions table exists
try {
    db_query(
        "CREATE TABLE IF NOT EXISTS wallet_transactions (
            txn_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            txn_type VARCHAR(30) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            note VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (txn_id),
            KEY idx_wallet_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
} catch (Exception $e) {
    error_log('Failed to ensure wallet_transactions table exists: ' . $e->getMessage());
}

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

function get_wallet_balance(int $user_id): float {
    $row = db_fetch(
        "SELECT COALESCE(SUM(
            CASE
                WHEN txn_type IN ('credit','deposit','topup','refund') THEN amount
                WHEN txn_type IN ('debit','purchase','withdraw') THEN -amount
                ELSE amount
            END
        ), 0) AS balance
         FROM wallet_transactions
         WHERE user_id = ?",
        [$user_id]
    );
    $balance = (float)($row['balance'] ?? 0);
    return $balance < 0 ? 0 : $balance;
}

function ensure_delivery_table(): void {
    db_query(
        "CREATE TABLE IF NOT EXISTS order_delivery (
            order_id INT(11) NOT NULL,
            courier_name VARCHAR(120) NOT NULL,
            courier_address VARCHAR(160) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function ensure_delivery_approval_table(): void {
    db_query(
        "CREATE TABLE IF NOT EXISTS order_delivery_approvals (
            approval_id INT(11) NOT NULL AUTO_INCREMENT,
            order_id INT(11) NOT NULL,
            seller_id INT(11) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            courier_name VARCHAR(120) DEFAULT NULL,
            courier_address VARCHAR(160) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            approved_at DATETIME DEFAULT NULL,
            PRIMARY KEY (approval_id),
            UNIQUE KEY uniq_order_seller (order_id, seller_id),
            KEY idx_order_status (order_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function get_admin_user_id_simple(): ?int {
    $row = db_fetch(
        "SELECT u.user_id
         FROM users u
         INNER JOIN roles r ON r.role_id = u.role_id
         WHERE r.role_name = 'admin'
         ORDER BY u.user_id ASC
         LIMIT 1"
    );
    return $row ? (int)$row['user_id'] : null;
}

function fetch_coupon_purchase(int $buyerId, string $code): ?array {
    $row = db_fetch(
        "SELECT cp.purchase_id, cp.coupon_id, cp.used_at, cp.uses_left,
                c.code, c.discount_type, c.discount_value, c.min_purchase, c.max_discount
         FROM coupon_purchases cp
         INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
         WHERE cp.buyer_id = ? AND c.code = ? AND COALESCE(cp.uses_left, 0) > 0
           AND c.is_active = 1 AND c.is_published = 1
           AND (c.starts_at IS NULL OR c.starts_at <= NOW())
           AND (c.ends_at IS NULL OR c.ends_at >= NOW())
         ORDER BY cp.created_at DESC
         LIMIT 1",
        [$buyerId, $code]
    );
    return $row ?: null;
}

function calculate_coupon_discount(array $coupon, float $eligibleTotal): float {
    if ($eligibleTotal <= 0) {
        return 0.0;
    }
    $discount = 0.0;
    if ($coupon['discount_type'] === 'percent') {
        $discount = $eligibleTotal * ((float)$coupon['discount_value'] / 100);
    } else {
        $discount = (float)$coupon['discount_value'];
    }
    if (!empty($coupon['max_discount'])) {
        $discount = min($discount, (float)$coupon['max_discount']);
    }
    return max(0.0, min($discount, $eligibleTotal));
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

            $discount = 0.0;
            $couponCode = null;
            $cartCoupon = db_fetch("SELECT * FROM cart_coupons WHERE cart_id = ?", [$cart_id]);
            if ($cartCoupon) {
                $couponPurchase = db_fetch(
                    "SELECT cp.purchase_id, cp.coupon_id, cp.uses_left, c.code, c.discount_type, c.discount_value, c.min_purchase, c.max_discount
                     FROM coupon_purchases cp
                     INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
                     WHERE cp.purchase_id = ? AND cp.buyer_id = ? AND COALESCE(cp.uses_left, 0) > 0
                       AND c.is_active = 1 AND c.is_published = 1
                       AND (c.starts_at IS NULL OR c.starts_at <= NOW())
                       AND (c.ends_at IS NULL OR c.ends_at >= NOW())
                     LIMIT 1",
                    [(int)$cartCoupon['coupon_purchase_id'], $buyer_id]
                );
                if ($couponPurchase) {
                    $allowedRows = db_fetch_all(
                        "SELECT product_id FROM coupon_product_allow WHERE coupon_id = ?",
                        [(int)$couponPurchase['coupon_id']]
                    );
                    $allowed = array_map(fn($row) => (int)$row['product_id'], $allowedRows);
                    $eligibleTotal = 0.0;
                    foreach ($items as $item) {
                        if (in_array((int)$item['product_id'], $allowed, true)) {
                            $eligibleTotal += ((float)$item['price']) * (int)$item['quantity'];
                        }
                    }
                    if ($eligibleTotal >= (float)$couponPurchase['min_purchase']) {
                        $discount = calculate_coupon_discount($couponPurchase, $eligibleTotal);
                        $couponCode = $couponPurchase['code'];
                    }
                } else {
                    db_execute("DELETE FROM cart_coupons WHERE cart_id = ?", [$cart_id]);
                }
            }
            
            json_out([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'discount' => $discount,
                'coupon_code' => $couponCode,
                'count' => count($items)
            ]);
            break;
        case 'apply_coupon':
            $cart_id = get_or_create_cart($buyer_id);
            $code = strtoupper(trim($_POST['code'] ?? ''));
            if ($code === '') {
                json_out(['success' => false, 'message' => 'Enter a coupon code.'], 400);
            }

            $couponPurchase = fetch_coupon_purchase((int)$buyer_id, $code);
            if (!$couponPurchase) {
                json_out(['success' => false, 'message' => 'Coupon not found or already used.'], 400);
            }

            $items = db_fetch_all(
                "SELECT ci.product_id, ci.quantity, p.price
                 FROM cart_items ci
                 INNER JOIN products p ON p.product_id = ci.product_id
                 WHERE ci.cart_id = ?",
                [$cart_id]
            );
            if (empty($items)) {
                json_out(['success' => false, 'message' => 'Cart is empty.'], 400);
            }

            $allowedRows = db_fetch_all(
                "SELECT product_id FROM coupon_product_allow WHERE coupon_id = ?",
                [(int)$couponPurchase['coupon_id']]
            );
            $allowed = array_map(fn($row) => (int)$row['product_id'], $allowedRows);
            if (empty($allowed)) {
                json_out(['success' => false, 'message' => 'No approved products for this coupon yet.'], 400);
            }

            $eligibleTotal = 0.0;
            $total = 0.0;
            foreach ($items as $item) {
                $line = ((float)$item['price']) * (int)$item['quantity'];
                $total += $line;
                if (in_array((int)$item['product_id'], $allowed, true)) {
                    $eligibleTotal += $line;
                }
            }

            if ($eligibleTotal <= 0) {
                json_out(['success' => false, 'message' => 'Coupon does not apply to your cart items.'], 400);
            }

            if ($eligibleTotal < (float)$couponPurchase['min_purchase']) {
                json_out(['success' => false, 'message' => 'Minimum purchase not met for this coupon.'], 400);
            }

            $discount = calculate_coupon_discount($couponPurchase, $eligibleTotal);
            db_execute(
                "REPLACE INTO cart_coupons (cart_id, coupon_purchase_id, coupon_id, applied_at)
                 VALUES (?, ?, ?, NOW())",
                [$cart_id, (int)$couponPurchase['purchase_id'], (int)$couponPurchase['coupon_id']]
            );

            json_out([
                'success' => true,
                'discount' => $discount,
                'total_before' => $total,
                'total_after' => max(0, $total - $discount),
                'code' => $couponPurchase['code']
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
        case 'checkout':
            $cart_id = get_or_create_cart($buyer_id);
            $items = db_fetch_all(
                "SELECT 
                    ci.product_id,
                    ci.quantity,
                    p.name,
                    p.price,
                    p.seller_id,
                    COALESCE(i.stock_qty, 0) AS stock_qty
                 FROM cart_items ci
                 INNER JOIN products p ON ci.product_id = p.product_id
                 LEFT JOIN inventory i ON p.product_id = i.product_id
                 WHERE ci.cart_id = ?",
                [$cart_id]
            );

            if (empty($items)) {
                json_out(['success' => false, 'message' => 'Your cart is empty.'], 400);
            }

            $total = 0.0;
            foreach ($items as $item) {
                $qty = (int)$item['quantity'];
                $stock = (int)($item['stock_qty'] ?? 0);
                if ($qty > $stock) {
                    json_out([
                        'success' => false,
                        'message' => 'Out of stock: ' . ($item['name'] ?? 'Item')
                    ], 400);
                }
                $total += ((float)$item['price']) * $qty;
            }

            $discount = 0.0;
            $couponPurchase = null;
            $cartCoupon = db_fetch("SELECT * FROM cart_coupons WHERE cart_id = ?", [$cart_id]);
            if ($cartCoupon) {
                $couponPurchase = db_fetch(
                    "SELECT cp.purchase_id, cp.coupon_id, cp.uses_left, c.code, c.discount_type, c.discount_value, c.min_purchase, c.max_discount
                     FROM coupon_purchases cp
                     INNER JOIN coupons c ON c.coupon_id = cp.coupon_id
                     WHERE cp.purchase_id = ? AND cp.buyer_id = ? AND COALESCE(cp.uses_left, 0) > 0
                       AND c.is_active = 1 AND c.is_published = 1
                       AND (c.starts_at IS NULL OR c.starts_at <= NOW())
                       AND (c.ends_at IS NULL OR c.ends_at >= NOW())
                     LIMIT 1",
                    [(int)$cartCoupon['coupon_purchase_id'], $buyer_id]
                );
            }

            $eligibleTotal = 0.0;
            $eligibleBySeller = [];
            if ($couponPurchase) {
                $allowedRows = db_fetch_all(
                    "SELECT product_id FROM coupon_product_allow WHERE coupon_id = ?",
                    [(int)$couponPurchase['coupon_id']]
                );
                $allowed = array_map(fn($row) => (int)$row['product_id'], $allowedRows);
                foreach ($items as $item) {
                    if (in_array((int)$item['product_id'], $allowed, true)) {
                        $line = ((float)$item['price']) * (int)$item['quantity'];
                        $eligibleTotal += $line;
                        $sellerId = (int)$item['seller_id'];
                        if (!isset($eligibleBySeller[$sellerId])) {
                            $eligibleBySeller[$sellerId] = 0.0;
                        }
                        $eligibleBySeller[$sellerId] += $line;
                    }
                }
                if ($eligibleTotal >= (float)$couponPurchase['min_purchase']) {
                    $discount = calculate_coupon_discount($couponPurchase, $eligibleTotal);
                    $total = max(0, $total - $discount);
                } else {
                    $couponPurchase = null;
                }
            }
            if ($cartCoupon && !$couponPurchase) {
                db_execute("DELETE FROM cart_coupons WHERE cart_id = ?", [$cart_id]);
            }

            $balance = get_wallet_balance((int)$buyer_id);
            if ($balance < $total) {
                json_out(['success' => false, 'message' => 'Insufficient wallet balance.'], 400);
            }

            $order_id = db_execute(
                "INSERT INTO orders (buyer_id, status, total_amount, created_at) VALUES (?, 'processing', ?, NOW())",
                [$buyer_id, $total]
            );

            foreach ($items as $item) {
                db_execute(
                    "INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)",
                    [$order_id, $item['product_id'], $item['price'], $item['quantity']]
                );
                db_execute(
                    "UPDATE inventory SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE product_id = ?",
                    [$item['quantity'], $item['product_id']]
                );
            }

            // Buyer wallet debit
            db_execute(
                "INSERT INTO wallet_transactions (user_id, txn_type, amount, note)
                 VALUES (?, 'purchase', ?, ?)",
                [$buyer_id, $total, 'Order #' . $order_id]
            );

            // Seller wallet credits
            $sellerTotals = [];
            foreach ($items as $item) {
                $seller_id = (int)$item['seller_id'];
                $line_total = ((float)$item['price']) * (int)$item['quantity'];
                if (!isset($sellerTotals[$seller_id])) {
                    $sellerTotals[$seller_id] = 0.0;
                }
                $sellerTotals[$seller_id] += $line_total;
            }
            if ($discount > 0 && $eligibleTotal > 0) {
                foreach ($eligibleBySeller as $seller_id => $eligibleAmount) {
                    $share = $eligibleAmount / $eligibleTotal;
                    $sellerTotals[$seller_id] = max(0, ($sellerTotals[$seller_id] ?? 0) - ($discount * $share));
                }
            }
            foreach ($sellerTotals as $seller_id => $amount) {
                db_execute(
                    "INSERT INTO wallet_transactions (user_id, txn_type, amount, note)
                     VALUES (?, 'credit', ?, ?)",
                    [$seller_id, $amount, 'Sale from Order #' . $order_id]
                );
            }

            $buyerRow = db_fetch("SELECT full_name FROM users WHERE user_id = ?", [$buyer_id]);
            $buyerName = $buyerRow['full_name'] ?? 'Buyer';
            ensure_delivery_approval_table();
            $baseUrl = defined('BASE_URL') ? BASE_URL : '/QuickMart';
            add_notification((int)$buyer_id, 'Order placed', "Order #{$order_id} confirmed. Waiting for seller approval.", 'order');
            foreach (array_keys($sellerTotals) as $sellerId) {
                db_execute(
                    "INSERT INTO order_delivery_approvals (order_id, seller_id, status, created_at)
                     VALUES (?, ?, 'pending', NOW())
                     ON DUPLICATE KEY UPDATE status = VALUES(status)",
                    [$order_id, $sellerId]
                );
                $approveUrl = $baseUrl . "/seller_dashboard/approve_delivery.php?order_id=" . (int)$order_id;
                add_notification((int)$sellerId, 'Approve delivery', "Order #{$order_id} is waiting for courier approval.", 'order', $approveUrl);
            }
            $adminId = get_admin_user_id_simple();
            if ($adminId) {
                add_notification($adminId, 'Order created', "Order #{$order_id} placed by {$buyerName}.", 'order');
            }

            if ($couponPurchase && $discount > 0) {
                db_execute(
                    "INSERT INTO order_coupons (order_id, coupon_id, coupon_purchase_id, discount_amount)
                     VALUES (?, ?, ?, ?)",
                    [$order_id, (int)$couponPurchase['coupon_id'], (int)$couponPurchase['purchase_id'], $discount]
                );
                db_execute(
                    "UPDATE coupon_purchases
                     SET uses_left = GREATEST(uses_left - 1, 0),
                         used_at = CASE WHEN uses_left - 1 <= 0 THEN NOW() ELSE used_at END
                     WHERE purchase_id = ?",
                    [(int)$couponPurchase['purchase_id']]
                );
            }

            db_execute("DELETE FROM cart_coupons WHERE cart_id = ?", [$cart_id]);
            db_execute("DELETE FROM cart_items WHERE cart_id = ?", [$cart_id]);

            json_out([
                'success' => true,
                'message' => 'Checkout complete',
                'order_id' => (int)$order_id
            ]);
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
