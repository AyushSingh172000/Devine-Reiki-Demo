<?php
// Homepage - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Reiki Bliss | Heal. Balance. Transform.";
$pageDescription = "Experience authentic Usui Reiki healing, certified courses, and Reiki-charged crystal bracelets guided by Dr. Chirag Gajjar & Binal Gajjar at Reiki Bliss.";

// Fetch dynamic data from database
try {
    // 1. Site Stats
    $statsStmt = $pdo->query("SELECT stat_key, stat_value, label FROM site_stats");
    $statsData = [];
    if ($statsStmt) {
        while ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
            $statsData[$row['stat_key']] = $row;
        }
    }

    // 2. Services (active, sorted)
    $servicesStmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC");
    $services = $servicesStmt ? $servicesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 3. Courses (active, sorted)
    $coursesStmt = $pdo->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY sort_order ASC");
    $courses = $coursesStmt ? $coursesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 4. Products (active, sorted, limit 10)
    $productsStmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 10");
    $products = $productsStmt ? $productsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 5. Testimonials (active)
    $testimonialsStmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC");
    $testimonials = $testimonialsStmt ? $testimonialsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
}

// Fallback values for site stats if needed
$livesHealed = $statsData['lives_healed']['stat_value'] ?? '30000';
$yearsExp = $statsData['years_experience']['stat_value'] ?? '25';
$courseLevels = $statsData['course_levels']['stat_value'] ?? '6';
$sessionsDone = $statsData['sessions_completed']['stat_value'] ?? '25000';

// Include Header
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION
     ========================================================================== -->
<section class="hero-section" id="hero">
    <!-- Absolute Background Animation Canvas (Zero Layout Impact, Positioned Behind All Text) -->
    <canvas id="hero-bg-canvas"></canvas>

    <div class="container hero-content animate-on-scroll">

        <span class="hero-location-badge">
            📍 Adajan, Surat · Est. 2014
        </span>

        <h1 class="hero-title">
            Heal. Balance. <em>Transform.</em><br>
            Your Journey Starts Here
        </h1>

        <p class="hero-subtext">
            Guided by <strong>Dr. Chirag Gajjar</strong> & <strong>Binal Gajjar</strong> — empowering lives through authentic Usui Reiki, chakra balancing, aura cleansing, and intention-charged crystal gemstones.
        </p>

        <div class="hero-ctas">
            <a href="<?php echo BASE_URL; ?>contact.php#consultation-hero" class="btn-gold btn-hero-primary">
                Book Free Session <span class="btn-arrow">→</span>
            </a>
            <a href="<?php echo BASE_URL; ?>courses.php" class="btn-secondary btn-hero-secondary">
                Explore Courses
            </a>
            <a href="<?php echo BASE_URL; ?>about.php" class="btn-secondary btn-hero-secondary">
                About the Center
            </a>
        </div>
    </div>

    <!-- Stats Counter Row -->
    <div class="hero-stats-wrapper">
        <div class="container">
            <div class="hero-stats-grid">
                <div class="hero-stat-card">
                    <div class="hero-stat-number stat-number" data-target="<?php echo htmlspecialchars($livesHealed); ?>">30K+</div>
                    <div class="hero-stat-label">Lives Healed & Transformed</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-number stat-number" data-target="<?php echo htmlspecialchars($yearsExp); ?>">25+</div>
                    <div class="hero-stat-label">Years Experience</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-number stat-number" data-target="<?php echo htmlspecialchars($courseLevels); ?>">6</div>
                    <div class="hero-stat-label">Course Levels Offered</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-number stat-number" data-target="<?php echo htmlspecialchars($sessionsDone); ?>">25K+</div>
                    <div class="hero-stat-label">Sessions Completed</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     3. CRYSTAL BRACELETS SECTION
     ========================================================================== -->
