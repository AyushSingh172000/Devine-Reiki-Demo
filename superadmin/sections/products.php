<?php
// Section 5: Products Section
$productsAlignStyle = '';
if (($secAlign ?? 'left') === 'center') {
    $productsAlignStyle = 'style="text-align: center; justify-content: center; flex-direction: column; align-items: center; gap: 14px;"';
} elseif (($secAlign ?? 'left') === 'right') {
    $productsAlignStyle = 'style="text-align: right; justify-content: flex-end; flex-direction: row-reverse;"';
}

$hidePrices = !empty($saToggles['hide_prices']);
?>
<section class="products-section" id="products">
    <div class="container">
<?php
$defaultProductsBadge = array_key_exists('home_products_badge', $siteSettings) ? trim((string)$siteSettings['home_products_badge']) : 'Sacred Crystal Energy';
$defaultProductsTitle = array_key_exists('home_products_title', $siteSettings) ? trim((string)$siteSettings['home_products_title']) : 'Featured Reiki Charged <em>Products</em>';
$defaultProductsDesc  = array_key_exists('home_products_desc', $siteSettings) ? trim((string)$siteSettings['home_products_desc']) : 'Energized astrological bracelets, natural healing crystals, and sacred gemstone artifacts charged with high-frequency Reiki symbols to amplify protection, prosperity, and peace.';

$productsBadge = isset($getSecContent) ? $getSecContent('products', 'badge', $defaultProductsBadge) : $defaultProductsBadge;
$productsTitle = isset($getSecContent) ? $getSecContent('products', 'title', $defaultProductsTitle) : $defaultProductsTitle;
$productsDesc  = isset($getSecContent) ? $getSecContent('products', 'desc', $defaultProductsDesc) : $defaultProductsDesc;

$productsSubtextMargin = (($secAlign ?? 'left') === 'center') ? 'margin: 8px auto 0;' : ((($secAlign ?? 'left') === 'right') ? 'margin: 8px 0 0 auto;' : 'margin-top: 8px;');
?>
        <div class="section-header-flex animate-on-scroll" <?php echo $productsAlignStyle; ?>>
            <div>
                <?php if (!empty(trim((string)$productsBadge))): ?>
                <span class="section-label"><?php echo htmlspecialchars($productsBadge); ?></span>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$productsTitle)))): ?>
                <h2 class="section-heading"><?php echo $productsTitle; ?></h2>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$productsDesc)))): ?>
                    <p class="section-subtext" style="color: var(--text-muted, #555D6E); font-size: 0.98rem; line-height: 1.6; max-width: 680px; <?php echo $productsSubtextMargin; ?> margin-bottom: 0;"><?php echo htmlspecialchars($productsDesc); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>products" class="view-all-link">View all products →</a>
        </div>

        <!-- Horizontal Scrollable Product Cards -->
        <div class="scroll-cards-row products-scroll-row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <a href="<?php echo BASE_URL; ?>product/<?php echo htmlspecialchars($prod['slug']); ?>" class="product-card-item animate-on-scroll">
                        <div class="product-img-box">
                            <?php if (!empty($prod['image'])): ?>
                                <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(201,168,76,0.06), rgba(99,102,241,0.06));">
                                    <span style="font-size: 2.2rem; opacity: 0.8;">💎</span>
                                </div>
                            <?php endif; ?>
                            <?php if (!$prod['in_stock']): ?>
                                <span class="badge badge-danger product-badge-tag">Out of Stock</span>
                            <?php elseif ($prod['discount_percent'] > 0): ?>
                                <span class="badge badge-gold product-badge-tag"><?php echo htmlspecialchars($prod['discount_percent']); ?>% OFF ✦ <?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php elseif (!empty($prod['badge_text'])): ?>
                                <span class="badge product-badge-tag"><?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-details-body">
                            <div>
                                <span class="product-category-name"><?php echo htmlspecialchars($prod['category']); ?></span>
                                <h3 class="product-title-text"><?php echo htmlspecialchars($prod['title']); ?></h3>
                            </div>
                            <div class="product-price-row">
                                <?php if ($hidePrices): ?>
                                    <span class="sale-price" style="font-size: 0.9rem;">Contact for Price</span>
                                <?php else: ?>
                                    <span class="sale-price">₹<?php echo number_format($prod['price'], 2); ?></span>
                                    <?php if ($prod['original_price']): ?>
                                        <span class="original-price">₹<?php echo number_format($prod['original_price'], 2); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products currently listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
