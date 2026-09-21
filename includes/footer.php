<?php
// Main Footer Component
require_once __DIR__ . '/../config/constants.php';
$footerLogoUrl = !empty($siteSettings['logo_path']) ? BASE_URL . ltrim($siteSettings['logo_path'], '/') : BASE_URL . 'assets/images/reikilogo1.png';
$footerBookingUrl = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');
$footerCleanWa = preg_replace('/[^0-9]/', '', $siteSettings['whatsapp'] ?? (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '919726581787'));
?>

<!-- Footer Section -->
<footer class="site-footer" id="site-footer">
    <!-- Background Video & Dark Overlay -->
    <div class="footer-bg-media">
        <video autoplay loop muted playsinline class="footer-bg-video">
            <source src="<?php echo BASE_URL; ?>assets/videos/spiritual-bg.mp4" type="video/mp4">
        </video>
        <div class="footer-video-overlay"></div>
    </div>

    <div class="container footer-content-container">
        <!-- CTA Banner -->
        <div class="footer-cta-banner">
            <div class="footer-cta-text">
                <h3>Begin Your Energy Healing Journey Today</h3>
                <p>Your first 20-minute spiritual consultation with our Reiki Masters is completely free.</p>
            </div>
            <div class="footer-cta-action">
                <a href="<?php echo htmlspecialchars($footerBookingUrl); ?>" target="_blank" rel="noopener" class="btn-gold">Book Free Session ✨</a>
            </div>
        </div>

        <!-- Main 4-Column Footer Grid -->
        <div class="footer-grid">
            <!-- Column 1: Logo & About -->
            <div class="footer-col footer-col-about">
                <a href="<?php echo BASE_URL; ?>" class="footer-logo">
                    <img src="<?php echo htmlspecialchars($footerLogoUrl); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" class="footer-logo-img">
                    <span class="logo-text">
                        <strong class="logo-title"><?php echo htmlspecialchars(SITE_NAME); ?></strong>
                        <span class="logo-subtitle"><?php echo htmlspecialchars(SITE_TAGLINE); ?></span>
                    </span>
                </a>
                <p class="footer-tagline">
                    Guided by <strong>Dr. Chirag Gajjar</strong> (Reiki Grand Master) & <strong>Binal Gajjar</strong> (Crystal & Numerology Expert) — dedicated to authentic healing, chakra alignment, and empowered living.
                </p>
                <div class="footer-socials">
                    <?php if (!empty(FACEBOOK_URL)): ?>
                    <a href="<?php echo htmlspecialchars(FACEBOOK_URL); ?>" target="_blank" rel="noopener" aria-label="Facebook" class="social-icon">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty(YOUTUBE_URL)): ?>
                    <a href="<?php echo htmlspecialchars(YOUTUBE_URL); ?>" target="_blank" rel="noopener" aria-label="YouTube" class="social-icon">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty(INSTAGRAM_URL)): ?>
                    <a href="<?php echo htmlspecialchars(INSTAGRAM_URL); ?>" target="_blank" rel="noopener" aria-label="Instagram" class="social-icon">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: Quick Links -->
            <div class="footer-col">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo BASE_URL; ?>products.php">Crystal Shop</a></li>
                    <li><a href="<?php echo BASE_URL; ?>courses.php">Reiki Courses</a></li>
                    <li><a href="<?php echo BASE_URL; ?>services.php">Healing Services</a></li>
                    <?php /* <li><a href="<?php echo BASE_URL; ?>gallery.php">Sanctuary Gallery</a></li> */ ?>
                    <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                </ul>
            </div>

            <!-- Column 3: Our Services -->
            <div class="footer-col">
                <h4 class="footer-title">Our Services</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>services.php#reiki-healing">Reiki Healing Session</a></li>
                    <li><a href="<?php echo BASE_URL; ?>services.php#distance-reiki">Distance Reiki Healing</a></li>
                    <li><a href="<?php echo BASE_URL; ?>services.php#chakra-balancing">Chakra Balancing</a></li>
                    <li><a href="<?php echo BASE_URL; ?>services.php#aura-cleansing">Aura Cleansing & Repair</a></li>
                    <li><a href="<?php echo BASE_URL; ?>services.php#crystal-therapy">Crystal Energy Therapy</a></li>
                    <li><a href="<?php echo htmlspecialchars($footerBookingUrl); ?>" target="_blank" rel="noopener">Free Consultation</a></li>
                </ul>
            </div>

            <!-- Column 4: Find Us -->
            <div class="footer-col">
                <h4 class="footer-title">Find Us</h4>
                <ul class="footer-contact-list">
                    <li>
                        <span class="contact-icon">📍</span>
                        <span><?php echo htmlspecialchars(SITE_ADDRESS); ?></span>
                    </li>
                    <li>
                        <span class="contact-icon">📞</span>
                        <a href="tel:<?php echo htmlspecialchars(SITE_PHONE); ?>"><?php echo htmlspecialchars(SITE_PHONE); ?></a>
                    </li>
                    <li>
                        <span class="contact-icon">✉️</span>
                        <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL); ?>"><?php echo htmlspecialchars(SITE_EMAIL); ?></a>
                    </li>
                    <li>
                        <span class="contact-icon">💬</span>
                        <a href="https://wa.me/<?php echo htmlspecialchars($footerCleanWa); ?>" target="_blank" rel="noopener">WhatsApp Us (<?php echo htmlspecialchars(SITE_PHONE); ?>)</a>
                    </li>
                    <li>
                        <span class="contact-icon">🗺️</span>
                        <a href="<?php echo htmlspecialchars(MAPS_URL); ?>" target="_blank" rel="noopener">Get Directions on Maps</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?> Healing Center. All sacred rights reserved. &middot; <a href="<?php echo BASE_URL; ?>admin/" style="color: inherit; opacity: 0.65; text-decoration: none; font-size: 0.9em;" title="Admin Portal">Admin Portal</a></p>
                <div class="footer-bottom-links">
                    <a href="<?php echo BASE_URL; ?>contact.php">Privacy Sanctuary</a>
                    <a href="<?php echo BASE_URL; ?>contact.php">Terms of Attunement</a>
                    <a href="#hero" class="footer-back-top">Back to Top ↑</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Interactive WhatsApp Inquiry Modal -->
