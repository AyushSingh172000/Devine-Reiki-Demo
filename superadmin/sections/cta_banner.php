<?php
// Section: Healing Journey CTA Banner (Adjustable via Super Admin)
$GLOBALS['cta_banner_rendered'] = true;

// Default values matching Screenshot 1 & 2
$defaultTag     = !empty($siteSettings['footer_cta_tag']) ? $siteSettings['footer_cta_tag'] : 'FIRST SESSION IS FREE';
$defaultTitle   = !empty($siteSettings['footer_cta_title']) ? $siteSettings['footer_cta_title'] : 'Begin Your <em>Healing Journey</em> Today';
$defaultDesc    = !empty($siteSettings['footer_cta_desc']) ? $siteSettings['footer_cta_desc'] : 'Take the first step. Meet Ms Anupama Agrawal and discover which modality resonates with your soul.';
$defaultBgImage = !empty($siteSettings['footer_cta_bg_image']) ? $siteSettings['footer_cta_bg_image'] : 'assets/images/cta-bg.jpg';
$defaultBtnText = !empty($siteSettings['footer_cta_btn_text']) ? $siteSettings['footer_cta_btn_text'] : 'Book Free Session →';

$bookingUrl    = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');
$defaultBtnUrl = !empty($siteSettings['footer_cta_btn_url']) ? $siteSettings['footer_cta_btn_url'] : $bookingUrl;

// Super Admin Dynamic Overrides from Homepage Manager
if (isset($getSecContent)) {
    $ctaTag     = $getSecContent('cta_banner', 'badge', $defaultTag);
    $ctaTitle   = $getSecContent('cta_banner', 'title', $defaultTitle);
    $ctaDesc    = $getSecContent('cta_banner', 'desc', $defaultDesc);
    $ctaBgImage = $getSecContent('cta_banner', 'bg_image', $defaultBgImage);
    $ctaBtnText = $getSecContent('cta_banner', 'btn_text', $defaultBtnText);
    $ctaBtnUrl  = $getSecContent('cta_banner', 'btn_url', $defaultBtnUrl);
} else {
    $layoutArr = !empty($siteSettings['superadmin_homepage_layout']) ? json_decode($siteSettings['superadmin_homepage_layout'], true) : [];
    $ctaSecData = [];
    if (is_array($layoutArr)) {
        foreach ($layoutArr as $sec) {
            if (!empty($sec['id']) && $sec['id'] === 'cta_banner') {
                $ctaSecData = $sec;
                break;
            }
        }
    }
    $ctaTag     = !empty($ctaSecData['badge']) ? $ctaSecData['badge'] : $defaultTag;
    $ctaTitle   = !empty($ctaSecData['title']) ? $ctaSecData['title'] : $defaultTitle;
    $ctaDesc    = !empty($ctaSecData['desc']) ? $ctaSecData['desc'] : $defaultDesc;
    $ctaBgImage = !empty($ctaSecData['bg_image']) ? $ctaSecData['bg_image'] : $defaultBgImage;
    $ctaBtnText = !empty($ctaSecData['btn_text']) ? $ctaSecData['btn_text'] : $defaultBtnText;
    $ctaBtnUrl  = !empty($ctaSecData['btn_url']) ? $ctaSecData['btn_url'] : $defaultBtnUrl;
    if (!isset($secAlign) && !empty($ctaSecData['align'])) {
        $secAlign = $ctaSecData['align'];
    }
}

if (empty($ctaBgImage)) {
    $ctaBgImage = 'assets/images/cta-bg.jpg';
}

