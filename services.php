<?php
// Services Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Healing Services | Divine Reiki & Energy Healing Center";
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
    <div class="container animate-on-scroll">
        <span class="services-hero-badge">What We Offer</span>
        <h1 class="services-hero-title">Holistic Healing Services</h1>
        <p class="services-hero-subtext">
            Discover our specialized energy healing modalities tailored to release blockages, balance chakras, and restore complete mind, body, and spiritual harmony.
        </p>

        <div style="margin-bottom: 28px;">
            <a href="<?php echo BASE_URL; ?>contact.php#consultation-hero" class="btn-gold" style="padding: 12px 30px; font-size: 1rem; text-decoration: none;">
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

        <!-- Featured First Service (Larger Hero Card) -->
        <?php if ($featuredService): ?>
            <div class="featured-service-card animate-on-scroll service-card-filterable" data-is-free="<?php echo $featuredService['is_free']; ?>">
                <div class="featured-img-box">
                    <img src="<?php echo htmlspecialchars($featuredService['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" alt="<?php echo htmlspecialchars($featuredService['title']); ?>" loading="lazy">
                    <?php if ($featuredService['is_free']): ?>
                        <span class="badge badge-green featured-badge-tag">FREE SESSION</span>
                    <?php else: ?>
                        <span class="badge badge-gold featured-badge-tag">FEATURED MODALITY</span>
                    <?php endif; ?>
                </div>
                <div class="featured-content-box">
                    <div>
                        <span class="featured-label">Featured Service</span>
                        <h2 class="featured-title">
                            <a href="<?php echo BASE_URL; ?>service-detail.php?slug=<?php echo htmlspecialchars($featuredService['slug']); ?>">
                                <?php echo htmlspecialchars($featuredService['title']); ?>
                            </a>
                        </h2>
                        <p class="featured-desc"><?php echo htmlspecialchars($featuredService['short_description']); ?></p>
                        <p style="font-size: 0.94rem; color: var(--muted-gray); line-height: 1.6;">
                            <?php echo htmlspecialchars(substr($featuredService['full_description'], 0, 180)) . '...'; ?>
                        </p>
                    </div>
                    <div class="featured-meta-row">
                        <div>
                            <?php if ($featuredService['is_free'] || $featuredService['price'] === null): ?>
                                <span class="featured-price" style="color: var(--soft-green-text);">Free Consultation</span>
                            <?php else: ?>
                                <span class="featured-price">₹<?php echo number_format($featuredService['price'], 2); ?></span>
                            <?php endif; ?>
                            <span class="featured-duration"> · <?php echo htmlspecialchars($featuredService['duration_minutes']); ?> mins session</span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>contact.php?service=<?php echo urlencode($featuredService['slug']); ?>#contact-form-section" class="btn-primary">
                            Book Session →
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2-Column Grid List of Remaining Services -->
        <div class="services-grid-list">
            <?php foreach ($otherServices as $service): ?>
                <div class="service-item-card animate-on-scroll service-card-filterable" data-is-free="<?php echo $service['is_free']; ?>">
                    <div class="service-thumb-box">
                        <img src="<?php echo htmlspecialchars($service['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" loading="lazy">
                    </div>
                    <div class="service-info-box">
                        <div>
                            <h3 class="service-item-title">
                                <a href="<?php echo BASE_URL; ?>service-detail.php?slug=<?php echo htmlspecialchars($service['slug']); ?>">
                                    <?php echo htmlspecialchars($service['title']); ?>
                                </a>
                            </h3>
                            <p class="service-item-desc"><?php echo htmlspecialchars($service['short_description']); ?></p>
                        </div>
                        <div class="service-item-meta">
                            <div>
                                <?php if ($service['is_free'] || $service['price'] === null): ?>
                                    <span class="service-item-price" style="color: var(--soft-green-text);">Free</span>
                                <?php else: ?>
                                    <span class="service-item-price">₹<?php echo number_format($service['price'], 2); ?></span>
                                <?php endif; ?>
                                <span style="font-size: 0.82rem; color: var(--muted-gray);"> (<?php echo htmlspecialchars($service['duration_minutes']); ?> mins)</span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>contact.php?service=<?php echo urlencode($service['slug']); ?>#contact-form-section" class="btn-secondary" style="padding: 6px 14px; font-size: 0.84rem;">
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
