<?php
require_once __DIR__ . '/includes/auth.php';

require_login();
$pdo = get_db();
$userId = current_user()['id'];

$ordersStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

$pageTitle = APP_NAME . ' — My Account';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl pt-2">
    <h1 class="text-3xl font-bold mb-1">My Account</h1>
    <p class="text-muted mb-8"><?= e(current_user()['first_name']) ?> <?= e(current_user()['last_name']) ?> &middot; <?= e(current_user()['email']) ?></p>

    <h2 class="text-xl font-bold mb-4">Order History</h2>

    <?php if (empty($orders)): ?>
        <p class="text-muted">You haven't placed any orders yet. <a href="<?= e(url('shop.php')) ?>" class="text-accent hover:underline">Start shopping</a>.</p>
    <?php else: ?>
        <div class="flex flex-col gap-3">
            <?php foreach ($orders as $order): ?>
                <a href="<?= e(url('order_confirmation.php?order_id=' . $order['id'])) ?>" class="bg-surface rounded-xl p-4 border border-white/5 flex items-center justify-between hover:border-accent/30 transition">
                    <div>
                        <p class="font-semibold">Order #<?= (int) $order['id'] ?></p>
                        <p class="text-muted text-sm"><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-accent font-semibold"><?= money($order['total']) ?></p>
                        <p class="text-xs <?= $order['status'] === 'paid' ? 'text-accent' : 'text-yellow-400' ?>"><?= e(ucfirst($order['status'])) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
