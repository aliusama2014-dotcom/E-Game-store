<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/wishlist.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}
csrf_verify();

$productId = (int) ($_POST['product_id'] ?? 0);
$nowFavorited = wishlist_toggle($productId);

if (is_ajax_request()) {
    json_response(['success' => true, 'favorited' => $nowFavorited, 'wishlist_count' => wishlist_count()]);
}

flash_set('success', $nowFavorited ? 'Added to your favorites.' : 'Removed from your favorites.');

$redirectTo = $_SERVER['HTTP_REFERER'] ?? url('shop.php');
header('Location: ' . $redirectTo);
exit;
