<?php
// Services Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Healing Services | Reiki Bliss";
$pageDescription = "Explore our holistic healing services including Usui Reiki sessions, distance healing, chakra balancing, aura repair, and crystal energy therapy.";

// Fetch Services from MySQL
try {
    $stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC");
    $services = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error in services.php: " . $e->getMessage());
    $services = [];
}

// Separate featured service (first item) and remaining services
$featuredService = !empty($services) ? $services[0] : null;
$otherServices = !empty($services) ? array_slice($services, 1) : [];

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION & FILTER TABS
     ========================================================================== -->
<section class="services-hero" id="services-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="services-hero-badge">What We Offer</span>
        <h1 class="services-hero-title">Holistic Healing Services</h1>
        <p class="services-hero-subtext">
            Discover our specialized energy healing modalities tailored to release blockages, balance chakras, and restore complete mind, body, and spiritual harmony.
        </p>

        <div style="margin-bottom: 28px;">
            <a href="<?php echo BOOKING_URL; ?>" target="_blank" rel="noopener" class="btn-gold" style="padding: 12px 30px; font-size: 1rem; text-decoration: none;">
                Book Free Session →
            </a>
        </div>

        <!-- Interactive Filter Tabs -->
        <div class="filter-tabs" id="service-filter-tabs">
            <button class="filter-btn active" data-filter="all">All Services</button>
            <button class="filter-btn" data-filter="free">Free Sessions</button>
            <button class="filter-btn" data-filter="paid">Paid Healing</button>
        </div>
    </div>
</section>

<!-- ==========================================================================
     2. SERVICES LISTING & GRID SECTION
     ========================================================================== -->
<section class="services-listing-section" id="services-grid-section">
    <div class="container">

        <!-- 2-Column Grid List of All Services -->
        <div class="services-grid-list">
            <?php foreach ($services as $service): ?>
                <?php 
                $hasImg = !empty($service['image']);
                $sImgUrl = $hasImg ? ((strpos($service['image'], 'http://') === 0 || strpos($service['image'], 'https://') === 0) ? $service['image'] : BASE_URL . ltrim($service['image'], '/')) : '';
                ?>
                <div class="service-item-card animate-on-scroll service-card-filterable" data-is-free="<?php echo $service['is_free']; ?>">
                    <?php if ($hasImg): ?>
                    <div class="service-thumb-box">
                        <img src="<?php echo htmlspecialchars($sImgUrl); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" loading="lazy">
                        <?php if ($service['is_free']): ?>
                            <span class="badge badge-green" style="position: absolute; top: 12px; left: 12px; font-size: 0.72rem; padding: 3px 8px; border-radius: 4px; font-weight: 700;">FREE</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="service-info-box">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                                <h3 class="service-item-title" style="margin-bottom: 0;">
                                    <a href="<?php echo BASE_URL; ?>service/<?php echo htmlspecialchars($service['slug']); ?>">
                                        <?php echo htmlspecialchars($service['title']); ?>
                                    </a>
                                </h3>
                                <?php if (!$hasImg && $service['is_free']): ?>
                                    <span class="badge badge-green" style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; font-weight: 700;">FREE</span>
                                <?php endif; ?>
                            </div>
                            <p class="service-item-desc"><?php echo htmlspecialchars($service['short_description']); ?></p>
                        </div>
                        <div class="service-item-meta">
                            <div>
                                <div style="display: flex; align-items: baseline; gap: 8px;">
                                    <?php if ($service['is_free'] || (float)$service['price'] <= 0): ?>
                                        <span class="service-item-price" style="color: var(--soft-green-text, #16a34a);">Free</span>
                                    <?php else: ?>
                                        <span class="service-item-price">₹<?php echo number_format($service['price'], 0); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap;">
                                    <span style="font-size: 0.74rem; font-weight: 600; background: rgba(200, 155, 60, 0.12); color: #8a6714; padding: 2px 8px; border-radius: 4px;">⏱️ <?php echo htmlspecialchars($service['duration_minutes']); ?> mins</span>
                                    <span style="font-size: 0.74rem; font-weight: 600; background: rgba(45, 27, 105, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 4px;">🌐 Mode: <?php echo htmlspecialchars($service['mode'] ?? 'Online'); ?></span>
                                </div>
                            </div>
                            <a href="<?php echo BOOKING_URL; ?>" target="_blank" rel="noopener" class="btn-gold" style="padding: 8px 18px; font-size: 0.86rem; align-self: flex-end;">
                                Book Session →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- JavaScript Filter Logic -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const filterBtns = document.querySelectorAll('#service-filter-tabs .filter-btn');
  const serviceCards = document.querySelectorAll('.service-card-filterable');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');

      serviceCards.forEach(card => {
        const isFree = card.getAttribute('data-is-free');

        if (filter === 'all') {
          card.classList.remove('hidden');
        } else if (filter === 'free' && isFree === '1') {
          card.classList.remove('hidden');
        } else if (filter === 'paid' && isFree === '0') {
          card.classList.remove('hidden');
        } else {
          card.classList.add('hidden');
        }
      });
    });
  });
});
</script>

<!-- ==========================================================================
     3. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
