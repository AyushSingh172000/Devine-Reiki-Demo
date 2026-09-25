<?php
// Product Detail Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

$slug = $_GET['slug'] ?? '';

// Fetch product by slug
$product = null;
if (!empty($slug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in product-detail.php: " . $e->getMessage());
    }
}

// Fallback if product not found
if (!$product) {
    try {
        $fallbackStmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
        $product = $fallbackStmt ? $fallbackStmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (PDOException $e) {
        $product = null;
    }
}

if (!$product) {
    header("Location: " . BASE_URL . "products.php");
    exit;
}

// Additional images parsing
$mainImage = $product['image'] ?: 'assets/images/products/amethyst-bracelet.jpg';
$additionalImages = is_string($product['additional_images']) ? json_decode($product['additional_images'], true) : $product['additional_images'];
$galleryImages = array_unique(array_filter(array_merge([$mainImage], is_array($additionalImages) ? $additionalImages : [])));

// Fetch related products
$relatedProducts = [];
try {
    $relStmt = $pdo->prepare("SELECT * FROM products WHERE id != ? AND is_active = 1 ORDER BY sort_order ASC LIMIT 4");
    $relStmt->execute([$product['id']]);
    $relatedProducts = $relStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching related products: " . $e->getMessage());
}

// WhatsApp pre-filled order message
$waText = "Hello Reiki Bliss, I would like to order the " . $product['title'] . " (Price: ₹" . number_format($product['price'], 2) . "). Please assist me with payment and delivery details.";
$waOrderUrl = "https://wa.me/919971655705?text=" . urlencode($waText);

// Page Metadata
$pageTitle = htmlspecialchars($product['title']) . " | Reiki Charged Crystal Shop";
$pageDescription = htmlspecialchars($product['short_description']);
$pageOgImage = $mainImage;

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="container">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="breadcrumb-separator">›</span>
        <a href="<?php echo BASE_URL; ?>products">Shop</a>
        <span class="breadcrumb-separator">›</span>
        <span><?php echo htmlspecialchars($product['title']); ?></span>
    </nav>
</div>

<!-- Product Detail Section -->
<section class="product-detail-section" id="product-detail">
    <div class="container">
        
        <div class="product-detail-grid animate-on-scroll">
            <!-- Left Column: Image Gallery -->
            <div class="gallery-container">
                <div class="main-img-box" style="position: relative;">
                    <img id="main-product-display" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" loading="lazy">
                    <?php if (!$product['in_stock']): ?>
                        <span class="badge badge-danger" style="position: absolute; top: 16px; left: 16px; font-size: 0.85rem; padding: 6px 14px; box-shadow: 0 4px 12px rgba(220,38,38,0.25); z-index: 2;">Out of Stock</span>
                    <?php endif; ?>
                </div>

                <?php if (count($galleryImages) > 1): ?>
                    <div class="thumbnails-row">
                        <?php foreach ($galleryImages as $idx => $imgUrl): ?>
                            <div class="thumb-item <?php echo ($idx === 0) ? 'active' : ''; ?>" onclick="switchProductImage('<?php echo htmlspecialchars($imgUrl); ?>', this)">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Thumbnail <?php echo $idx + 1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Product Details & WhatsApp Order -->
            <div class="product-info-column">
                <div>
                    <span class="product-cat-tag"><?php echo htmlspecialchars($product['category']); ?></span>
                    <h1 class="product-detail-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <div style="margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                        <?php if ($product['discount_percent'] > 0): ?>
                            <span class="badge badge-gold"><?php echo htmlspecialchars($product['discount_percent']); ?>% OFF<?php if (!empty($product['badge_text'])) echo ' ✦ ' . htmlspecialchars($product['badge_text']); ?></span>
                        <?php elseif (!empty($product['badge_text'])): ?>
                            <span class="badge"><?php echo htmlspecialchars($product['badge_text']); ?></span>
                        <?php endif; ?>

                        <?php if ($product['in_stock']): ?>
                            <span class="badge badge-green">In Stock &amp; Ready to Ship</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-detail-prices">
                        <span class="detail-sale-price">₹<?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($product['original_price']): ?>
                            <span class="detail-orig-price">₹<?php echo number_format($product['original_price'], 2); ?></span>
                        <?php endif; ?>
                    </div>

                    <p style="font-size: 1.05rem; font-weight: 500; color: var(--dark-text); margin-bottom: 20px; line-height: 1.6;">
                        <?php echo htmlspecialchars($product['short_description']); ?>
                    </p>

                    <div style="font-size: 0.95rem; color: var(--muted-gray); line-height: 1.7; margin-bottom: 30px;">
                        <?php 
                        $paragraphs = explode("\n", $product['full_description']);
                        foreach ($paragraphs as $para) {
                            $para = trim($para);
                            if (!empty($para)) {
                                echo '<p style="margin-bottom: 12px;">' . htmlspecialchars($para) . '</p>';
                            }
                        }
                        ?>
                    </div>

                    <ul style="list-style: none; padding: 0; margin-bottom: 32px; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: var(--dark-text);">
                        <li><span style="color: var(--accent-gold-dark);">✓</span> 100% Authentic Natural Gemstones</li>
                        <li><span style="color: var(--accent-gold-dark);">✓</span> Sage Cleansed & Reiki Master Charged</li>
                        <li><span style="color: var(--accent-gold-dark);">✓</span> Includes Energized Protection Pouch</li>
                    </ul>
                </div>

                <!-- Order via WhatsApp Action Button -->
                <div>
                    <?php if ($product['in_stock']): ?>
                        <a href="<?php echo htmlspecialchars($waOrderUrl); ?>" target="_blank" rel="noopener" class="btn-whatsapp-order">
                            Order via WhatsApp 💬
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn-whatsapp-order disabled" disabled title="This product is currently out of stock">
                            Currently Out of Stock ✕
                        </button>
                        <p style="font-size: 0.88rem; color: #dc2626; margin-top: 10px; text-align: center; font-weight: 500;">
                            ⚠️ This item is currently out of stock and cannot be ordered at this time.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Related Products Section -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-products-wrapper animate-on-scroll">
                <h3 class="section-heading" style="font-size: 2rem; margin-bottom: 30px;">Related Crystal <em>Products</em></h3>
                <div class="products-catalog-grid">
                    <?php foreach ($relatedProducts as $rel): ?>
                        <a href="<?php echo BASE_URL; ?>product/<?php echo htmlspecialchars($rel['slug']); ?>" class="product-shop-card">
                            <div class="product-img-frame">
                                <img src="<?php echo htmlspecialchars($rel['image'] ?: 'assets/images/products/amethyst-bracelet.jpg'); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy">
                                <?php if (!$rel['in_stock']): ?>
                                    <span class="badge badge-danger shop-badge-pos">Out of Stock</span>
                                <?php elseif ($rel['discount_percent'] > 0): ?>
                                    <span class="badge badge-gold shop-badge-pos"><?php echo htmlspecialchars($rel['discount_percent']); ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-shop-info">
                                <div>
                                    <span class="product-cat-tag"><?php echo htmlspecialchars($rel['category']); ?></span>
                                    <h4 class="product-item-name" style="font-size: 1.05rem;"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                </div>
                                <div class="product-price-flex">
                                    <span class="price-current">₹<?php echo number_format($rel['price'], 2); ?></span>
                                    <?php if ($rel['original_price']): ?>
                                        <span class="price-old">₹<?php echo number_format($rel['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Gallery Thumbnail Switcher Script -->
<script>
function switchProductImage(src, thumbElement) {
  const mainImg = document.getElementById('main-product-display');
  if (mainImg) {
    mainImg.src = src;
  }
  const thumbs = document.querySelectorAll('.thumb-item');
  thumbs.forEach(t => t.classList.remove('active'));
  if (thumbElement) {
    thumbElement.classList.add('active');
  }
}
</script>

<!-- Reusable CTA Section -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
