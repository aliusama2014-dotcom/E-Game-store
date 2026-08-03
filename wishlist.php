<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/wishlist.php';

$pdo = get_db();
$products = wishlist_resolve($pdo);

$gridConfig = [
    'initialProducts' => array_map('product_to_json', $products),
    'favoritesOnly'   => true,
    'showSearch'      => false,
    'showPills'       => false,
    'wideGrid'        => true,
    'syncUrl'         => false,
    'emptyMessage'    => "Nothing here yet — tap the heart on any game to save it for later.",
];

$pageTitle = APP_NAME . ' — Favorites';
require __DIR__ . '/includes/header.php';
?>

<section class="pt-2">
    <h1 class="text-2xl font-bold mb-6">Your Favorites</h1>

    <div id="product-grid-root" data-config='<?= e(json_encode($gridConfig)) ?>'>
        <?php if (empty($products)): ?>
            <p class="text-muted">
                Nothing here yet — tap the heart on any game to save it for later.
                <a href="<?= e(url('shop.php')) ?>" class="text-accent hover:underline">Browse the shop</a>.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                <?php foreach ($products as $product): ?>
                    <?php require __DIR__ . '/includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