<section class="bracelets-section" id="bracelets">
    <!-- Infinite Auto-Scrolling Bracelet Photos Carousel -->
    <div class="infinite-carousel carousel-photos-row">
        <div class="infinite-carousel-track">
            <div class="bracelet-photo-item"><img src="assets/images/products/amethyst-bracelet.jpg" alt="Reiki Amethyst Bracelet" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/7-chakra-bracelet.jpg" alt="7 Chakra Bracelet" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/rose-quartz-bracelet.jpg" alt="Rose Quartz Love Bracelet" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/black-tourmaline-bracelet.jpg" alt="Black Tourmaline Bracelet" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/amethyst-1.jpg" alt="Natural Amethyst Beads" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/chakra-1.jpg" alt="Chakra Alignment Gemstones" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/rosequartz-1.jpg" alt="Rose Quartz Energy" loading="lazy"></div>
            <div class="bracelet-photo-item"><img src="assets/images/products/tourmaline-1.jpg" alt="Protection Crystal Beads" loading="lazy"></div>
        </div>
    </div>

    <div class="container">
        <div class="text-center animate-on-scroll" style="margin-bottom: 50px;">
            <span class="section-label">Custom Crystal Bracelets</span>
            <h2 class="section-heading">Handcrafted Just for <em>You</em></h2>
            <p>Each crystal bracelet is cleansed with white sage, aligned to your planetary vibrations or personal intention, and energized by our Reiki Masters.</p>
        </div>

        <!-- Dual Bracelet Customization Cards -->
        <div class="bracelets-cards-grid">
            <!-- Card 1: Birth Chart Bracelet -->
            <div class="bracelet-card animate-on-scroll">
                <div>
                    <div class="bracelet-icon-circle">🔮</div>
                    <h3 class="bracelet-card-title">Birth Chart Bracelet</h3>
                    <p>Designed strictly based on your exact Date, Time, and Place of Birth to harmonize planetary frequencies and balance weak chakras in your horoscope.</p>
                    <ul class="bracelet-features">
                        <li><span class="feature-check">✓</span> Custom planetary gemstone alignment</li>
                        <li><span class="feature-check">✓</span> Vedic astrological energy matrix</li>
                        <li><span class="feature-check">✓</span> 100% Reiki Master charged & cleansed</li>
                    </ul>
                </div>
                <a href="<?php echo BASE_URL; ?>custom-bracelet.php?type=birth-chart" class="btn-primary w-full">
                    Order Birth Chart Bracelet →
                </a>
            </div>

            <!-- Card 2: Customized Bracelet -->
            <div class="bracelet-card animate-on-scroll">
                <div>
                    <div class="bracelet-icon-circle">✨</div>
                    <h3 class="bracelet-card-title">Customized Bracelet</h3>
                    <p>Select your desired life intention — whether for financial abundance, heart healing, anxiety relief, or psychic protection — tailored to your energy field.</p>
                    <ul class="bracelet-features">
                        <li><span class="feature-check">✓</span> Custom intention programming</li>
                        <li><span class="feature-check">✓</span> Handpicked high-frequency crystals</li>
                        <li><span class="feature-check">✓</span> Personal aura & chakra alignment</li>
                    </ul>
                </div>
                <a href="<?php echo BASE_URL; ?>custom-bracelet.php?type=customized" class="btn-gold w-full">
                    Order Customized Bracelet →
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     4. SERVICES SECTION
     ========================================================================== -->
