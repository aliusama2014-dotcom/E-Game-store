<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}

csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
$qty = max(1, (int) ($_POST['quantity'] ?? 1));

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name, stock_qty FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => 'That game could not be found.'], 404);
    }
    flash_set('error', 'That game could not be found.');
    redirect('shop.php');
}

if ((int) $product['stock_qty'] <= 0) {
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $product['name'] . ' is currently out of stock.'], 409);
    }
    flash_set('error', $product['name'] . ' is currently out of stock.');
    redirect('shop.php');
}

cart_add($productId, $qty);

if (is_ajax_request()) {
    json_response(['success' => true, 'message' => $product['name'] . ' added to your cart.', 'cart_count' => cart_count()]);
}

flash_set('success', $product['name'] . ' added to your cart.');

$redirectTo = $_SERVER['HTTP_REFERER'] ?? url('shop.php');
header('Location: ' . $redirectTo);
exit;
