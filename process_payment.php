<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}
csrf_verify();

function luhn_is_valid(string $digits): bool
{
    $sum = 0;
    $alt = false;
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = (int) $digits[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    return $sum % 10 === 0;
}

$pdo = get_db();
$orderId = (int) ($_POST['order_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = "pending"');
$stmt->execute([$orderId, current_user()['id']]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'Order not found or already processed.');
    redirect('cart.php');
}

$cardName = post_str('card_name');
$cardNumRaw = preg_replace('/\D/', '', (string) ($_POST['card_num'] ?? ''));
$expDate = post_str('exp_date');
$cvv = preg_replace('/\D/', '', (string) ($_POST['cvv'] ?? ''));

$formatValid = $cardName !== ''
    && preg_match('/^\d{13,19}$/', $cardNumRaw)
    && preg_match('/^\d{2}\/\d{2}$/', $expDate)
    && preg_match('/^\d{3,4}$/', $cvv)
    && luhn_is_valid($cardNumRaw);

// Simulated gateway rule: numbers ending in 0000 are declined so the
// flow can be tested end-to-end without a real payment processor.
$isDeclineTest = str_ends_with($cardNumRaw, '0000');
$approved = $formatValid && !$isDeclineTest;

$last4 = substr($cardNumRaw, -4) ?: null;
$brand = match (true) {
    str_starts_with($cardNumRaw, '4') => 'Visa',
    str_starts_with($cardNumRaw, '5') => 'Mastercard',
    default => 'Card',
};
$transactionRef = strtoupper(bin2hex(random_bytes(8)));

// NOTE: $cardNumRaw and $cvv are used only in memory for this request
// and are never written to the database — only the masked last4 is.

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO payments (order_id, method, card_last4, card_brand, status, transaction_ref)
         VALUES (?, "mock_card", ?, ?, ?, ?)'
    )->execute([$orderId, $last4, $brand, $approved ? 'approved' : 'declined', $transactionRef]);

    if (!$approved) {
        $pdo->commit();
        flash_set('error', 'Payment declined. Please check your card details and try again.');
        redirect('payment.php?order_id=' . $orderId);
    }

    // Decrement stock and hand out a digital key per unit purchased.
    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();

    foreach ($items as $item) {
        $updateStock = $pdo->prepare(
            'UPDATE products SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE id = ?'
        );
        $updateStock->execute([$item['quantity'], $item['product_id']]);

        // Claim up to `quantity` unused keys for this product.
        $keyStmt = $pdo->prepare(
            'SELECT id FROM product_keys WHERE product_id = ? AND order_item_id IS NULL LIMIT ? FOR UPDATE'
        );
        $keyStmt->bindValue(1, $item['product_id'], PDO::PARAM_INT);
        $keyStmt->bindValue(2, (int) $item['quantity'], PDO::PARAM_INT);
        $keyStmt->execute();
        $availableKeys = $keyStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($availableKeys as $keyId) {
            $pdo->prepare('UPDATE product_keys SET order_item_id = ? WHERE id = ?')
                ->execute([$item['id'], $keyId]);
        }

        // If the pool ran short, mint fresh mock keys rather than
        // leaving the customer without one — this is a demo store.
        $missing = (int) $item['quantity'] - count($availableKeys);
        for ($i = 0; $i < $missing; $i++) {
            $pdo->prepare(
                'INSERT INTO product_keys (product_id, key_code, order_item_id) VALUES (?, ?, ?)'
            )->execute([$item['product_id'], generate_mock_key(), $item['id']]);
        }
    }

    $pdo->prepare('UPDATE orders SET status = "paid" WHERE id = ?')->execute([$orderId]);
    $pdo->commit();

    cart_clear();
    flash_set('success', 'Payment approved! Your digital keys are ready below.');
    redirect('order_confirmation.php?order_id=' . $orderId);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Payment processing failed: ' . $e->getMessage());
    flash_set('error', 'Something went wrong processing your payment. Please try again.');
    redirect('payment.php?order_id=' . $orderId);
}
