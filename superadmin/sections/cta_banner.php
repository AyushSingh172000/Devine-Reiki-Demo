<?php
// Section: Healing Journey CTA Banner (Adjustable via Super Admin)
$GLOBALS['cta_banner_rendered'] = true;

$ctaTag = !empty($siteSettings['footer_cta_tag']) ? $siteSettings['footer_cta_tag'] : 'FIRST SESSION IS FREE';
$ctaTitle = !empty($siteSettings['footer_cta_title']) ? $siteSettings['footer_cta_title'] : 'Begin Your <em>Healing Journey</em> Today';
$ctaDesc = !empty($siteSettings['footer_cta_desc']) ? $siteSettings['footer_cta_desc'] : 'Take the first step. Meet Ms Anupama Agrawal and discover which modality resonates with your soul.';
$ctaBtnText = !empty($siteSettings['footer_cta_btn_text']) ? $siteSettings['footer_cta_btn_text'] : 'Book Free Session →';

$bookingUrl = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');
$ctaBtnUrl = !empty($siteSettings['footer_cta_btn_url']) ? $siteSettings['footer_cta_btn_url'] : $bookingUrl;

// Super Admin alignment handling
$bannerAlignStyle = '';
$textAlignStyle = '';
$actionAlignStyle = '';

$currentAlign = $secAlign ?? 'left';
if ($currentAlign === 'center') {
    $bannerAlignStyle = 'flex-direction: column; text-align: center; justify-content: center; gap: 20px;';
    $textAlignStyle = 'text-align: center; margin: 0 auto;';
    $actionAlignStyle = 'display: flex; justify-content: center; width: 100%;';
} elseif ($currentAlign === 'right') {
    $bannerAlignStyle = 'flex-direction: row-reverse; text-align: right;';
    $textAlignStyle = 'text-align: right; margin-left: auto;';
}
?>
<section class="homepage-cta-section" id="healing-cta-banner" style="padding: 40px 0 20px;">
    <div class="container">
        <div class="footer-cta-banner animate-on-scroll" style="margin-bottom: 0; <?php echo $bannerAlignStyle; ?>">
            <div class="footer-cta-text" style="<?php echo $textAlignStyle; ?>">
                <span class="footer-cta-tag"><?php echo htmlspecialchars($ctaTag); ?></span>
                <h3><?php echo $ctaTitle; ?></h3>
                <p><?php echo htmlspecialchars($ctaDesc); ?></p>
            </div>
            <div class="footer-cta-action" style="<?php echo $actionAlignStyle; ?>">
                <a href="<?php echo htmlspecialchars($ctaBtnUrl); ?>" target="_blank" rel="noopener" class="btn-gold"><?php echo htmlspecialchars($ctaBtnText); ?></a>
            </div>
        </div>
    </div>
</section>
