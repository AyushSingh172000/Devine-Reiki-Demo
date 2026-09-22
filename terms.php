<?php
// Terms of Attunement Page - Reiki Bliss
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

$pageTitle = !empty($siteSettings['terms_title']) ? $siteSettings['terms_title'] . ' | ' . SITE_NAME : 'Terms of Attunement | ' . SITE_NAME;
$pageDescription = "Read the sacred exchange principles, consultation guidelines, and attunement commitments at Reiki Bliss.";

$termsTitle = !empty($siteSettings['terms_title']) ? $siteSettings['terms_title'] : 'Terms of Attunement';
$termsTagline = !empty($siteSettings['terms_tagline']) ? $siteSettings['terms_tagline'] : 'Sacred Exchange & Guidelines';
$termsRawContent = !empty($siteSettings['terms_content']) ? $siteSettings['terms_content'] : "### 1. Nature of Energy Healing & Complementary Practice
Reiki and complementary modalities offered at Reiki Bliss are gentle, non-invasive spiritual practices intended to support relaxation, emotional balance, and holistic wellness. They are not intended as a substitute for licensed medical diagnosis, clinical treatment, or psychiatric care.

### 2. Booking, Punctuality & Sacred Space
Please arrive or log into your online session 5 minutes prior to the scheduled time in a quiet, undisturbed sanctuary to ensure an optimal energetic experience.

### 3. Cancellation & Rescheduling Courtesy
We honor the mutual exchange of time and energy. If you need to reschedule your healing appointment or consultation, kindly notify us at least 24 hours in advance via WhatsApp or phone so another seeker can be accommodated.

### 4. Attunements & Course Certifications
Certification in Usui Reiki, Lama Fera, and Angel Healing is bestowed upon completion of all coursework, practical attunements, and demonstration of authentic comprehension under the direct guidance of Grand Master Anupama Agrawal.

### 5. Healing Crystals & Sacred Energization
All crystal bracelets, pendulums, and raw stones available at Reiki Bliss are 100% authentic, naturally sourced, and cleansed & charged with high-vibrational Reiki energy before dispatch. As natural minerals, minor organic variations in shade and texture are natural marks of authenticity.

### 6. Guiding Philosophy & Personal Responsibility
Healing is an inward, co-creative journey. In accordance with the Usui principles and our motto 'Think Positive, Be Positive', each participant is an empowered active creator of their own energetic transformation.";

if (!function_exists('renderLegalSections')) {
    function renderLegalSections($text) {
        if (empty(trim($text))) return '';
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));

        if (strpos($text, '###') !== false) {
            $sections = preg_split('/^###\s+/m', $text);
            $output = '';
            foreach ($sections as $section) {
                $section = trim($section);
                if (empty($section)) continue;

                $lines = explode("\n", $section);
                $heading = array_shift($lines);
                $body = trim(implode("\n", $lines));

                $paragraphs = preg_split('/\n{2,}/', $body);
                $bodyHtml = '';
                foreach ($paragraphs as $p) {
                    $p = trim($p);
                    if (empty($p)) continue;
                    if (preg_match('/^[-*]\s+/m', $p)) {
                        $items = preg_split('/^[-*]\s+/m', $p);
                        $listHtml = '<ul class="legal-list">';
                        foreach ($items as $item) {
                            $item = trim($item);
                            if (!empty($item)) {
                                $listHtml .= '<li>' . nl2br(htmlspecialchars($item)) . '</li>';
                            }
                        }
                        $listHtml .= '</ul>';
                        $bodyHtml .= $listHtml;
                    } else {
                        $bodyHtml .= '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                    }
                }

                $output .= '<div class="legal-card">
                    <div class="legal-card-header">
                        <span class="legal-badge-icon">✦</span>
                        <h3 class="legal-card-title">' . htmlspecialchars(trim($heading)) . '</h3>
                    </div>
                    <div class="legal-card-body">' . $bodyHtml . '</div>
                </div>';
            }
            return $output;
        } else {
            $paragraphs = preg_split('/\n{2,}/', $text);
            $output = '<div class="legal-card"><div class="legal-card-body">';
            foreach ($paragraphs as $p) {
                $p = trim($p);
                if (!empty($p)) {
                    $output .= '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                }
            }
            $output .= '</div></div>';
            return $output;
        }
    }
}

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="legal-hero">
    <div class="container">
        <span class="legal-badge">✦ Sacred Guidelines ✦</span>
        <h1 class="legal-hero-title"><?php echo htmlspecialchars($termsTitle); ?></h1>
        <p class="legal-hero-subtitle"><?php echo htmlspecialchars($termsTagline); ?></p>
        
        <div class="legal-meta-bar">
            <div class="legal-meta-item">
                <span class="dot">✦</span>
                <span>Usui Lineage Principles</span>
            </div>
            <div class="legal-meta-item">
                <span class="dot">✦</span>
                <span>Grand Master Anupama Agrawal</span>
            </div>
            <div class="legal-meta-item">
                <span class="dot">✦</span>
                <span>Last Updated: <?php echo date('F Y'); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- Content Section -->
<section class="legal-content-section">
    <div class="legal-layout">
        <?php echo renderLegalSections($termsRawContent); ?>

        <!-- Support Card -->
        <div class="legal-support-card">
            <h4>Ready to Begin Your Healing Attunement?</h4>
            <p>Schedule your complimentary consultation or talk to Grand Master Anupama Agrawal directly.</p>
            <div class="legal-support-actions">
                <?php
                $bookingHref = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');
                $cleanWa = preg_replace('/[^0-9]/', '', $siteSettings['whatsapp'] ?? (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '919726581787'));
                ?>
                <a href="<?php echo htmlspecialchars($bookingHref); ?>" target="_blank" rel="noopener" class="btn-primary" style="padding: 12px 28px; font-size: 0.95rem;">Book Free Session →</a>
                <a href="https://wa.me/<?php echo htmlspecialchars($cleanWa); ?>" target="_blank" rel="noopener" class="btn-secondary" style="padding: 12px 28px; font-size: 0.95rem;">💬 WhatsApp Us</a>
            </div>
        </div>
    </div>
</section>

<?php
// Include Footer Component
include __DIR__ . '/includes/footer.php';
?>
