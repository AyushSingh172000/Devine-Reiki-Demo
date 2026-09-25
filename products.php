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

// Build Query
$whereClause = "WHERE is_active = 1";
$params = [];

if ($category !== 'all' && !empty($category)) {
    $whereClause .= " AND category LIKE ?";
    $params[] = '%' . $category . '%';
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
                <a href="?category=bracelets&sort=<?php echo urlencode($sort); ?>" class="category-pill <?php echo ($category === 'bracelets') ? 'active' : ''; ?>">Bracelets</a>
                <a href="?category=pendulums&sort=<?php echo urlencode($sort); ?>" class="category-pill <?php echo ($category === 'pendulums') ? 'active' : ''; ?>">Pendulums</a>
                <a href="?category=stones&sort=<?php echo urlencode($sort); ?>" class="category-pill <?php echo ($category === 'stones') ? 'active' : ''; ?>">Stones</a>
                <a href="?category=hangings&sort=<?php echo urlencode($sort); ?>" class="category-pill <?php echo ($category === 'hangings') ? 'active' : ''; ?>">Hangings</a>
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
                            <img src="<?php echo htmlspecialchars($prod['image'] ?: 'assets/images/products/amethyst-bracelet.jpg'); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" loading="lazy">
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