<section class="services-section" id="services">
    <div class="container">
        <div class="section-header-flex animate-on-scroll">
            <div>
                <span class="section-label">Holistic Healing Modalities</span>
                <h2 class="section-heading">Our Core <em>Services</em></h2>
            </div>
            <a href="<?php echo BASE_URL; ?>services.php" class="view-all-link">View all services →</a>
        </div>

        <!-- Horizontal Scrollable Service Cards -->
        <div class="scroll-cards-row">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <div class="scroll-card">
                        <div class="card-img-box">
                            <img src="<?php echo htmlspecialchars($service['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" loading="lazy">
                            <?php if ($service['is_free']): ?>
                                <span class="badge badge-green card-badge-pos">FREE SESSION</span>
                            <?php else: ?>
                                <span class="badge card-badge-pos"><?php echo htmlspecialchars($service['duration_minutes']); ?> mins</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body-content">
                            <div>
                                <span class="card-repeat-title"><?php echo htmlspecialchars($service['title']); ?></span>
                                <h3 class="card-main-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="card-desc-text"><?php echo htmlspecialchars($service['short_description']); ?></p>
                            </div>
                            <div class="card-footer-meta">
                                <div>
                                    <?php if ($service['is_free'] || $service['price'] === null): ?>
                                        <span class="card-price-value" style="color: var(--soft-green-text);">Free</span>
                                    <?php else: ?>
                                        <span class="card-price-value">₹<?php echo number_format($service['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo BASE_URL; ?>services.php#book" class="btn-primary btn-book-nav">Book Session →</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No services currently listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     5. TESTIMONIAL IMAGES CAROUSEL
     ========================================================================== -->
<section class="testimonials-photos-section" style="padding: 40px 0; background-color: var(--light-cream);">
    <div class="infinite-carousel testimonials-photos-row">
        <div class="infinite-carousel-track">
            <div class="testimonial-photo-item"><img src="assets/images/testimonials/sunita.jpg" alt="Client Sunita" loading="lazy"></div>
            <div class="testimonial-photo-item"><img src="assets/images/testimonials/rahul.jpg" alt="Student Rahul" loading="lazy"></div>
            <div class="testimonial-photo-item"><img src="assets/images/testimonials/kavita.jpg" alt="Client Kavita" loading="lazy"></div>
            <div class="testimonial-photo-item"><img src="assets/images/team/ananya-sharma.jpg" alt="Master Ananya" loading="lazy"></div>
            <div class="testimonial-photo-item"><img src="assets/images/team/rajesh-varma.jpg" alt="Master Rajesh" loading="lazy"></div>
            <div class="testimonial-photo-item"><img src="assets/images/team/priya-nair.jpg" alt="Master Priya" loading="lazy"></div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     6. TESTIMONIALS TEXT SECTION
     ========================================================================== -->
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="text-center animate-on-scroll" style="margin-bottom: 50px;">
            <span class="section-label">Stories of Healing</span>
            <h2 class="section-heading">What Our Students & Clients <em>Say</em></h2>
            <p>Read real life experiences from individuals who restored harmony, vitality, and peace through our Reiki sessions.</p>
        </div>

        <div class="testimonials-grid">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $t): ?>
                    <div class="testimonial-card-box animate-on-scroll">
                        <div class="quote-mark">“</div>
                        <p class="testimonial-text-body"><?php echo htmlspecialchars($t['content']); ?></p>
                        <div class="testimonial-author-row">
                            <?php if (!empty($t['image'])): ?>
                                <div class="author-initial-circle" style="padding: 0; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($t['image']); ?>" alt="<?php echo htmlspecialchars($t['client_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php else: ?>
                                <div class="author-initial-circle">
                                    <?php echo strtoupper(substr($t['client_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="author-info-text">
                                <strong><?php echo htmlspecialchars($t['client_name']); ?></strong>
                                <span><?php echo htmlspecialchars($t['location']); ?> · ⭐⭐⭐⭐⭐</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No testimonials available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     7. COURSES SECTION
     ========================================================================== -->
<section class="courses-section" id="courses">
    <div class="container">
        <div class="section-header-flex animate-on-scroll">
            <div>
                <span class="section-label">Certified Energy Training</span>
                <h2 class="section-heading">Explore Reiki & Healing <em>Courses</em></h2>
            </div>
            <a href="<?php echo BASE_URL; ?>courses.php" class="view-all-link">View all courses →</a>
        </div>

        <!-- Horizontal Scrollable Course Cards -->
        <div class="scroll-cards-row">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="scroll-card">
                        <div class="card-img-box">
                            <img src="<?php echo htmlspecialchars($course['image'] ?: 'assets/images/courses/reiki-level-1.jpg'); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" loading="lazy">
                        </div>
                        <div class="card-body-content">
                            <div>
                                <span class="card-repeat-title"><?php echo htmlspecialchars($course['title']); ?></span>
                                <h3 class="card-main-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="card-desc-text"><?php echo htmlspecialchars($course['short_description']); ?></p>
                            </div>
                            <div class="card-footer-meta">
                                <span class="card-price-value"><?php echo htmlspecialchars($course['price_text']); ?></span>
                                <a href="<?php echo BASE_URL; ?>courses.php" class="btn-secondary" style="padding: 6px 16px; font-size: 0.85rem;">View details →</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses currently listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     8. PRODUCTS SECTION
     ========================================================================== -->
<section class="products-section" id="products">
    <div class="container">
        <div class="section-header-flex animate-on-scroll">
            <div>
                <span class="section-label">Sacred Crystal Energy</span>
                <h2 class="section-heading">Featured Reiki Charged <em>Products</em></h2>
            </div>
            <a href="<?php echo BASE_URL; ?>shop.php" class="view-all-link">View all products →</a>
        </div>

        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <a href="<?php echo BASE_URL; ?>product-detail.php?slug=<?php echo htmlspecialchars($prod['slug']); ?>" class="product-card-item animate-on-scroll">
                        <div class="product-img-box">
                            <img src="<?php echo htmlspecialchars($prod['image'] ?: 'assets/images/products/amethyst-bracelet.jpg'); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" loading="lazy">
                            <?php if ($prod['discount_percent'] > 0): ?>
                                <span class="badge badge-gold product-badge-tag"><?php echo htmlspecialchars($prod['discount_percent']); ?>% OFF ✦ <?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php else: ?>
                                <span class="badge product-badge-tag"><?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-details-body">
                            <div>
                                <span class="product-category-name"><?php echo htmlspecialchars($prod['category']); ?></span>
                                <h3 class="product-title-text"><?php echo htmlspecialchars($prod['title']); ?></h3>
                            </div>
                            <div class="product-price-row">
                                <span class="sale-price">₹<?php echo number_format($prod['price'], 2); ?></span>
                                <?php if ($prod['original_price']): ?>
                                    <span class="original-price">₹<?php echo number_format($prod['original_price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products currently listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
