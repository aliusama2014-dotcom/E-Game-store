<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/wishlist.php';

$pdo = get_db();
$slug = trim((string) ($_GET['slug'] ?? ''));

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.is_active = 1"
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Game Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="text-center text-muted py-20">Sorry, that game could not be found.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$reviewStmt = $pdo->prepare(
    "SELECT r.*, u.first_name, u.last_name FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ? ORDER BY r.created_at DESC"
);
$reviewStmt->execute([$product['id']]);
$reviews = $reviewStmt->fetchAll();

$avgRating = 0;
$reviewCount = count($reviews);
if ($reviewCount > 0) {
    $avgRating = round(array_sum(array_column($reviews, 'rating')) / $reviewCount, 1);
}

// A user may review a product only after actually purchasing it (paid order).
$canReview = false;
$alreadyReviewed = false;
if (is_logged_in()) {
    $purchaseCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'paid'"
    );
    $purchaseCheck->execute([current_user()['id'], $product['id']]);
    $canReview = (int) $purchaseCheck->fetchColumn() > 0;

    $ownReviewCheck = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND user_id = ?");
    $ownReviewCheck->execute([$product['id'], current_user()['id']]);
    $alreadyReviewed = (int) $ownReviewCheck->fetchColumn() > 0;
}

$isFavorited = wishlist_has((int) $product['id']);
$hasDiscount = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['price'];

$pageTitle = $product['name'] . ' — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="pt-2 grid md:grid-cols-2 gap-10">
    <div class="relative bg-surface2 rounded-3xl shadow-glowLg aspect-[4/3] overflow-hidden flex items-center justify-center p-6">
        <img src="<?= e(url($product['image_path'])) ?>" class="max-w-full max-h-full object-contain" alt="<?= e($product['name']) ?>" />
        <form action="<?= e(url('wishlist_toggle.php')) ?>" method="post" class="absolute top-4 right-4">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <button type="submit" class="wishlist-btn !w-11 !h-11 <?= $isFavorited ? 'is-active' : '' ?>" title="<?= $isFavorited ? 'Remove from favorites' : 'Add to favorites' ?>">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="<?= $isFavorited ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= icon('heart') ?></svg>
            </button>
        </form>
        <?php if ($hasDiscount): ?>
            <span class="absolute top-4 left-4 btn-gradient text-xs font-bold px-3 py-1.5 rounded-full">
                Save <?= (int) round((1 - $product['price'] / $product['compare_at_price']) * 100) ?>%
            </span>
        <?php endif; ?>
    </div>

    <div>
        <p class="text-muted text-sm"><?= e($product['category_name'] ?? 'Uncategorized') ?> &middot; <?= e($product['platform']) ?></p>
        <h1 class="text-3xl md:text-4xl font-extrabold mt-1"><?= e($product['name']) ?></h1>

        <div class="flex items-center gap-2 mt-3">
            <span class="text-accent text-lg">
                <?= str_repeat('★', (int) round($avgRating)) . str_repeat('☆', 5 - (int) round($avgRating)) ?>
            </span>
            <span class="text-muted text-sm"><?= $reviewCount > 0 ? "$avgRating / 5 ($reviewCount reviews)" : 'No reviews yet' ?></span>
        </div>

        <p class="text-gray-300 mt-5 leading-relaxed"><?= nl2br(e($product['description'] ?? '')) ?></p>

        <div class="flex items-baseline gap-3 mt-6">
            <?php if ($hasDiscount): ?>
                <span class="text-muted line-through text-lg"><?= money($product['compare_at_price']) ?></span>
            <?php endif; ?>
            <p class="text-3xl font-bold text-accent"><?= money($product['price']) ?></p>
        </div>
        <p class="text-sm text-muted mt-1">
            <?= $product['is_digital'] ? 'Digital key delivered instantly after payment' : 'Physical copy' ?>
            &middot; <?= (int) $product['stock_qty'] > 0 ? (int) $product['stock_qty'] . ' in stock' : 'Out of stock' ?>
        </p>

        <form action="<?= e(url('cart_add.php')) ?>" method="post" class="flex items-center gap-3 mt-6">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="number" name="quantity" value="1" min="1" max="<?= (int) $product['stock_qty'] ?>" class="input-field w-24" />
            <button class="px-6 py-3 rounded-xl btn-gradient font-semibold" <?= (int) $product['stock_qty'] <= 0 ? 'disabled' : '' ?>>
                Add to Cart
            </button>
        </form>
    </div>
</section>

<!-- REVIEWS -->
<section class="pb-4 mt-14">
    <h2 class="text-2xl font-bold mb-6">Customer Reviews</h2>

    <?php if ($canReview && !$alreadyReviewed): ?>
        <form action="<?= e(url('add_review.php')) ?>" method="post" class="bg-surface rounded-2xl p-6 mb-8 border border-white/5">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <p class="font-semibold mb-2">Leave a review</p>
            <div class="star-rating mb-3">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?> />
                    <label for="star<?= $i ?>">★</label>
                <?php endfor; ?>
            </div>
            <textarea name="comment" rows="3" placeholder="What did you think?" class="input-field"></textarea>
            <button class="mt-3 px-5 py-2 rounded-xl btn-gradient font-semibold">Submit Review</button>
        </form>
    <?php elseif ($alreadyReviewed): ?>
        <p class="text-muted mb-8">You've already reviewed this game — thanks!</p>
    <?php elseif (is_logged_in()): ?>
        <p class="text-muted mb-8">Purchase this game to leave a review.</p>
    <?php else: ?>
        <p class="text-muted mb-8"><a href="<?= e(url('sign_in.php')) ?>" class="text-accent hover:underline">Sign in</a> to leave a review.</p>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
        <p class="text-muted text-sm">No reviews yet — be the first!</p>
    <?php else: ?>
        <div class="flex flex-col gap-4">
            <?php foreach ($reviews as $review): ?>
                <div class="bg-surface rounded-xl p-4 border border-white/5">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold"><?= e($review['first_name']) ?> <?= e(substr($review['last_name'], 0, 1)) ?>.</span>
                        <span class="text-accent text-sm"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></span>
                    </div>
                    <?php if (!empty($review['comment'])): ?>
                        <p class="text-gray-300 text-sm mt-2"><?= nl2br(e($review['comment'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
