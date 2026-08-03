<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/wishlist.php';

$pdo = get_db();

// Hero slides: deals first (so the discount story is real, not staged),
// then fill with the newest active listings.
$heroProducts = $pdo->query(
    "SELECT * FROM products WHERE is_active = 1
     ORDER BY (compare_at_price IS NOT NULL AND compare_at_price > price) DESC, created_at DESC
     LIMIT 4"
)->fetchAll();

// Popular Right Now: highest-rated first, falling back to newest so
// the rail isn't empty before any reviews exist.
$popular = $pdo->query(
    "SELECT p.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
     FROM products p
     LEFT JOIN reviews r ON r.product_id = p.id
     WHERE p.is_active = 1
     GROUP BY p.id
     ORDER BY avg_rating DESC, p.created_at DESC
     LIMIT 5"
)->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$activeCategory = trim((string) ($_GET['category'] ?? ''));

$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE p.is_active = 1";
$params = [];
if ($activeCategory !== '') {
    $sql .= " AND c.slug = :category";
    $params['category'] = $activeCategory;
}
$sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT 9";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$gridProducts = $stmt->fetchAll();

/** Small, data-derived flavor tags for the hero — not arbitrary fluff. */
function hero_tags(array $product): array
{
    $tags = [$product['platform']];
    if (!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['price']) {
        $tags[] = 'On Sale';
    }
    if (!empty($product['avg_rating']) && $product['avg_rating'] >= 4.5) {
        $tags[] = 'Fan Favorite';
    } else {
        $tags[] = 'New';
    }
    return array_slice($tags, 0, 3);
}

$pageTitle = APP_NAME . ' — Home';

$gridConfig = [
    'initialCategory' => $activeCategory,
    'initialProducts' => array_map('product_to_json', $gridProducts),
    'categories'      => array_map(static fn($c) => ['name' => $c['name'], 'slug' => $c['slug']], $categories),
    'showSearch'      => false,
    'showPills'       => true,
    'wideGrid'        => false,
    'syncUrl'         => true,
    'emptyMessage'    => 'No games in this category yet.',
];

require __DIR__ . '/includes/header.php';
?>

<div class="grid lg:grid-cols-[1fr_320px] gap-6 pt-2">
    <!-- HERO -->
    <div class="hero-carousel relative rounded-3xl overflow-hidden bg-surface min-h-[420px]" data-autoplay="6000">
        <?php foreach ($heroProducts as $i => $product):
            $tags = hero_tags($product);
            $hasDiscount = !empty($product['compare_at_price']) && $product['compare_at_price'] > $product['price'];
        ?>
            <div class="hero-slide <?= $i === 0 ? '' : 'hidden' ?> relative min-h-[420px] flex items-end" data-slide="<?= $i ?>">
                <img src="<?= e(url($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" class="absolute inset-0 w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-t from-bg via-bg/70 to-bg/10"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-bg/90 via-transparent to-transparent"></div>

                <span class="absolute top-6 right-7 text-white/40 text-sm font-semibold"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>

                <div class="relative z-10 p-7 md:p-10 max-w-xl">
                    <div class="flex gap-2 mb-4 flex-wrap">
                        <?php foreach ($tags as $tag): ?>
                            <span class="text-[11px] font-semibold bg-white/10 backdrop-blur px-3 py-1.5 rounded-full"><?= e($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight leading-none uppercase"><?= e($product['name']) ?></h1>
                    <p class="text-muted mt-3 text-sm md:text-base leading-relaxed line-clamp-2"><?= e($product['description'] ?? '') ?></p>

                    <div class="flex items-center gap-4 mt-6 flex-wrap">
                        <div class="flex items-baseline gap-2">
                            <?php if ($hasDiscount): ?>
                                <span class="text-muted line-through text-sm"><?= money($product['compare_at_price']) ?></span>
                            <?php endif; ?>
                            <span class="btn-gradient px-4 py-2 rounded-xl font-bold">
                                <?= money($product['price']) ?>
                                <?php if ($hasDiscount): ?>
                                    <span class="font-normal text-xs">(Save <?= (int) round((1 - $product['price'] / $product['compare_at_price']) * 100) ?>%)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <a href="<?= e(url('product.php?slug=' . $product['slug'])) ?>" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur transition font-semibold text-sm">View Game</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($heroProducts) > 1): ?>
            <div class="absolute bottom-6 left-7 md:left-10 flex gap-2 z-10">
                <?php foreach ($heroProducts as $i => $product): ?>
                    <button class="carousel-dot <?= $i === 0 ? 'is-active' : '' ?>" data-goto="<?= $i ?>" aria-label="Go to slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- POPULAR RIGHT NOW -->
    <aside class="bg-surface rounded-3xl p-5 flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">Popular Right Now</h2>
            <a href="<?= e(url('shop.php')) ?>" class="text-xs text-accent hover:underline">View all</a>
        </div>

        <div class="flex flex-col gap-3">
            <?php foreach ($popular as $product): ?>
                <a href="<?= e(url('product.php?slug=' . $product['slug'])) ?>" class="flex items-center gap-3 group">
                    <div class="w-14 h-14 rounded-xl bg-surface2 flex items-center justify-center shrink-0 p-1.5">
                        <img src="<?= e(url($product['image_path'])) ?>" class="max-w-full max-h-full object-contain" alt="<?= e($product['name']) ?>" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold truncate group-hover:text-accent transition"><?= e($product['name']) ?></p>
                        <p class="text-xs text-muted"><?= e($product['category_name'] ?? 'Game') ?></p>
                        <p class="text-xs text-muted flex items-center gap-1 mt-0.5">
                            <svg viewBox="0 0 24 24" width="10" height="10" fill="#ff5d82" stroke="none"><?= icon('star') ?></svg>
                            <?= $product['avg_rating'] ? round((float) $product['avg_rating'], 1) : 'New' ?>
                        </p>
                    </div>
                    <span class="btn-gradient text-[11px] font-bold px-2.5 py-1.5 rounded-full whitespace-nowrap"><?= money($product['price']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<!-- CATEGORY + GRID -->
<section class="mt-10">
    <h2 class="text-xl font-bold mb-4">Game Category</h2>

    <div id="product-grid-root" data-config='<?= e(json_encode($gridConfig)) ?>'>
        <div class="flex items-center gap-3 overflow-x-auto pb-1 mb-6">
            <a href="<?= e(url('index.php')) ?>" class="<?= $activeCategory === '' ? 'btn-gradient' : 'pill' ?> rounded-full px-6 py-2.5 text-sm font-semibold shrink-0">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e(url('index.php?category=' . $cat['slug'])) ?>" class="<?= $activeCategory === $cat['slug'] ? 'btn-gradient' : 'pill' ?> rounded-full px-6 py-2.5 text-sm font-semibold shrink-0"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($gridProducts)): ?>
            <p class="text-muted">No games in this category yet.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                <?php foreach ($gridProducts as $product): ?>
                    <?php require __DIR__ . '/includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
