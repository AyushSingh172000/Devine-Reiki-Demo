<?php
// Contact & Consultation Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Pre-filled Service/Course Parameter Handling
$prefilledService = $_GET['service'] ?? '';
$prefilledCourse = $_GET['course'] ?? '';
$defaultMessage = '';

if (!empty($prefilledService)) {
    $defaultMessage = "Hello Anupama Agrawal, I would like to book a session for " . htmlspecialchars($prefilledService) . ".";
} elseif (!empty($prefilledCourse)) {
    $defaultMessage = "Hello Anupama Agrawal, I am interested in enrolling in the " . htmlspecialchars($prefilledCourse) . " certification course.";
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
$contactCleanWa = preg_replace('/[^0-9]/', '', $siteSettings['whatsapp'] ?? (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '919726581787'));
$contactEmail = !empty($siteSettings['email']) ? $siteSettings['email'] : (defined('SITE_EMAIL') ? SITE_EMAIL : '');
$contactAddress = !empty($siteSettings['address']) ? $siteSettings['address'] : (defined('SITE_ADDRESS') ? SITE_ADDRESS : '');
$contactHours = !empty($siteSettings['working_hours']) ? $siteSettings['working_hours'] : (defined('WORKING_HOURS') ? WORKING_HOURS : 'Mon–Sat: 7 AM – 6 PM · Sun: 9 AM – 1 PM');
$contactMapsUrl = !empty($siteSettings['maps_url']) ? $siteSettings['maps_url'] : (defined('MAPS_URL') ? MAPS_URL : '#');
$contactMapsEmbed = !empty($siteSettings['maps_embed_url']) ? $siteSettings['maps_embed_url'] : 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3719.866755490487!2d72.7981504758784!3d21.19745918228308!2m3!1f02f000!0f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be04e6c38bbcd69%3A0x6b13280c42eb5598!2sAdajan%2C%20Surat%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin';
?>

<!-- ==========================================================================
     1. HERO SECTION (FREE CONSULTATION CTA)
     ========================================================================== -->
<section class="contact-hero" id="consultation-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="contact-hero-badge">Free Online Consultation</span>
        <h1 class="contact-hero-title">30 Minutes with <em>Reiki Masters</em></h1>
        <p class="contact-hero-subtext">
            Take the first step toward physical vitality and spiritual peace. Schedule a complimentary 30-minute online video guidance session directly with our certified Reiki Masters.
        </p>

        <ul class="contact-hero-bullets">
            <li>🌐 100% Online &amp; Confidential Call</li>
            <li>🧘 Understand root cause of energy blockages</li>
            <li>🎁 Completely Free — No Obligations</li>
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
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 50px; position: relative; z-index: 2;" class="animate-on-scroll">
            <span class="badge badge-gold" style="margin-bottom: 12px; background: rgba(201, 168, 76, 0.18); color: #C9A84C; border: 1px solid rgba(201, 168, 76, 0.4);">Our Sanctuary Location</span>
            <h2 class="section-heading" style="font-size: 2.8rem; color: #ffffff;"><?php echo htmlspecialchars(SITE_NAME); ?></h2>
            <p style="color: rgba(255, 255, 255, 0.82); max-width: 600px; margin: 0 auto; font-size: 1.05rem;">
                Located in the heart of Adajan, Surat. Visit our peaceful center or connect with our healing practitioners virtually.
            </p>
        </div>

        <div class="center-info-grid">
            <!-- Info 1: Address -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box">📍</div>
                <h3 class="center-info-title">Visit Us</h3>
                <p class="center-info-text"><?php echo htmlspecialchars($contactAddress); ?></p>
                <a href="<?php echo htmlspecialchars($contactMapsUrl); ?>" target="_blank" rel="noopener" class="center-info-link">
                    View on Google Maps →
                </a>
            </div>

            <!-- Info 2: Call & WhatsApp -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box">📞</div>
                <h3 class="center-info-title">Call / WhatsApp</h3>
                <p class="center-info-text">
                    <a href="tel:<?php echo htmlspecialchars($contactPhone); ?>" style="color: inherit; text-decoration: none; font-weight: 600; display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($contactPhone); ?></a>
                    <span>WhatsApp: <?php echo htmlspecialchars($contactPhone); ?></span>
                </p>
                <a href="https://wa.me/<?php echo htmlspecialchars($contactCleanWa); ?>" target="_blank" rel="noopener" class="center-info-link">
                    Chat on WhatsApp →
                </a>
            </div>

            <!-- Info 3: Email -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box">✉️</div>
                <h3 class="center-info-title">Email Us</h3>
                <p class="center-info-text">
                    <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" style="color: inherit; text-decoration: none; font-weight: 500;"><?php echo htmlspecialchars($contactEmail); ?></a>
                </p>
                <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>" class="center-info-link">
                    Send Email Inquiry →
                </a>
            </div>

            <!-- Info 4: Center Hours -->
            <div class="center-info-card animate-on-scroll">
                <div class="center-icon-box">⏰</div>
                <h3 class="center-info-title">Center Hours</h3>
                <p class="center-info-text">
                    <?php echo nl2br(htmlspecialchars($contactHours)); ?>
                </p>
                <a href="<?php echo htmlspecialchars($contactBookingUrl); ?>" target="_blank" rel="noopener" class="center-info-link">
                    Book Consultation →
                </a>
            </div>

        </div>

    </div>
</section>

<!-- ==========================================================================
     3. CONTACT FORM SECTION (#contact-form-section)
     ========================================================================== -->
<section class="contact-form-section" id="contact-form-section">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container">
        
        <div class="contact-form-card animate-on-scroll" id="book-form">
            <div class="form-title-box">
                <span class="badge" style="margin-bottom: 12px; background: rgba(201, 168, 76, 0.18); color: #C9A84C; border: 1px solid rgba(201, 168, 76, 0.4);">Direct Message</span>
                <h2 class="section-heading" style="font-size: 2.5rem; margin-bottom: 10px; color: #ffffff;">Send Us a <em>Message</em></h2>
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.98rem; max-width: 600px; margin: 0 auto;">Fill out the form below and our healing masters will reach out to you promptly within 24 hours.</p>
            </div>

            <!-- AJAX Response Alert Box -->
            <div id="form-response-alert" class="form-alert-box" role="alert"></div>

            <form id="contact-form-element" action="<?php echo BASE_URL; ?>api/contact-submit.php" method="POST" novalidate>
                <div class="form-grid-2col">
                    <!-- Name Input -->
                    <div class="form-group">
                        <label for="contact-name" class="form-label">Your Full Name *</label>
                        <input type="text" id="contact-name" name="name" class="form-input" placeholder="e.g. Ananya Sharma" required>
                    </div>

                    <!-- Email Input -->
                    <div class="form-group">
                        <label for="contact-email" class="form-label">Your Email Address *</label>
                        <input type="email" id="contact-email" name="email" class="form-input" placeholder="name@example.com" required>
                    </div>

                    <!-- Phone Input -->
                    <div class="form-group full-width">
                        <label for="contact-phone" class="form-label">Phone / WhatsApp Number *</label>
                        <input type="tel" id="contact-phone" name="phone" class="form-input" placeholder="+91 98765 43210" required>
                    </div>

                    <!-- Message Textarea -->
                    <div class="form-group full-width">
                        <label for="contact-message" class="form-label">Your Message or Healing Concern *</label>
                        <textarea id="contact-message" name="message" class="form-textarea" placeholder="Tell us about your physical, emotional, or spiritual healing goals..." required><?php echo htmlspecialchars($defaultMessage); ?></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 10px;">
                    <button type="submit" id="contact-submit-btn" class="btn-gold" style="padding: 14px 44px; font-size: 1.05rem; cursor: pointer; border: none;">
                        Send Message →
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
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Shree Sai Reiki & Yog Centre Location Map">
    </iframe>
</section>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
