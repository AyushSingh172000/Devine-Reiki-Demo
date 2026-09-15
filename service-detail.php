<?php
// Service Detail Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

$slug = $_GET['slug'] ?? '';

// Fetch single service by slug
$service = null;
if (!empty($slug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in service-detail.php: " . $e->getMessage());
    }
}

// Fallback if not found: fetch first available service or redirect
if (!$service) {
    try {
        $fallbackStmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
        $service = $fallbackStmt ? $fallbackStmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (PDOException $e) {
        $service = null;
    }
}

if (!$service) {
    header("Location: " . BASE_URL . "services.php");
    exit;
}

// Fetch related services (excluding current)
$relatedServices = [];
try {
    $relStmt = $pdo->prepare("SELECT * FROM services WHERE id != ? AND is_active = 1 ORDER BY sort_order ASC LIMIT 4");
    $relStmt->execute([$service['id']]);
    $relatedServices = $relStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching related services: " . $e->getMessage());
}

// Page Metadata
$pageTitle = htmlspecialchars($service['title']) . " | Divine Reiki & Energy Healing Center";
$pageDescription = htmlspecialchars($service['short_description']);
$pageOgImage = !empty($service['image']) ? $service['image'] : null;

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="container">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="<?php echo BASE_URL; ?>index.php">Home</a>
        <span class="breadcrumb-separator">›</span>
        <a href="<?php echo BASE_URL; ?>services.php">Services</a>
        <span class="breadcrumb-separator">›</span>
        <span><?php echo htmlspecialchars($service['title']); ?></span>
    </nav>
</div>

<!-- Service Detail Section -->
<section class="service-detail-section" id="service-detail">
    <div class="container">
        <div class="service-detail-grid">
            
            <!-- Main Service Content Column -->
            <article class="service-detail-main animate-on-scroll">
                <!-- Hero Image -->
                <div class="service-main-hero-img">
                    <img src="<?php echo htmlspecialchars($service['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" loading="lazy">
                </div>

                <!-- Title & Meta Header -->
                <h1 class="service-detail-title"><?php echo htmlspecialchars($service['title']); ?></h1>
                
                <div class="service-header-meta">
                    <div>
                        <?php if ($service['is_free'] || $service['price'] === null): ?>
                            <span class="service-meta-price" style="color: var(--soft-green-text);">Free Session</span>
                        <?php else: ?>
                            <span class="service-meta-price">₹<?php echo number_format($service['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="service-meta-duration">
                        ⏱️ Duration: <strong><?php echo htmlspecialchars($service['duration_minutes']); ?> Minutes</strong>
                    </div>
                </div>

                <!-- Short Summary -->
                <div style="font-size: 1.15rem; font-weight: 500; color: var(--primary-purple); margin-bottom: 24px; line-height: 1.6;">
                    <?php echo htmlspecialchars($service['short_description']); ?>
                </div>

                <!-- Full Description Content -->
                <div class="service-body-description">
                    <?php 
                    $paragraphs = explode("\n", $service['full_description']);
                    foreach ($paragraphs as $para) {
                        $para = trim($para);
                        if (!empty($para)) {
                            echo '<p>' . htmlspecialchars($para) . '</p>';
                        }
                    }
                    ?>
                </div>

                <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                    <a href="<?php echo BOOKING_URL; ?>" target="_blank" rel="noopener" class="btn-gold" style="font-size: 1.1rem; padding: 14px 36px;">
                        Book Session Now →
                    </a>
                </div>
            </article>

            <!-- Sidebar: Other Related Services -->
            <aside class="related-sidebar animate-on-scroll">
                <h3 class="related-sidebar-title">Other Services</h3>
                
                <?php if (!empty($relatedServices)): ?>
                    <?php foreach ($relatedServices as $rel): ?>
                        <a href="<?php echo BASE_URL; ?>service-detail.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>" class="related-service-card">
                            <div class="related-thumb-box">
                                <img src="<?php echo htmlspecialchars($rel['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy">
                            </div>
                            <div>
                                <h4 class="related-title"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                <span class="related-price">
                                    <?php echo ($rel['is_free'] || $rel['price'] === null) ? 'Free' : '₹' . number_format($rel['price'], 2); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 0.9rem; color: var(--muted-gray);">No other services found.</p>
                <?php endif; ?>

                <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
                    <a href="<?php echo BASE_URL; ?>services.php" class="btn-primary" style="width: 100%; font-size: 0.9rem; padding: 10px;">
                        View All Services →
                    </a>
                </div>
            </aside>

        </div>
    </div>
</section>

<!-- Reusable CTA Section -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
