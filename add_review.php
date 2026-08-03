<?php
require_once __DIR__ . '/includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}
csrf_verify();

$pdo = get_db();
$productId = (int) ($_POST['product_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = post_str('comment');
$userId = current_user()['id'];

$productStmt = $pdo->prepare('SELECT slug FROM products WHERE id = ?');
$productStmt->execute([$productId]);
$product = $productStmt->fetch();

if (!$product) {
    flash_set('error', 'Game not found.');
    redirect('shop.php');
}

if ($rating < 1 || $rating > 5) {
    flash_set('error', 'Please choose a rating between 1 and 5 stars.');
    redirect('product.php?slug=' . $product['slug']);
}

$purchaseCheck = $pdo->prepare(
    "SELECT COUNT(*) FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'paid'"
);
$purchaseCheck->execute([$userId, $productId]);
if ((int) $purchaseCheck->fetchColumn() === 0) {
    flash_set('error', 'You can only review games you have purchased.');
    redirect('product.php?slug=' . $product['slug']);
}

try {
    $pdo->prepare(
        'INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)'
    )->execute([$productId, $userId, $rating, $comment !== '' ? $comment : null]);
    flash_set('success', 'Thanks for your review!');
} catch (PDOException $e) {
    // Unique constraint on (product_id, user_id) — already reviewed.
    flash_set('error', 'You have already reviewed this game.');
}

redirect('product.php?slug=' . $product['slug']);
