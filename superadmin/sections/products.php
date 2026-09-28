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
        <div class="section-header-flex animate-on-scroll" <?php echo $productsAlignStyle; ?>>
            <div>
                <span class="section-label">Sacred Crystal Energy</span>
                <h2 class="section-heading">Featured Reiki Charged <em>Products</em></h2>
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
