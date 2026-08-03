<?php
require_once __DIR__ . '/includes/auth.php';

require_login();

$pdo = get_db();
$orderId = (int) ($_GET['order_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, current_user()['id']]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('cart.php');
}

if ($order['status'] !== 'pending') {
    redirect('order_confirmation.php?order_id=' . $orderId);
}

$pageTitle = APP_NAME . ' — Payment';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center pt-4 pb-14">
    <div class="w-full max-w-md bg-surface rounded-3xl border border-accent/10 shadow-glow hover:shadow-glowLg transition p-9">
        <h1 class="text-2xl font-bold text-center mb-1">Add Payment</h1>
        <p class="text-center text-accent font-semibold mb-6">Total due: <?= money($order['total']) ?></p>

        <div class="rounded-xl px-4 py-3 text-xs bg-black/40 border border-white/10 text-muted mb-6">
            <strong class="text-gray-300">This is a mock gateway</strong> — no real charge occurs and no card
            details are ever stored. Use test number <span class="text-accent">4242 4242 4242 4242</span> for an
            approved payment, or any number ending in <span class="text-accent">0000</span> to simulate a decline.
        </div>

        <form action="<?= e(url('process_payment.php')) ?>" method="post" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

            <input type="text" placeholder="Cardholder Name" name="card_name" class="input-field" required>
            <input type="text" placeholder="Card Number" name="card_num" inputmode="numeric" autocomplete="cc-number" class="input-field" required maxlength="23">
            <input type="text" placeholder="Expiration Date (MM/YY)" name="exp_date" class="input-field" required maxlength="5">
            <input type="text" placeholder="CVV" name="cvv" inputmode="numeric" class="input-field" required maxlength="4">

            <button class="mt-2 px-6 py-3 rounded-xl btn-gradient font-semibold">Pay <?= money($order['total']) ?></button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
