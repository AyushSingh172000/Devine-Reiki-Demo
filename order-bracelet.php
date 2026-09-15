<?php
// Order Customized Crystal Bracelet Page - Reiki Bliss
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Order Custom Crystal Bracelet | Reiki Bliss";
$pageDescription = "Order a 100% natural, Reiki-charged custom crystal bracelet crafted specifically with your chosen healing intention and charged by our Reiki Masters.";
$heroBadge = "Intention Charged Crystals";
$heroTitle = "Customized <em>Crystal Bracelet</em>";
$heroSubtext = "Select your specific healing intention — whether for anxiety relief, financial abundance, energy protection, or love — and our Reiki Masters will energetically cleanse, program, and charge your custom gemstone bracelet.";

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION
     ========================================================================== -->
<section class="order-hero" id="order-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="order-hero-badge"><?php echo $heroBadge; ?></span>
        <h1 class="order-hero-title"><?php echo $heroTitle; ?></h1>
        <p class="order-hero-subtext"><?php echo $heroSubtext; ?></p>
    </div>
</section>

<!-- ==========================================================================
     2. ORDER FORM SECTION
     ========================================================================== -->
<section class="order-form-section" id="order-form-section">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container">
        
        <!-- Success Confirmation Container -->
        <div id="order-success-box" class="order-success-box">
            <div class="success-icon-circle">✓</div>
            <h2 class="section-heading" style="font-size: 2.2rem; margin-bottom: 12px; color: #ffffff;">Order Inquiry Received!</h2>
            <p class="success-msg-text" style="font-size: 1.05rem; color: #4cd964; max-width: 600px; margin: 0 auto 20px;">
                Thank you! Your custom bracelet request has been recorded.
            </p>
            <p style="font-size: 0.95rem; color: rgba(255, 255, 255, 0.88);">
                To complete your custom gemstone selection and confirm wrist measurements, click the button below to connect with Reiki Grandmaster Anupama Agrawal on WhatsApp:
            </p>
            <div>
                <a href="#" id="wa-followup-btn" target="_blank" rel="noopener" class="wa-followup-btn">
                    💬 Complete Order on WhatsApp
                </a>
            </div>
        </div>

        <!-- Form Card Container -->
        <div class="order-card animate-on-scroll">
            <div style="text-align: center; margin-bottom: 36px;">
                <span class="badge badge-gold" style="margin-bottom: 12px; background: rgba(201, 168, 76, 0.18); color: #C9A84C; border: 1px solid rgba(201, 168, 76, 0.4);">Custom Order Form</span>
                <h2 class="section-heading" style="font-size: 2.4rem; color: #ffffff; margin-bottom: 10px;">Provide <em>Your Details</em></h2>
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.98rem; max-width: 620px; margin: 0 auto;">
                    Please select your primary spiritual intention and describe any specific gemstones or wrist dimensions.
                </p>
            </div>

            <!-- Form Response Alert Box -->
            <div id="order-alert-box" class="form-alert-box" role="alert"></div>

            <form id="bracelet-order-form" action="<?php echo BASE_URL; ?>api/order-submit.php" method="POST" novalidate>
                <input type="hidden" name="order_type" value="customized">

                <div class="form-grid-2col">
                    <!-- Name Input -->
                    <div class="form-group">
                        <label for="order-name" class="form-label">Full Name *</label>
                        <input type="text" id="order-name" name="name" class="form-input" placeholder="e.g. Rahul Verma" required>
                    </div>

                    <!-- Email Input -->
                    <div class="form-group">
                        <label for="order-email" class="form-label">Email Address *</label>
                        <input type="email" id="order-email" name="email" class="form-input" placeholder="name@example.com" required>
                    </div>

                    <!-- Phone Input -->
                    <div class="form-group full-width">
                        <label for="order-phone" class="form-label">Phone / WhatsApp Number *</label>
                        <input type="tel" id="order-phone" name="phone" class="form-input" placeholder="+91 98765 43210" required>
                    </div>

                    <!-- Customized Intention Specific Fields -->
                    <div class="form-group full-width">
                        <label for="intention-select" class="form-label">Primary Intention / Healing Purpose *</label>
                        <select id="intention-select" name="intention" class="form-select" required>
                            <option value="">-- Select Primary Intention --</option>
                            <option value="Anxiety & Stress Relief">🧘 Anxiety &amp; Stress Relief</option>
                            <option value="Financial Prosperity & Wealth">💰 Financial Prosperity &amp; Abundance</option>
                            <option value="Health & Physical Vitality">🌿 Health &amp; Physical Vitality</option>
                            <option value="Love & Relationship Harmony">💖 Love &amp; Relationship Harmony</option>
                            <option value="Psychic & Energy Protection">🛡️ Psychic &amp; Energy Protection</option>
                            <option value="Focus & Career Growth">🚀 Focus, Mind &amp; Career Growth</option>
                            <option value="Self-Confidence & Courage">🦁 Self-Confidence &amp; Courage</option>
                            <option value="Spiritual Awakening">✨ Spiritual Awakening &amp; Intuition</option>
                            <option value="Deep Sleep & Calm">🌙 Deep Sleep &amp; Emotional Calm</option>
                            <option value="Custom Intention">✍️ Custom Intention (Specify Below)</option>
                        </select>
                    </div>

                    <div class="form-group full-width" id="custom-intention-group" style="display: none;">
                        <label for="custom-intention" class="form-label">Specify Your Custom Intention *</label>
                        <input type="text" id="custom-intention" name="custom_intention" class="form-input" placeholder="e.g. Harmonizing exam stress & boosting memory retention">
                    </div>

                    <!-- Message / Additional Notes -->
                    <div class="form-group full-width">
                        <label for="order-message" class="form-label">Additional Message / Wrist Size Notes</label>
                        <textarea id="order-message" name="message" class="form-textarea" placeholder="Provide any specific health issues, wrist circumference, or gemstone preferences..."></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 10px;">
                    <button type="submit" id="order-submit-btn" class="btn-gold" style="padding: 14px 44px; font-size: 1.05rem; cursor: pointer; border: none;">
                        Submit Order Request →
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>

<!-- Include CTA Section -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
