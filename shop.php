<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/wishlist.php';

$pdo = get_db();

$search = trim((string) ($_GET['q'] ?? ''));
$categorySlug = trim((string) ($_GET['category'] ?? ''));

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE p.is_active = 1";
$params = [];

if ($search !== '') {
    $sql .= " AND p.name LIKE :search";
    $params['search'] = '%' . $search . '%';
}
if ($categorySlug !== '') {
    $sql .= " AND c.slug = :category";
    $params['category'] = $categorySlug;
}
$sql .= " GROUP BY p.id ORDER BY p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

function shop_url(string $search, string $category): string
{
    $qs = array_filter(['q' => $search, 'category' => $category]);
    return url('shop.php' . (!empty($qs) ? '?' . http_build_query($qs) : ''));
}

$gridConfig = [
    'initialSearch'   => $search,
    'initialCategory' => $categorySlug,
    'initialProducts' => array_map('product_to_json', $products),
    'categories'      => array_map(static fn($c) => ['name' => $c['name'], 'slug' => $c['slug']], $categories),
    'showSearch'      => true,
    'showPills'       => true,
    'wideGrid'        => true,
    'syncUrl'         => true,
    'emptyMessage'    => 'No games matched your search.',
];

$pageTitle = APP_NAME . ' — Shop';
require __DIR__ . '/includes/header.php';
?>

<section class="pt-2">
    <h1 class="text-2xl font-bold mb-6">Our Games</h1>

    <div id="product-grid-root" data-config='<?= e(json_encode($gridConfig)) ?>'>
        <div class="mb-6">
            <form method="get" class="w-full sm:w-72">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search this list..." class="input-field" />
                <?php if ($categorySlug !== ''): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
            </form>
        </div>

        <div class="flex items-center gap-3 overflow-x-auto pb-2 mb-6">
            <a href="<?= e(shop_url($search, '')) ?>" class="<?= $categorySlug === '' ? 'btn-gradient' : 'pill' ?> rounded-full px-6 py-2.5 text-sm font-semibold shrink-0">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e(shop_url($search, $cat['slug'])) ?>" class="<?= $categorySlug === $cat['slug'] ? 'btn-gradient' : 'pill' ?> rounded-full px-6 py-2.5 text-sm font-semibold shrink-0"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <p class="text-muted">No games matched your search.</p>
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