<div class="wa-modal-wrapper" id="wa-modal-wrapper" aria-hidden="true">
    <div class="wa-modal-card">
        <!-- Modal Header -->
        <div class="wa-modal-header">
            <div class="wa-header-info">
                <div class="wa-badge-icon">
                    <svg width="24" height="24" fill="#ffffff" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                </div>
                <div class="wa-header-text">
                    <h4 class="wa-modal-title">How can we help?</h4>
                    <p class="wa-modal-subtitle">Reiki Bliss · Usually replies in hours</p>
                </div>
            </div>
            <button class="wa-modal-close" id="wa-modal-close" aria-label="Close modal">✕</button>
        </div>

        <!-- Body Step 1: Select Topic -->
        <div class="wa-modal-body" id="wa-step-1">
            <span class="wa-section-label">WHAT'S THIS ABOUT?</span>
            <div class="wa-options-list">
                <button class="wa-option-item" data-topic="Book a Healing Session">
                    <span class="wa-option-icon">🙏</span>
                    <span class="wa-option-text">Book a Healing Session</span>
                </button>
                <button class="wa-option-item" data-topic="Enquire About a Course">
                    <span class="wa-option-icon">📚</span>
                    <span class="wa-option-text">Enquire About a Course</span>
                </button>
                <button class="wa-option-item" data-topic="Order Birth Chart Bracelet">
                    <span class="wa-option-icon">🔮</span>
                    <span class="wa-option-text">Order Birth Chart Bracelet</span>
                </button>
                <button class="wa-option-item" data-topic="Order Customized Bracelet">
                    <span class="wa-option-icon">✨</span>
                    <span class="wa-option-text">Order Customized Bracelet</span>
                </button>
                <button class="wa-option-item" data-topic="Product / Shop Query">
                    <span class="wa-option-icon">🛍️</span>
                    <span class="wa-option-text">Product / Shop Query</span>
                </button>
                <button class="wa-option-item" data-topic="Something Else">
                    <span class="wa-option-icon">💬</span>
                    <span class="wa-option-text">Something Else</span>
                </button>
            </div>
        </div>

        <!-- Body Step 2: Add Details & Open WhatsApp -->
        <div class="wa-modal-body" id="wa-step-2" style="display: none;">
            <div class="wa-selected-pill-box">
                <span class="wa-selected-pill" id="wa-selected-pill">
                    <span id="wa-pill-text">🙏 Book a Healing Session</span>
                    <button class="wa-pill-remove" id="wa-pill-remove" aria-label="Remove topic">✕</button>
                </span>
            </div>

            <label class="wa-section-label" for="wa-custom-message">
                ANYTHING TO ADD? <span class="wa-optional-tag">(optional)</span>
            </label>
            
            <textarea id="wa-custom-message" class="wa-textarea" rows="3" placeholder="e.g. Preferred day/time, specific concern..."></textarea>

            <button id="wa-submit-btn" class="wa-btn-submit">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                Open WhatsApp
            </button>

            <button class="wa-back-link" id="wa-back-btn">← Change topic</button>
        </div>
    </div>
