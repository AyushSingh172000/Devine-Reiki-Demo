<?php
// Contact & Consultation Page - Divine Reiki & Energy Healing Center
$currentPage = 'contact.php';
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Pre-filled Service/Course/Product Parameter Handling
$prefilledService = $_GET['service'] ?? '';
$prefilledCourse = $_GET['course'] ?? '';
$prefilledProduct = $_GET['product'] ?? '';
$detectedOrderType = '';
$detectedItemName = '';
$defaultMessage = '';

if (!empty($prefilledProduct)) {
    $detectedOrderType = 'product';
    $productTitle = $prefilledProduct;
    $productPrice = '';
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $pStmt = $pdo->prepare("SELECT title, price FROM products WHERE slug = ? LIMIT 1");
            $pStmt->execute([$prefilledProduct]);
            $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);
            if ($pRow && !empty($pRow['title'])) {
                $productTitle = $pRow['title'];
                if (!empty($pRow['price'])) {
                    $productPrice = ' (Price: ₹' . number_format($pRow['price'], 2) . ')';
                }
            } else {
                $productTitle = ucwords(str_replace('-', ' ', $prefilledProduct));
            }
        } catch (PDOException $e) {
            $productTitle = ucwords(str_replace('-', ' ', $prefilledProduct));
        }
    }
    $detectedItemName = $productTitle . $productPrice;
    $defaultMessage = "Hello Reiki Bliss, I would like to order the " . $productTitle . $productPrice . ". Please assist me with payment and delivery details.";
} elseif (!empty($prefilledCourse)) {
    $detectedOrderType = 'course';
    $courseName = $prefilledCourse;
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $cStmt = $pdo->prepare("SELECT title FROM courses WHERE slug = ? LIMIT 1");
            $cStmt->execute([$prefilledCourse]);
            $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
            if ($cRow && !empty($cRow['title'])) {
                $courseName = $cRow['title'];
            } else {
                $courseName = ucwords(str_replace('-', ' ', $prefilledCourse));
            }
        } catch (PDOException $e) {
            $courseName = ucwords(str_replace('-', ' ', $prefilledCourse));
        }
    }
    $detectedItemName = $courseName;
    $defaultMessage = "Hello Reiki Bliss, I am interested in enrolling in the " . $courseName . " certification course.";
} elseif (!empty($prefilledService)) {
    $defaultMessage = "Hello, I would like to book a session for " . htmlspecialchars($prefilledService) . ".";
}

if (!empty($_GET['success'])) {
    $defaultMessage = '';
}

// Page Metadata
$pageTitle = "Contact Us & Book Free Session | Reiki Bliss";
$pageDescription = "Get in touch with Reiki Grandmaster Anupama Agrawal at Reiki Bliss. Book a free 30-minute consultation or send an inquiry.";

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<?php
$contactBookingUrl = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');
$contactPhone = !empty($siteSettings['phone']) ? $siteSettings['phone'] : (defined('SITE_PHONE') ? SITE_PHONE : '');
$contactCleanWa = preg_replace('/[^0-9]/', '', $siteSettings['whatsapp'] ?? (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '919971655705'));
$contactEmail = !empty($siteSettings['email']) ? $siteSettings['email'] : (defined('SITE_EMAIL') ? SITE_EMAIL : '');
$contactAddress = !empty($siteSettings['address']) ? $siteSettings['address'] : (defined('SITE_ADDRESS') ? SITE_ADDRESS : '');
$contactHours = !empty($siteSettings['working_hours']) ? $siteSettings['working_hours'] : (defined('WORKING_HOURS') ? WORKING_HOURS : 'Monday - Saturday: 9:00 AM - 7:00 PM (IST)');
$contactMapsUrl = !empty($siteSettings['maps_url']) ? $siteSettings['maps_url'] : (defined('MAPS_URL') ? MAPS_URL : '#');
$contactMapsEmbed = !empty($contactMapsUrl) ? convertGoogleMapsToEmbedUrl($contactMapsUrl, $contactAddress) : '';
if (empty($contactMapsEmbed)) {
    $contactMapsEmbed = defined('MAPS_EMBED_URL') && !empty(MAPS_EMBED_URL) ? MAPS_EMBED_URL : convertGoogleMapsToEmbedUrl('', $contactAddress);
}

