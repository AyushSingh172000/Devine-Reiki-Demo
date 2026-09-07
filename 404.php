<?php
// 404 Error Page - Divine Reiki Center
http_response_code(404);

$pageTitle = "Page Not Found (404) | Divine Reiki & Energy Healing Center";
$pageDescription = "The page you are looking for could not be found. Return to our homepage to explore Reiki healing sessions, certification courses, and crystal bracelets.";

require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

include __DIR__ . '/includes/header.php';
?>

<section class="services-section" style="padding: 100px 0; text-align: center; background: linear-gradient(135deg, #FDF8F0 0%, #F5EBE0 100%);">
    <div class="container animate-on-scroll">
        <span class="badge badge-gold" style="font-size: 1.1rem; padding: 8px 20px; margin-bottom: 20px;">404 Error</span>
        <h1 class="hero-title" style="font-size: 3.5rem; color: var(--primary-purple); margin-bottom: 20px;">
            Path Not Found
        </h1>
        <p style="font-size: 1.2rem; color: var(--muted-gray); max-width: 600px; margin: 0 auto 36px; line-height: 1.7;">
            The page you are seeking may have moved into new energy, or the URL entered is incorrect. Let us guide you back to harmony.
        </p>

        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 50px;">
            <a href="<?php echo BASE_URL; ?>index.php" class="btn-gold" style="padding: 14px 36px; font-size: 1.05rem;">
                🏠 Return to Homepage
            </a>
            <a href="<?php echo BASE_URL; ?>services.php" class="btn-secondary" style="padding: 14px 36px; font-size: 1.05rem;">
                🧘 Explore Healing Services
            </a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn-secondary" style="padding: 14px 36px; font-size: 1.05rem;">
                💬 Contact Us
            </a>
        </div>

        <div style="background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); padding: 32px; border-radius: 20px; max-width: 700px; margin: 0 auto; border: 1px solid rgba(201, 168, 76, 0.3);">
            <h3 style="font-family: 'Playfair Display', serif; color: var(--primary-purple); margin-bottom: 16px;">Looking for something specific?</h3>
            <ul style="list-style: none; display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; padding: 0; font-weight: 600;">
                <li><a href="<?php echo BASE_URL; ?>shop.php" style="color: var(--primary-purple); text-decoration: none;">💎 Crystal Shop</a></li>
                <li><a href="<?php echo BASE_URL; ?>courses.php" style="color: var(--primary-purple); text-decoration: none;">🎓 Reiki Courses</a></li>
                <li><a href="<?php echo BASE_URL; ?>gallery.php" style="color: var(--primary-purple); text-decoration: none;">🖼️ Sanctuary Gallery</a></li>
                <li><a href="<?php echo BASE_URL; ?>blog.php" style="color: var(--primary-purple); text-decoration: none;">📝 Spiritual Blog</a></li>
            </ul>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/cta-section.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
