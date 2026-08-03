<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

require_login();

$pdo = get_db();
$resolved = cart_resolve($pdo);
$lines = $resolved['lines'];
$subtotal = $resolved['subtotal'];

if (empty($lines)) {
    flash_set('error', 'Your cart is empty.');
    redirect('shop.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Re-resolve against the DB one more time right before writing,
    // so nothing stale from the session ever hits the orders table.
    $resolved = cart_resolve($pdo);
    $lines = $resolved['lines'];
    $subtotal = $resolved['subtotal'];

    if (empty($lines)) {
        flash_set('error', 'Your cart is empty.');
        redirect('shop.php');
    }

    try {
        $pdo->beginTransaction();

        // Lock and verify stock for every line before committing to an order.
        foreach ($lines as $line) {
            $stmt = $pdo->prepare('SELECT stock_qty FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$line['product']['id']]);
            $stock = (int) $stmt->fetchColumn();
            if ($stock < $line['quantity']) {
                throw new RuntimeException($line['product']['name'] . ' no longer has enough stock.');
            }
        }

        $orderStmt = $pdo->prepare(
            'INSERT INTO orders (user_id, status, subtotal, total) VALUES (?, "pending", ?, ?)'
        );
        $orderStmt->execute([current_user()['id'], $subtotal, $subtotal]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($lines as $line) {
            $itemStmt->execute([
                $orderId,
                $line['product']['id'],
                $line['quantity'],
                $line['product']['price'],
                $line['line_total'],
            ]);
        }

        $pdo->commit();
        redirect('payment.php?order_id=' . $orderId);
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('Checkout failed: ' . $e->getMessage());
        flash_set('error', 'Could not start checkout: ' . $e->getMessage());
        redirect('cart.php');
    }
}

$pageTitle = APP_NAME . ' — Checkout';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-3xl pt-2">
    <h1 class="text-3xl font-bold mb-8">Checkout</h1>

    <div class="bg-surface rounded-2xl p-6 border border-accent/10 mb-8">
        <?php foreach ($lines as $line): $p = $line['product']; ?>
            <div class="flex justify-between py-2 border-b border-white/5 last:border-0">
                <span><?= e($p['name']) ?> &times; <?= (int) $line['quantity'] ?></span>
                <span><?= money($line['line_total']) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="flex justify-between pt-4 mt-2 text-lg font-bold">
            <span>Total</span>
            <span class="text-accent"><?= money($subtotal) ?></span>
        </div>
    </div>

    <form action="<?= e(url('checkout.php')) ?>" method="post">
        <?= csrf_field() ?>
        <button class="w-full px-6 py-3 rounded-xl btn-gradient font-semibold">Continue to Payment</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