// Super Admin alignment handling (default center matching Screenshot 1)
$currentAlign = $secAlign ?? 'center';
?>
<section class="homepage-cta-section" id="healing-cta-banner" style="padding: 40px 0 30px;">
    <div class="container">
        <div class="cta-journey-box animate-on-scroll" style="position: relative; border-radius: 24px; padding: 58px 48px; background: linear-gradient(rgba(14, 10, 8, 0.72), rgba(14, 10, 8, 0.78)), url('<?php echo htmlspecialchars($ctaBgImage); ?>') center center / cover no-repeat; overflow: hidden; border: 1px solid rgba(200, 155, 60, 0.32); box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18);">
            <!-- Subtle Golden Ambient Glow in the center -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 560px; height: 320px; background: radial-gradient(circle, rgba(200, 155, 60, 0.15) 0%, rgba(0,0,0,0) 70%); pointer-events: none; z-index: 1;"></div>

            <?php if ($currentAlign === 'center'): ?>
                <!-- Centered Layout (Matching Screenshot 1 inside the box) -->
                <div style="position: relative; z-index: 2; max-width: 780px; margin: 0 auto; text-align: center;">
                    <span class="cta-journey-tag" style="display: inline-block; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #D4A853; margin-bottom: 14px;">
                        <?php echo htmlspecialchars($ctaTag); ?>
                    </span>

                    <h2 class="cta-journey-heading" style="font-family: var(--font-serif, 'Playfair Display', Georgia, serif); font-size: clamp(1.9rem, 3.8vw, 2.75rem); font-weight: 500; color: #ffffff; line-height: 1.25; margin-bottom: 16px; letter-spacing: -0.01em;">
                        <?php echo $ctaTitle; ?>
                    </h2>

                    <p class="cta-journey-desc" style="color: rgba(255, 255, 255, 0.88); font-size: 1.05rem; line-height: 1.65; max-width: 660px; margin: 0 auto 32px;">
                        <?php echo htmlspecialchars($ctaDesc); ?>
                    </p>

                    <div class="cta-journey-action" style="display: flex; justify-content: center; align-items: center;">
                        <a href="<?php echo htmlspecialchars($ctaBtnUrl); ?>" class="btn-gold cta-journey-btn" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 15px 38px; font-size: 1.02rem; font-weight: 600; color: #ffffff; background: linear-gradient(135deg, #d89e2e 0%, #b8821f 50%, #996810 100%); border: none; border-radius: 50px; text-decoration: none; box-shadow: 0 8px 24px rgba(184, 130, 31, 0.35); transition: all 0.3s ease;">
                            <?php echo htmlspecialchars($ctaBtnText); ?>
                        </a>
                    </div>
                </div>
            <?php elseif ($currentAlign === 'right'): ?>
                <!-- Right Aligned Flex Layout -->
                <div style="position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 32px; flex-wrap: wrap; flex-direction: row-reverse;">
                    <div style="flex: 1; min-width: 280px; text-align: right;">
                        <span class="cta-journey-tag" style="display: inline-block; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #D4A853; margin-bottom: 12px;">
                            <?php echo htmlspecialchars($ctaTag); ?>
                        </span>
                        <h2 class="cta-journey-heading" style="font-family: var(--font-serif, 'Playfair Display', Georgia, serif); font-size: clamp(1.8rem, 3.4vw, 2.5rem); font-weight: 500; color: #ffffff; line-height: 1.25; margin-bottom: 14px;">
                            <?php echo $ctaTitle; ?>
                        </h2>
                        <p class="cta-journey-desc" style="color: rgba(255, 255, 255, 0.88); font-size: 1.02rem; line-height: 1.65; max-width: 620px; margin-left: auto; margin-bottom: 0;">
                            <?php echo htmlspecialchars($ctaDesc); ?>
                        </p>
                    </div>
                    <div class="cta-journey-action" style="flex-shrink: 0;">
                        <a href="<?php echo htmlspecialchars($ctaBtnUrl); ?>" class="btn-gold cta-journey-btn" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 15px 36px; font-size: 1rem; font-weight: 600; color: #ffffff; background: linear-gradient(135deg, #d89e2e 0%, #b8821f 50%, #996810 100%); border: none; border-radius: 50px; text-decoration: none; box-shadow: 0 8px 24px rgba(184, 130, 31, 0.35); transition: all 0.3s ease;">
                            <?php echo htmlspecialchars($ctaBtnText); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Left Aligned Flex Layout (Side-by-side like Screenshot 2 with dark spiritual BG) -->
                <div style="position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 32px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px; text-align: left;">
                        <span class="cta-journey-tag" style="display: inline-block; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #D4A853; margin-bottom: 12px;">
                            <?php echo htmlspecialchars($ctaTag); ?>
                        </span>
                        <h2 class="cta-journey-heading" style="font-family: var(--font-serif, 'Playfair Display', Georgia, serif); font-size: clamp(1.8rem, 3.4vw, 2.5rem); font-weight: 500; color: #ffffff; line-height: 1.25; margin-bottom: 14px;">
                            <?php echo $ctaTitle; ?>
                        </h2>
                        <p class="cta-journey-desc" style="color: rgba(255, 255, 255, 0.88); font-size: 1.02rem; line-height: 1.65; max-width: 620px; margin-bottom: 0;">
                            <?php echo htmlspecialchars($ctaDesc); ?>
                        </p>
                    </div>
                    <div class="cta-journey-action" style="flex-shrink: 0;">
                        <a href="<?php echo htmlspecialchars($ctaBtnUrl); ?>" class="btn-gold cta-journey-btn" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 15px 36px; font-size: 1rem; font-weight: 600; color: #ffffff; background: linear-gradient(135deg, #d89e2e 0%, #b8821f 50%, #996810 100%); border: none; border-radius: 50px; text-decoration: none; box-shadow: 0 8px 24px rgba(184, 130, 31, 0.35); transition: all 0.3s ease;">
                            <?php echo htmlspecialchars($ctaBtnText); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<style>
#healing-cta-banner .cta-journey-heading em {
    font-style: italic;
    color: #D4A853;
    font-weight: 400;
}
#healing-cta-banner .cta-journey-btn:hover {
    background: linear-gradient(135deg, #e5ab37 0%, #c89128 50%, #ad7818 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(216, 158, 46, 0.48) !important;
    color: #ffffff !important;
}
@media (max-width: 768px) {
    #healing-cta-banner .cta-journey-box {
        padding: 36px 20px !important;
        border-radius: 18px !important;
    }
}
</style>
