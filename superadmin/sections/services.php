<?php
// Section 2: Services & 7 Chakras
$alignStyle = '';
if (($secAlign ?? 'left') === 'center') {
    $alignStyle = 'style="text-align: center; justify-content: center; flex-direction: column; align-items: center; gap: 14px;"';
} elseif (($secAlign ?? 'left') === 'right') {
    $alignStyle = 'style="text-align: right; justify-content: flex-end; flex-direction: row-reverse;"';
}

$hidePrices = !empty($saToggles['hide_prices']);
$disableBookings = !empty($saToggles['disable_bookings']);
$serviceBookingUrl = $disableBookings ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $siteSettings['whatsapp'] ?? '919426895692') : BOOKING_URL;
?>
<section class="services-section" id="services">
    <div id="chakras" style="position: relative; top: -80px; visibility: hidden;"></div>
    <div class="container">
<?php
$servicesBadge = isset($getSecContent) ? $getSecContent('services', 'badge', 'Holistic Healing Modalities') : 'Holistic Healing Modalities';
$servicesTitle = isset($getSecContent) ? $getSecContent('services', 'title', 'Our Core <em>Services</em>') : 'Our Core <em>Services</em>';
$servicesDesc  = isset($getSecContent) ? $getSecContent('services', 'desc', '') : '';
?>
        <div class="section-header-flex animate-on-scroll" <?php echo $alignStyle; ?>>
            <div>
                <span class="section-label"><?php echo htmlspecialchars($servicesBadge); ?></span>
                <h2 class="section-heading"><?php echo $servicesTitle; ?></h2>
                <?php if (!empty($servicesDesc)): ?>
                    <p class="section-subtext" style="color: var(--text-muted); font-size: 0.95rem; margin-top: 6px;"><?php echo htmlspecialchars($servicesDesc); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>services" class="view-all-link">View all services →</a>
        </div>

        <!-- Horizontal Scrollable Service Cards -->
        <div class="scroll-cards-row">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <div class="scroll-card">
                        <div class="card-img-box">
                            <img src="<?php echo htmlspecialchars($service['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" loading="lazy">
                            <?php if ($service['is_free']): ?>
                                <span class="badge badge-green card-badge-pos">FREE SESSION</span>
                            <?php else: ?>
                                <span class="badge card-badge-pos"><?php echo htmlspecialchars($service['duration_minutes']); ?> mins</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body-content">
                            <div>
                                <span class="card-repeat-title"><?php echo htmlspecialchars($service['title']); ?></span>
                                <h3 class="card-main-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="card-desc-text"><?php echo htmlspecialchars($service['short_description']); ?></p>
                            </div>
                            <div class="card-footer-meta">
                                <div>
                                    <?php if ($hidePrices): ?>
                                        <span class="card-price-value" style="font-size: 0.85rem; color: var(--gold);">Contact for Price</span>
                                    <?php elseif ($service['is_free'] || $service['price'] === null): ?>
                                        <span class="card-price-value" style="color: var(--soft-green-text);">Free</span>
                                    <?php else: ?>
                                        <span class="card-price-value">₹<?php echo number_format($service['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo htmlspecialchars($serviceBookingUrl); ?>" target="_blank" rel="noopener" class="btn-primary btn-book-nav">Book Session →</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No services currently listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
