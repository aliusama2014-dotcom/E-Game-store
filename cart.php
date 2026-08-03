<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pdo = get_db();
$resolved = cart_resolve($pdo);
$lines = $resolved['lines'];
$subtotal = $resolved['subtotal'];

$cartConfig = [
    'initialLines'    => cart_lines_for_json($lines),
    'initialSubtotal' => $subtotal,
    'checkoutUrl'     => url('checkout.php'),
    'shopUrl'         => url('shop.php'),
];

$pageTitle = APP_NAME . ' — Your Cart';
require __DIR__ . '/includes/header.php';
?>

<section class="pt-2 max-w-5xl">
    <h1 class="text-2xl font-bold mb-8">Your Cart</h1>

    <div id="cart-root" data-config='<?= e(json_encode($cartConfig)) ?>'>
        <?php if (empty($lines)): ?>
            <p class="text-muted">Your cart is empty. <a href="<?= e(url('shop.php')) ?>" class="text-accent hover:underline">Browse the shop</a>.</p>
        <?php else: ?>
            <div class="flex flex-col gap-4">
                <?php foreach ($lines as $line): $p = $line['product']; ?>
                    <div class="bg-surface rounded-2xl p-4 flex items-center gap-4 border border-white/5">
                        <div class="w-20 h-20 rounded-xl bg-surface2 flex items-center justify-center shrink-0 p-2">
                            <img src="<?= e(url($p['image_path'])) ?>" class="max-w-full max-h-full object-contain" alt="<?= e($p['name']) ?>" />
                        </div>
                        <div class="flex-1">
                            <a href="<?= e(url('product.php?slug=' . $p['slug'])) ?>" class="font-semibold hover:text-accent transition"><?= e($p['name']) ?></a>
                            <p class="text-muted text-sm"><?= money($p['price']) ?> each</p>
                        </div>

                        <form action="<?= e(url('cart_update.php')) ?>" method="post" class="flex items-center gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                            <input type="number" name="quantity" value="<?= (int) $line['quantity'] ?>" min="1" max="<?= (int) $p['stock_qty'] ?>" class="input-field w-20" />
                            <button class="px-3 py-2 rounded-lg btn-gradient text-sm font-semibold">Update</button>
                        </form>

                        <p class="w-24 text-right font-semibold text-accent"><?= money($line['line_total']) ?></p>

                        <form action="<?= e(url('cart_remove.php')) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                            <button class="text-red-400 hover:text-red-300 transition text-sm">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end mt-8">
                <div class="w-full max-w-sm bg-surface rounded-2xl p-6 border border-white/5">
                    <div class="flex justify-between text-lg mb-4">
                        <span>Subtotal</span>
                        <span class="text-accent font-bold"><?= money($subtotal) ?></span>
                    </div>
                    <a href="<?= e(url('checkout.php')) ?>" class="block text-center px-6 py-3 rounded-xl btn-gradient font-semibold">Proceed to Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
