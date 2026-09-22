<?php
// Privacy Sanctuary Page - Reiki Bliss
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

$pageTitle = !empty($siteSettings['privacy_title']) ? $siteSettings['privacy_title'] . ' | ' . SITE_NAME : 'Privacy Sanctuary | ' . SITE_NAME;
$pageDescription = "Read the sacred privacy principles, confidentiality policies, and personal data protection standards at Reiki Bliss.";

$privacyTitle = !empty($siteSettings['privacy_title']) ? $siteSettings['privacy_title'] : 'Privacy Sanctuary';
$privacyTagline = !empty($siteSettings['privacy_tagline']) ? $siteSettings['privacy_tagline'] : 'Sacred Trust & Data Sovereignty';
$privacyRawContent = !empty($siteSettings['privacy_content']) ? $siteSettings['privacy_content'] : "### 1. Sacred Trust & Confidentiality
All personal details, healing histories, chakra diagnostics, and energetic impressions shared during sessions or consultations remain strictly confidential. Your spiritual journey is held in complete reverence.

### 2. Information We Collect
We only collect personal information that you intentionally provide to us—such as your name, email address, phone/WhatsApp number, and consultation notes when booking a session or purchasing spiritual crystals.

### 3. Energy Session Notes & Integrity
Any notes taken by Grand Master Anupama Agrawal during intuitive energy readings or distant healing attunements are stored securely and never disclosed or transferred to third parties.

### 4. Contact & Communication Preferences
We use your contact details solely to confirm appointments, share Zoom/Google Meet links, provide session preparation instructions, or respond to your spiritual inquiries. We never sell, rent, or spam your contact information.

### 5. Third-Party Platforms & Secure Payments
Online consultations and video calls are conducted through encrypted platforms (such as Google Meet, Zoom, or WhatsApp). Payment transactions for crystals and courses are handled through verified payment gateways with industry-standard encryption.

### 6. Your Rights & Data Sanctuary Access
You have the complete right to request a copy of your stored contact details, request amendments, or ask for the complete deletion of your records at any time by contacting us directly.";

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
        <span class="legal-badge">✦ Sacred Sanctuary ✦</span>
        <h1 class="legal-hero-title"><?php echo htmlspecialchars($privacyTitle); ?></h1>
        <p class="legal-hero-subtitle"><?php echo htmlspecialchars($privacyTagline); ?></p>
        
        <div class="legal-meta-bar">
            <div class="legal-meta-item">
                <span class="dot">✦</span>
                <span>Protected Under Sacred Trust</span>
            </div>
            <div class="legal-meta-item">
                <span class="dot">✦</span>
                <span>Founder: Anupama Agrawal</span>
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
        <?php echo renderLegalSections($privacyRawContent); ?>

        <!-- Support Card -->
        <div class="legal-support-card">
            <h4>Have Questions Regarding Your Privacy?</h4>
            <p>We are here to assist and ensure you feel completely secure and supported on your healing path.</p>
            <div class="legal-support-actions">
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn-primary" style="padding: 12px 28px; font-size: 0.95rem;">Connect With Us</a>
                <?php
                $cleanWa = preg_replace('/[^0-9]/', '', $siteSettings['whatsapp'] ?? (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '919726581787'));
                ?>
                <a href="https://wa.me/<?php echo htmlspecialchars($cleanWa); ?>" target="_blank" rel="noopener" class="btn-secondary" style="padding: 12px 28px; font-size: 0.95rem;">💬 WhatsApp Sanctuary</a>
            </div>
        </div>
    </div>
</section>

<?php
// Include Footer Component
include __DIR__ . '/includes/footer.php';
?>
