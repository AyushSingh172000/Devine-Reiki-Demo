<?php
// Crystal Shop / Products Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Reiki Charged Crystal Shop | Reiki Bliss";
$pageDescription = "Explore 100% natural, white-sage cleansed and Reiki Master charged crystal bracelets, pendulums, gemstones, and energetic home hangings.";

// Get parameters
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Fetch Active Categories from Category Master
$activeCategories = [];
try {
    $catQuery = $pdo->query("SELECT name, slug FROM product_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $activeCategories = $catQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($activeCategories)) {
    try {
        $distCats = $pdo->query("SELECT DISTINCT category FROM products WHERE is_active = 1 AND category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($distCats as $dc) {
            $activeCategories[] = [
                'name' => $dc, 
                'slug' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($dc)))
            ];
        }
    } catch (PDOException $e) {}
}

// Build Query
$whereClause = "WHERE is_active = 1";
$params = [];

if ($category !== 'all' && !empty($category)) {
    $catClean = str_replace('-', ' ', $category);
    $whereClause .= " AND (category = ? OR category LIKE ? OR LOWER(REPLACE(category, ' ', '-')) = ?)";
    $params[] = $category;
    $params[] = '%' . $catClean . '%';
    $params[] = strtolower($category);
}

// Sorting Order
$orderBy = "ORDER BY sort_order ASC, created_at DESC";
if ($sort === 'price_asc') {
    $orderBy = "ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $orderBy = "ORDER BY price DESC";
} elseif ($sort === 'newest') {
    $orderBy = "ORDER BY created_at DESC";
}

// Total Count Query
$totalProducts = 0;
try {
    $countSql = "SELECT COUNT(*) FROM products {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Count query error in products.php: " . $e->getMessage());
}

$totalPages = max(1, ceil($totalProducts / $perPage));

// Fetch Products Query
$products = [];
try {
    $sql = "SELECT * FROM products {$whereClause} {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Products query error in products.php: " . $e->getMessage());
}

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION
     ========================================================================== -->
<section class="products-hero" id="shop-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="products-hero-badge">Reiki Charged Crystals</span>
        <h1 class="products-hero-title">Crystal <em>Shop</em></h1>
        <p class="products-hero-subtext">
            Explore our collection of authentic natural gemstones, chakra balancing bracelets, and spiritual energy tools — 100% cleansed with white sage and charged by our Reiki Masters.
        </p>
    </div>
</section>

<!-- ==========================================================================
     2. PRODUCTS CATALOG SECTION (FILTER, SORT & GRID)
     ========================================================================== -->
<section class="products-catalog-section" id="catalog">
    <div class="container">

        <!-- Filter & Sort Bar -->
        <div class="filter-sort-bar animate-on-scroll">
            <!-- Category Filter Pills -->
            <div class="category-pill-group">
                <a href="?category=all&sort=<?php echo urlencode($sort); ?>" class="category-pill <?php echo ($category === 'all') ? 'active' : ''; ?>">All Products</a>
                <?php foreach ($activeCategories as $catItem): 
                    $catSlug = !empty($catItem['slug']) ? $catItem['slug'] : strtolower(preg_replace('/[^a-z0-9]+/i', '-', $catItem['name']));
                    $isCurrentActive = (
                        strtolower($category) === strtolower($catSlug) || 
                        strtolower($category) === strtolower($catItem['name']) || 
                        str_replace('-', ' ', strtolower($category)) === strtolower($catItem['name'])
                    );
                ?>
                    <a href="?category=<?php echo urlencode($catSlug); ?>&sort=<?php echo urlencode($sort); ?>" class="category-pill <?php echo $isCurrentActive ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($catItem['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Sort Dropdown Form -->
            <form method="GET" action="" class="sort-group" id="sort-form">
                <?php if ($category !== 'all'): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <?php endif; ?>
                <label for="sort" class="sort-label">Sort By:</label>
                <select name="sort" id="sort" class="sort-dropdown-select" onchange="document.getElementById('sort-form').submit();">
                    <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest Arrivals</option>
                    <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </form>
        </div>

        <!-- 4-Column Product Grid -->
        <div class="products-catalog-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <a href="<?php echo BASE_URL; ?>product/<?php echo htmlspecialchars($prod['slug']); ?>" class="product-shop-card animate-on-scroll">
                        <div class="product-img-frame">
                            <?php if (!empty($prod['image'])): ?>
                                <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; min-height: 220px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(201,168,76,0.06), rgba(99,102,241,0.06));">
                                    <span style="font-size: 2.5rem; opacity: 0.8;">💎</span>
                                </div>
                            <?php endif; ?>
                            <?php if (!$prod['in_stock']): ?>
                                <span class="badge badge-danger shop-badge-pos">Out of Stock</span>
                            <?php elseif ($prod['discount_percent'] > 0): ?>
                                <span class="badge badge-gold shop-badge-pos"><?php echo htmlspecialchars($prod['discount_percent']); ?>% OFF ✦ <?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php elseif (!empty($prod['badge_text'])): ?>
                                <span class="badge shop-badge-pos"><?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-shop-info">
                            <div>
                                <span class="product-cat-tag"><?php echo htmlspecialchars($prod['category']); ?></span>
                                <h3 class="product-item-name"><?php echo htmlspecialchars($prod['title']); ?></h3>
                            </div>
                            <div class="product-price-flex">
                                <span class="price-current">₹<?php echo number_format($prod['price'], 2); ?></span>
                                <?php if ($prod['original_price']): ?>
                                    <span class="price-old">₹<?php echo number_format($prod['original_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 0;">
                    <p style="font-size: 1.1rem; color: var(--muted-gray);">No crystal products found matching your current filter.</p>
                    <a href="?category=all" class="btn-primary" style="margin-top: 16px;">View All Products</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <!-- Previous Page Link -->
                <a href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo ($page - 1); ?>" 
                   class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" aria-label="Previous Page">
                    ←
                </a>

                <!-- Page Number Links -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>" 
                       class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next Page Link -->
                <a href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo ($page + 1); ?>" 
                   class="pagination-link <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" aria-label="Next Page">
                    →
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ==========================================================================
     3. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
