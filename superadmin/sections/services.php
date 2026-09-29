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
$defaultServicesBadge = array_key_exists('home_services_badge', $siteSettings) ? trim((string)$siteSettings['home_services_badge']) : 'Holistic Healing Modalities';
$defaultServicesTitle = array_key_exists('home_services_title', $siteSettings) ? trim((string)$siteSettings['home_services_title']) : 'Our Core <em>Services</em>';
$defaultServicesDesc  = array_key_exists('home_services_desc', $siteSettings) ? trim((string)$siteSettings['home_services_desc']) : 'Experience personalized Reiki healing, chakra alignment, and aura cleansing guided by Grandmaster Anupama Agrawal to restore physical vitality and spiritual harmony.';

$servicesBadge = isset($getSecContent) ? $getSecContent('services', 'badge', $defaultServicesBadge) : $defaultServicesBadge;
$servicesTitle = isset($getSecContent) ? $getSecContent('services', 'title', $defaultServicesTitle) : $defaultServicesTitle;
$servicesDesc  = isset($getSecContent) ? $getSecContent('services', 'desc', $defaultServicesDesc) : $defaultServicesDesc;

$servicesSubtextMargin = (($secAlign ?? 'left') === 'center') ? 'margin: 8px auto 0;' : ((($secAlign ?? 'left') === 'right') ? 'margin: 8px 0 0 auto;' : 'margin-top: 8px;');
?>
        <div class="section-header-flex animate-on-scroll" <?php echo $alignStyle; ?>>
            <div>
                <?php if (!empty(trim((string)$servicesBadge))): ?>
                <span class="section-label"><?php echo htmlspecialchars($servicesBadge); ?></span>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$servicesTitle)))): ?>
                <h2 class="section-heading"><?php echo $servicesTitle; ?></h2>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$servicesDesc)))): ?>
                    <p class="section-subtext" style="color: var(--text-muted, #555D6E); font-size: 0.98rem; line-height: 1.6; max-width: 680px; <?php echo $servicesSubtextMargin; ?> margin-bottom: 0;"><?php echo htmlspecialchars($servicesDesc); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>services" class="view-all-link">View all services →</a>
        </div>

        <!-- Horizontal Scrollable Service Cards -->
        <div class="scroll-cards-row">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <?php 
                    $hasSImg = !empty($service['image']);
                    $sImgUrl = $hasSImg ? ((strpos($service['image'], 'http://') === 0 || strpos($service['image'], 'https://') === 0) ? $service['image'] : BASE_URL . ltrim($service['image'], '/')) : '';
                    ?>
                    <div class="scroll-card">
                        <?php if ($hasSImg): ?>
                        <div class="card-img-box">
                            <a href="<?php echo BASE_URL; ?>service/<?php echo htmlspecialchars($service['slug']); ?>">
                                <img src="<?php echo htmlspecialchars($sImgUrl); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" loading="lazy">
                            </a>
                            <?php if ($service['is_free']): ?>
                                <span class="badge badge-green card-badge-pos">FREE • <?php echo htmlspecialchars($service['mode'] ?? 'Online'); ?></span>
                            <?php else: ?>
                                <span class="badge card-badge-pos"><?php echo htmlspecialchars($service['duration_minutes']); ?> mins • <?php echo htmlspecialchars($service['mode'] ?? 'Online'); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="card-body-content" style="<?php echo !$hasSImg ? 'padding-top: 24px;' : ''; ?>">
                            <div>
                                <?php if (!$hasSImg): ?>
                                    <div style="margin-bottom: 10px;">
                                        <?php if ($service['is_free']): ?>
                                            <span class="badge badge-green" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 4px;">FREE • <?php echo htmlspecialchars($service['mode'] ?? 'Online'); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-gold" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 4px;"><?php echo htmlspecialchars($service['duration_minutes']); ?> mins • <?php echo htmlspecialchars($service['mode'] ?? 'Online'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="card-repeat-title"><?php echo htmlspecialchars($service['title']); ?></span>
                                <h3 class="card-main-title">
                                    <a href="<?php echo BASE_URL; ?>service/<?php echo htmlspecialchars($service['slug']); ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($service['title']); ?>
                                    </a>
                                </h3>
                                <p class="card-desc-text"><?php echo htmlspecialchars($service['short_description']); ?></p>
                            </div>
                            <div class="card-footer-meta">
                                <div>
                                    <?php if ($hidePrices): ?>
                                        <span class="card-price-value" style="font-size: 0.85rem; color: var(--gold);">Contact for Price</span>
                                    <?php elseif ($service['is_free'] || $service['price'] === null || (float)$service['price'] <= 0): ?>
                                        <span class="card-price-value" style="color: var(--soft-green-text);">Free</span>
                                    <?php else: ?>
                                        <span class="card-price-value">₹<?php echo number_format($service['price'], 0); ?></span>
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
