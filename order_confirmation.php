<?php
require_once __DIR__ . '/includes/auth.php';

require_login();

$pdo = get_db();
$orderId = (int) ($_GET['order_id'] ?? 0);

$orderStmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$orderStmt->execute([$orderId, current_user()['id']]);
$order = $orderStmt->fetch();

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('index.php');
}

$itemsStmt = $pdo->prepare(
    "SELECT oi.*, p.name, p.image_path, p.is_digital
     FROM order_items oi JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$keysStmt = $pdo->prepare(
    "SELECT pk.*, oi.product_id FROM product_keys pk
     JOIN order_items oi ON oi.id = pk.order_item_id
     WHERE oi.order_id = ?"
);
$keysStmt->execute([$orderId]);
$keysByProduct = [];
foreach ($keysStmt->fetchAll() as $key) {
    $keysByProduct[$key['product_id']][] = $key['key_code'];
}

$pageTitle = APP_NAME . ' — Order Confirmation';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-3xl pt-2">
    <h1 class="text-3xl font-bold mb-2">
        <?= $order['status'] === 'paid' ? 'Thank you for your order!' : 'Order #' . (int) $order['id'] ?>
    </h1>
    <p class="text-muted mb-8">
        Status:
        <span class="<?= $order['status'] === 'paid' ? 'text-accent' : 'text-yellow-400' ?> font-semibold">
            <?= e(ucfirst($order['status'])) ?>
        </span>
    </p>

    <div class="flex flex-col gap-4">
        <?php foreach ($items as $item): ?>
            <div class="bg-surface rounded-2xl p-4 border border-white/5">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl bg-surface2 flex items-center justify-center shrink-0 p-2">
                        <img src="<?= e(url($item['image_path'])) ?>" class="max-w-full max-h-full object-contain" alt="<?= e($item['name']) ?>" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold"><?= e($item['name']) ?></p>
                        <p class="text-muted text-sm">Qty <?= (int) $item['quantity'] ?> &middot; <?= money($item['subtotal']) ?></p>
                    </div>
                </div>

                <?php if ($order['status'] === 'paid' && $item['is_digital'] && !empty($keysByProduct[$item['product_id']])): ?>
                    <div class="mt-4 pt-4 border-t border-white/5">
                        <p class="text-sm text-muted mb-2">Your digital key<?= count($keysByProduct[$item['product_id']]) > 1 ? 's' : '' ?>:</p>
                        <?php foreach ($keysByProduct[$item['product_id']] as $keyCode): ?>
                            <code class="block bg-black/40 border border-accent/20 text-accent rounded-lg px-3 py-2 text-sm mb-1"><?= e($keyCode) ?></code>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-between items-center mt-8 bg-surface rounded-2xl p-6 border border-accent/10">
        <span class="text-lg font-bold">Order Total</span>
        <span class="text-2xl font-bold text-accent"><?= money($order['total']) ?></span>
    </div>

    <a href="<?= e(url('profile.php')) ?>" class="block text-center mt-8 px-6 py-3 rounded-xl btn-gradient font-semibold">View Order History</a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