// Contact Hero Section Customization
$contactHeroBadge = array_key_exists('contact_hero_badge', $siteSettings) ? trim((string)$siteSettings['contact_hero_badge']) : 'Free Online Consultation';
$contactHeroTitle = array_key_exists('contact_hero_title', $siteSettings) ? trim((string)$siteSettings['contact_hero_title']) : '30 Minutes with <em>Reiki Masters</em>';

$contactHeroDesc = '';
if (array_key_exists('contact_hero_subtext', $siteSettings)) {
    $contactHeroDesc = trim((string)$siteSettings['contact_hero_subtext']);
} elseif (array_key_exists('contact_hero_desc', $siteSettings)) {
    $contactHeroDesc = trim((string)$siteSettings['contact_hero_desc']);
} else {
    $contactHeroDesc = 'Take the first step toward physical vitality and spiritual peace. Schedule a complimentary 30-minute online video guidance session directly with our certified Reiki Masters.';
}

// Center Info Section & Cards Customization (Falls back to global settings when fields are blank)
$contactSecBadge = !empty(trim((string)($siteSettings['contact_sec_badge'] ?? ''))) ? trim((string)$siteSettings['contact_sec_badge']) : 'Our Sanctuary Location';
$contactSecHeading = !empty(trim((string)($siteSettings['contact_sec_heading'] ?? ''))) ? trim((string)$siteSettings['contact_sec_heading']) : htmlspecialchars($siteSettings['site_name'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Reiki Bliss'));
$contactSecDesc = !empty(trim((string)($siteSettings['contact_sec_desc'] ?? ''))) ? trim((string)$siteSettings['contact_sec_desc']) : 'Visit our peaceful sanctuary or connect with our healing practitioners virtually.';

// Card 1: Visit Us (Falls back to global address and maps URL)
$cCard1Icon = !empty(trim((string)($siteSettings['contact_card1_icon'] ?? ''))) ? trim((string)$siteSettings['contact_card1_icon']) : '📍';
$cCard1Title = !empty(trim((string)($siteSettings['contact_card1_title'] ?? ''))) ? trim((string)$siteSettings['contact_card1_title']) : 'Visit Us';
$cCard1Text = !empty(trim((string)($siteSettings['contact_card1_text'] ?? ''))) ? trim((string)$siteSettings['contact_card1_text']) : $contactAddress;
$cCard1Btn = !empty(trim((string)($siteSettings['contact_card1_btn'] ?? ''))) ? trim((string)$siteSettings['contact_card1_btn']) : 'View on Google Maps →';
$cCard1Url = !empty(trim((string)($siteSettings['contact_card1_url'] ?? ''))) ? trim((string)$siteSettings['contact_card1_url']) : $contactMapsUrl;

// Card 2: Call / WhatsApp (Falls back to global phone, whatsapp, and wa.me link)
$cCard2Icon = !empty(trim((string)($siteSettings['contact_card2_icon'] ?? ''))) ? trim((string)$siteSettings['contact_card2_icon']) : '📞';
$cCard2Title = !empty(trim((string)($siteSettings['contact_card2_title'] ?? ''))) ? trim((string)$siteSettings['contact_card2_title']) : 'Call / WhatsApp';
$cCard2Phone = !empty(trim((string)($siteSettings['contact_card2_phone'] ?? ''))) ? trim((string)$siteSettings['contact_card2_phone']) : $contactPhone;
$cCard2Wa = !empty(trim((string)($siteSettings['contact_card2_wa'] ?? ''))) ? trim((string)$siteSettings['contact_card2_wa']) : (!empty($siteSettings['whatsapp']) ? $siteSettings['whatsapp'] : $contactPhone);
$cCard2Btn = !empty(trim((string)($siteSettings['contact_card2_btn'] ?? ''))) ? trim((string)$siteSettings['contact_card2_btn']) : 'Chat on WhatsApp →';
$cCard2Url = !empty(trim((string)($siteSettings['contact_card2_url'] ?? ''))) ? trim((string)$siteSettings['contact_card2_url']) : (!empty($contactCleanWa) ? ('https://wa.me/' . $contactCleanWa) : '#');

// Card 3: Email Us (Falls back to global email)
$cCard3Icon = !empty(trim((string)($siteSettings['contact_card3_icon'] ?? ''))) ? trim((string)$siteSettings['contact_card3_icon']) : '✉️';
$cCard3Title = !empty(trim((string)($siteSettings['contact_card3_title'] ?? ''))) ? trim((string)$siteSettings['contact_card3_title']) : 'Email Us';
$cCard3Email = !empty(trim((string)($siteSettings['contact_card3_email'] ?? ''))) ? trim((string)$siteSettings['contact_card3_email']) : $contactEmail;
$cCard3Btn = !empty(trim((string)($siteSettings['contact_card3_btn'] ?? ''))) ? trim((string)$siteSettings['contact_card3_btn']) : 'Send Email Inquiry →';

// Card 4: Center Hours (Falls back to global working hours and booking URL)
$cCard4Icon = !empty(trim((string)($siteSettings['contact_card4_icon'] ?? ''))) ? trim((string)$siteSettings['contact_card4_icon']) : '⏰';
$cCard4Title = !empty(trim((string)($siteSettings['contact_card4_title'] ?? ''))) ? trim((string)$siteSettings['contact_card4_title']) : 'Center Hours';
$cCard4Hours = !empty(trim((string)($siteSettings['contact_card4_hours'] ?? ''))) ? trim((string)$siteSettings['contact_card4_hours']) : $contactHours;
$cCard4Btn = !empty(trim((string)($siteSettings['contact_card4_btn'] ?? ''))) ? trim((string)$siteSettings['contact_card4_btn']) : 'Book Consultation →';
$cCard4Url = !empty(trim((string)($siteSettings['contact_card4_url'] ?? ''))) ? trim((string)$siteSettings['contact_card4_url']) : $contactBookingUrl;
?>

<!-- ==========================================================================
     1. HERO SECTION (FREE CONSULTATION CTA)
     ========================================================================== -->
<section class="contact-hero" id="consultation-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <?php if (!empty($contactHeroBadge)): ?>
        <span class="contact-hero-badge"><?php echo htmlspecialchars($contactHeroBadge); ?></span>
        <?php endif; ?>
        <?php if (!empty(trim(strip_tags((string)$contactHeroTitle)))): ?>
        <h1 class="contact-hero-title"><?php echo $contactHeroTitle; ?></h1>
        <?php endif; ?>
        <?php if (!empty(trim(strip_tags((string)$contactHeroDesc)))): ?>
        <p class="contact-hero-subtext">
            <?php echo nl2br(htmlspecialchars($contactHeroDesc)); ?>
        </p>
        <?php endif; ?>

        <ul class="contact-hero-bullets">
            <li>100% Online &amp; Confidential Call</li>
            <li>Understand root cause of energy blockages</li>
            <li>Completely Free — No Obligations</li>
        </ul>

        <div class="contact-hero-btns">
            <a href="<?php echo htmlspecialchars($contactBookingUrl); ?>" 
               target="_blank" rel="noopener" class="btn-gold" style="padding: 14px 34px; font-size: 1.05rem;">
                Book Free Session →
            </a>
            <a href="#contact-form-section" class="btn-secondary" style="padding: 14px 30px; font-size: 0.95rem;">
                Send a Message
            </a>
        </div>
    </div>
</section>

<!-- ==========================================================================
     2. CENTER INFO SECTION (REIKI BLISS)
     ========================================================================== -->
<section class="center-info-section" id="center-info">
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 50px; position: relative; z-index: 2;" class="animate-on-scroll">
            <?php if (!empty($contactSecBadge)): ?>
            <span class="badge badge-gold" style="margin-bottom: 12px;"><?php echo htmlspecialchars($contactSecBadge); ?></span>
            <?php endif; ?>
            <?php if (!empty($contactSecHeading)): ?>
            <h2 class="section-heading" style="font-size: 2.8rem; color: #0F1117;"><?php echo htmlspecialchars($contactSecHeading); ?></h2>
            <?php endif; ?>
            <?php if (!empty($contactSecDesc)): ?>
            <p style="color: #555D6E; max-width: 600px; margin: 0 auto; font-size: 1.05rem;">
                <?php echo nl2br(htmlspecialchars($contactSecDesc)); ?>
            </p>
            <?php endif; ?>
        </div>

        <div class="center-info-grid">
            <!-- Info 1: Address -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box"><?php echo htmlspecialchars($cCard1Icon); ?></div>
                <h3 class="center-info-title"><?php echo htmlspecialchars($cCard1Title); ?></h3>
                <p class="center-info-text"><?php echo nl2br(htmlspecialchars($cCard1Text)); ?></p>
                <?php if (!empty($cCard1Btn) && !empty($cCard1Url)): ?>
                <a href="<?php echo htmlspecialchars($cCard1Url); ?>" target="_blank" rel="noopener" class="center-info-link">
                    <?php echo htmlspecialchars($cCard1Btn); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Info 2: Call & WhatsApp -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box"><?php echo htmlspecialchars($cCard2Icon); ?></div>
                <h3 class="center-info-title"><?php echo htmlspecialchars($cCard2Title); ?></h3>
                <p class="center-info-text">
                    <?php if (!empty($cCard2Phone)): ?>
                    <a href="tel:<?php echo htmlspecialchars($cCard2Phone); ?>" style="color: inherit; text-decoration: none; font-weight: 600; display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($cCard2Phone); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($cCard2Wa)): ?>
                    <span>WhatsApp: <?php echo htmlspecialchars($cCard2Wa); ?></span>
                    <?php endif; ?>
                </p>
                <?php if (!empty($cCard2Btn) && !empty($cCard2Url)): ?>
                <a href="<?php echo htmlspecialchars($cCard2Url); ?>" target="_blank" rel="noopener" class="center-info-link">
                    <?php echo htmlspecialchars($cCard2Btn); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Info 3: Email -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box"><?php echo htmlspecialchars($cCard3Icon); ?></div>
                <h3 class="center-info-title"><?php echo htmlspecialchars($cCard3Title); ?></h3>
                <p class="center-info-text">
                    <?php if (!empty($cCard3Email)): ?>
                    <a href="mailto:<?php echo htmlspecialchars($cCard3Email); ?>" style="color: inherit; text-decoration: none; font-weight: 500;"><?php echo htmlspecialchars($cCard3Email); ?></a>
                    <?php endif; ?>
                </p>
                <?php if (!empty($cCard3Btn) && !empty($cCard3Email)): ?>
                <a href="mailto:<?php echo htmlspecialchars($cCard3Email); ?>" class="center-info-link">
                    <?php echo htmlspecialchars($cCard3Btn); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Info 4: Center Hours -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box"><?php echo htmlspecialchars($cCard4Icon); ?></div>
                <h3 class="center-info-title"><?php echo htmlspecialchars($cCard4Title); ?></h3>
                <p class="center-info-text">
                    <?php echo nl2br(htmlspecialchars($cCard4Hours)); ?>
                </p>
                <?php if (!empty($cCard4Btn) && !empty($cCard4Url)): ?>
                <a href="<?php echo htmlspecialchars($cCard4Url); ?>" target="_blank" rel="noopener" class="center-info-link">
                    <?php echo htmlspecialchars($cCard4Btn); ?>
                </a>
                <?php endif; ?>
            </div>

        </div>

    </div>
</section>

<!-- ==========================================================================
     3. CONTACT FORM SECTION (#contact-form-section)
     ========================================================================== -->
<div id="enquire" style="scroll-margin-top: 100px;"></div>
<section class="contact-form-section" id="contact-form-section" style="scroll-margin-top: 100px;">
    <div class="container">
        
        <div class="contact-form-card animate-on-scroll" id="book-form" style="scroll-margin-top: 100px;">
            <div class="form-title-box">
                <?php if ($detectedOrderType === 'product'): ?>
                    <span class="badge badge-gold" style="margin-bottom: 12px;">🛍️ Product Order</span>
                    <h2 class="section-heading" style="font-size: 2.5rem; margin-bottom: 10px; color: #0F1117;">Order via <em>WhatsApp</em></h2>
                    <p style="color: #555D6E; font-size: 0.98rem; max-width: 600px; margin: 0 auto;">Ordering: <strong><?php echo htmlspecialchars($detectedItemName); ?></strong>. Provide your name &amp; phone to place your order directly on WhatsApp.</p>
                <?php elseif ($detectedOrderType === 'course'): ?>
                    <span class="badge badge-gold" style="margin-bottom: 12px;">🎓 Course Enrollment</span>
                    <h2 class="section-heading" style="font-size: 2.5rem; margin-bottom: 10px; color: #0F1117;">Enrol via <em>WhatsApp</em></h2>
                    <p style="color: #555D6E; font-size: 0.98rem; max-width: 600px; margin: 0 auto;">Course: <strong><?php echo htmlspecialchars($detectedItemName); ?></strong>. Provide your details below to enquire or book your seat on WhatsApp.</p>
                <?php else: ?>
                    <span class="badge badge-gold" style="margin-bottom: 12px;">Direct Message</span>
                    <h2 class="section-heading" style="font-size: 2.5rem; margin-bottom: 10px; color: #0F1117;">Send Us a <em>Message</em></h2>
                    <p style="color: #555D6E; font-size: 0.98rem; max-width: 600px; margin: 0 auto;">Fill out the form below and our healing masters will reach out to you promptly within 24 hours.</p>
                <?php endif; ?>
            </div>

            <!-- AJAX / Fallback Response Alert Box -->
            <?php 
            $feedbackSuccess = $_GET['success'] ?? '';
            $feedbackError = $_GET['error'] ?? '';
            ?>
            <div id="form-response-alert" class="form-alert-box<?php echo $feedbackSuccess ? ' alert-success' : ($feedbackError ? ' alert-danger' : ''); ?>" <?php echo ($feedbackSuccess || $feedbackError) ? 'style="display:block;"' : ''; ?> role="alert"><?php echo htmlspecialchars($feedbackSuccess ?: $feedbackError); ?></div>

            <form id="contact-form-element" action="<?php echo BASE_URL; ?>api/contact-submit.php" method="POST" novalidate>
                <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($detectedOrderType); ?>">
                <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($detectedItemName); ?>">

                <div class="form-grid-2col">
                    <!-- Name Input -->
                    <div class="form-group">
                        <label for="contact-name" class="form-label">Your Full Name *</label>
                        <input type="text" id="contact-name" name="name" class="form-input" placeholder="e.g. Ananya Sharma" required>
                    </div>

                    <!-- Phone Input -->
                    <div class="form-group">
                        <label for="contact-phone" class="form-label">Phone / WhatsApp Number *</label>
                        <input type="tel" id="contact-phone" name="phone" class="form-input" placeholder="+91 98765 43210" required>
                    </div>

                    <!-- Message Textarea -->
                    <div class="form-group full-width">
                        <label for="contact-message" class="form-label">Your Message or Healing Concern <span style="font-weight: normal; color: #888; font-size: 0.9em;">(Optional)</span></label>
                        <textarea id="contact-message" name="message" class="form-textarea" placeholder="Tell us about your physical, emotional, or spiritual healing goals..."><?php echo htmlspecialchars($defaultMessage); ?></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 10px;">
                    <button type="submit" id="contact-submit-btn" class="btn-gold" style="padding: 14px 44px; font-size: 1.05rem; cursor: pointer; border: none;">
                        <?php echo !empty($detectedOrderType) ? ($detectedOrderType === 'product' ? 'Order on WhatsApp →' : 'Enrol on WhatsApp →') : 'Send Message →'; ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>

<!-- ==========================================================================
     4. GOOGLE MAP EMBED SECTION
     ========================================================================== -->
<section class="map-section" id="location-map">
    <iframe src="<?php echo htmlspecialchars($contactMapsEmbed); ?>" 
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Reiki Bliss Location Map">
    </iframe>
</section>

<!-- Include Footer Component -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#enquire' || window.location.hash === '#book-form' || window.location.hash === '#contact-form-section' || window.location.search.includes('course=') || window.location.search.includes('service=') || window.location.search.includes('product=')) {
        var target = document.getElementById('book-form') || document.getElementById('enquire') || document.getElementById('contact-form-section');
        if (target) {
            setTimeout(function() {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 180);
        }
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
