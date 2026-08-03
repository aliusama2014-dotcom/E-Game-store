<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}

csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
cart_remove($productId);

if (is_ajax_request()) {
    $resolved = cart_resolve(get_db());
    json_response(['success' => true, 'lines' => cart_lines_for_json($resolved['lines']), 'subtotal' => $resolved['subtotal'], 'cart_count' => cart_count()]);
}

flash_set('success', 'Item removed from cart.');
redirect('cart.php');
