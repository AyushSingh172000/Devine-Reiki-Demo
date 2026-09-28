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
$defaultProductsDesc = 'Energized astrological bracelets, natural healing crystals, and sacred gemstone artifacts charged with high-frequency Reiki symbols to amplify protection, prosperity, and peace.';
$productsBadge = isset($getSecContent) ? $getSecContent('products', 'badge', 'Sacred Crystal Energy') : 'Sacred Crystal Energy';
$productsTitle = isset($getSecContent) ? $getSecContent('products', 'title', 'Featured Reiki Charged <em>Products</em>') : 'Featured Reiki Charged <em>Products</em>';
$productsDesc  = isset($getSecContent) ? $getSecContent('products', 'desc', $defaultProductsDesc) : $defaultProductsDesc;

$productsSubtextMargin = (($secAlign ?? 'left') === 'center') ? 'margin: 8px auto 0;' : ((($secAlign ?? 'left') === 'right') ? 'margin: 8px 0 0 auto;' : 'margin-top: 8px;');
?>
        <div class="section-header-flex animate-on-scroll" <?php echo $productsAlignStyle; ?>>
            <div>
                <span class="section-label"><?php echo htmlspecialchars($productsBadge); ?></span>
                <h2 class="section-heading"><?php echo $productsTitle; ?></h2>
                <?php if (!empty($productsDesc)): ?>
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
                            <img src="<?php echo htmlspecialchars($prod['image'] ?: 'assets/images/products/amethyst-bracelet.jpg'); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" loading="lazy">
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