</div>

<!-- Floating WhatsApp Chat Button Trigger (Green Pill matching Screenshot) -->
<button class="whatsapp-float-btn" id="whatsapp-float-btn" aria-label="Open WhatsApp Help Menu">
    <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24">
        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
    </svg>
    <span>WhatsApp</span>
</button>

<!-- Main JavaScript Modules -->
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/text-healing-animation.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/counter.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/carousel.js?v=<?php echo file_exists(dirname(__DIR__) . '/assets/js/carousel.js') ? filemtime(dirname(__DIR__) . '/assets/js/carousel.js') : '2.0'; ?>"></script>

<?php if (isset($currentPage) && $currentPage == 'gallery.php'): ?>

<script src="<?php echo BASE_URL; ?>assets/js/gallery.js"></script>
<?php endif; ?>
<?php if (isset($currentPage) && $currentPage == 'contact.php'): ?>
<script src="<?php echo BASE_URL; ?>assets/js/contact-form.js"></script>
<?php endif; ?>
<?php if (isset($currentPage) && ($currentPage == 'order-bracelet.php' || $currentPage == 'custom-bracelet.php')): ?>
<script src="<?php echo BASE_URL; ?>assets/js/order-bracelet.js"></script>
<?php endif; ?>

<!-- Google Calendar Appointment Scheduling Popup Integration -->
<style>
.qxCTlb {
  display: none !important;
}
</style>
<div id="gcal-scheduling-target-wrapper" style="display: none !important;" aria-hidden="true">
    <div id="gcal-scheduling-target"></div>
</div>
<script src="https://calendar.google.com/calendar/scheduling-button-script.js" async onload="initGCalButton()"></script>
<script>
function initGCalButton() {
  if (window.calendar && window.calendar.schedulingButton) {
    var target = document.getElementById('gcal-scheduling-target');
    if (target && !document.querySelector('.qxCTlb')) {
      calendar.schedulingButton.load({
        url: '<?php echo BOOKING_URL; ?>?gv=true',
        color: '#C9A84C',
        label: 'Book an appointment',
        target: target,
      });
    }
  }
}
window.addEventListener('load', initGCalButton);
</script>
<!-- end Google Calendar Appointment Scheduling -->
</body>
</html>
