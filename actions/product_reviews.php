<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

// Ensure optional image column exists
try {
    db_query("ALTER TABLE product_reviews ADD COLUMN image_url VARCHAR(255) NULL");
} catch (Throwable $e) {
    // Ignore if column already exists
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if ($productId <= 0) {
        json_out(['success' => false, 'message' => 'Invalid product'], 400);
    }

    $reviews = db_fetch_all(
        "SELECT pr.review_id, pr.rating, pr.comment, pr.image_url, pr.created_at,
                u.full_name AS buyer_name
         FROM product_reviews pr
         INNER JOIN users u ON u.user_id = pr.buyer_id
         WHERE pr.product_id = ?
         ORDER BY pr.created_at DESC",
        [$productId]
    );

    $summary = db_fetch(
        "SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS total_reviews
         FROM product_reviews
         WHERE product_id = ?",
        [$productId]
    );

    json_out([
        'success' => true,
        'reviews' => $reviews,
        'average' => round((float)($summary['avg_rating'] ?? 0), 1),
        'count' => (int)($summary['total_reviews'] ?? 0),
    ]);
}

if ($method === 'POST') {
    require_role('buyer');

    $productId = (int)($_POST['product_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));
    $buyerId = get_user_id() ?? 0;

    if ($productId <= 0 || $buyerId <= 0) {
        json_out(['success' => false, 'message' => 'Invalid request'], 400);
    }
    if ($rating < 1 || $rating > 5) {
        json_out(['success' => false, 'message' => 'Rating must be between 1 and 5'], 400);
    }
    if ($comment === '' && empty($_FILES['image'])) {
        json_out(['success' => false, 'message' => 'Please write a review or add an image'], 400);
    }

    $imageUrl = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $origName = $_FILES['image']['name'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            json_out(['success' => false, 'message' => 'Image must be JPG, PNG, or WEBP'], 400);
        }

        $dir = __DIR__ . '/../images/reviews';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = uniqid('review_', true) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $dest)) {
            json_out(['success' => false, 'message' => 'Failed to upload image'], 500);
        }

        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $imageUrl = $base . '/images/reviews/' . $filename;
    }

    $existing = db_fetch(
        "SELECT review_id, image_url
         FROM product_reviews
         WHERE product_id = ? AND buyer_id = ?
         LIMIT 1",
        [$productId, $buyerId]
    );

    if ($existing) {
        $finalImage = $imageUrl ?: ($existing['image_url'] ?? null);
        db_execute(
            "UPDATE product_reviews
             SET rating = ?, comment = ?, image_url = ?, created_at = NOW()
             WHERE review_id = ?",
            [$rating, $comment, $finalImage, $existing['review_id']]
        );
    } else {
        db_execute(
            "INSERT INTO product_reviews (product_id, buyer_id, rating, comment, image_url, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$productId, $buyerId, $rating, $comment, $imageUrl]
        );
    }

    json_out(['success' => true, 'message' => 'Review submitted']);
}

json_out(['success' => false, 'message' => 'Method not allowed'], 405);
