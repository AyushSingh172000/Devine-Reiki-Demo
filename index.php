<?php
// Homepage - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';

// Check for subpaths appended after index.php (e.g. index.php/admin or index.php/anything-else)
$pathInfo = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/');
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    $parsedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/index\.php(?:/(.*))?$#i', $parsedPath, $matches)) {
        $pathInfo = isset($matches[1]) ? trim($matches[1], '/') : '';
    }
}

if (!empty($pathInfo)) {
    if (strtolower($pathInfo) === 'admin' || strtolower($pathInfo) === 'admin/login') {
        header('Location: ' . BASE_URL . 'admin/login.php', true, 302);
        exit;
    } else {
        // Any other subpath after index.php -> show 404 error page
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Helper to resolve media URLs across uploads, assets, and external links
if (!function_exists('getCarouselImgUrl')) {
    function getCarouselImgUrl($imgPath, $fallback = '') {
        if (empty($imgPath)) {
            return !empty($fallback) ? (strpos($fallback, 'http') === 0 ? $fallback : BASE_URL . ltrim($fallback, '/')) : '';
        }
        if (strpos($imgPath, 'http://') === 0 || strpos($imgPath, 'https://') === 0) {
            return $imgPath;
        }
        return BASE_URL . ltrim($imgPath, '/');
    }
}

// Page Metadata
$pageTitle = "Reiki Bliss | Heal. Balance. Transform.";
$pageDescription = "Experience authentic Usui Reiki healing, certified courses, and Reiki-charged crystal bracelets guided by Reiki Grandmaster Anupama Agrawal at Reiki Bliss.";

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

    // 4. Products (active, sorted, limit 12)
    $productsStmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 12");
    $products = $productsStmt ? $productsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 5. Testimonials (active)
    $testimonialsStmt = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC");
    $testimonials = $testimonialsStmt ? $testimonialsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 6. Team Members (active)
    $teamMembersStmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $teamMembers = $teamMembersStmt ? $teamMembersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 7. Gallery Images (active, crystal & bracelet categories)
    $galleryBraceletsStmt = $pdo->query("SELECT image_path, caption FROM gallery_images WHERE is_active = 1 AND (category LIKE '%Crystal%' OR category LIKE '%Bracelet%' OR category = 'Crystals') ORDER BY sort_order ASC");
    $galleryBracelets = $galleryBraceletsStmt ? $galleryBraceletsStmt->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
    $services = $services ?? [];
    $courses = $courses ?? [];
    $products = $products ?? [];
    $testimonials = $testimonials ?? [];
    $teamMembers = $teamMembers ?? [];
    $galleryBracelets = $galleryBracelets ?? [];
}

// -------------------------------------------------------------------------
// Build Dynamic Crystal Bracelet Carousel List (from Products & Gallery)
// -------------------------------------------------------------------------
$braceletCarouselImages = [];

if (!empty($products)) {
    foreach ($products as $prod) {
        if (!empty($prod['image'])) {
            $braceletCarouselImages[] = [
                'src' => $prod['image'],
                'alt' => $prod['title'] ?? 'Crystal Bracelet'
            ];
        }
        if (!empty($prod['additional_images'])) {
            $extraList = is_string($prod['additional_images']) ? json_decode($prod['additional_images'], true) : $prod['additional_images'];
            if (is_array($extraList)) {
                foreach ($extraList as $extraImg) {
                    if (!empty($extraImg)) {
                        $braceletCarouselImages[] = [
                            'src' => $extraImg,
                            'alt' => ($prod['title'] ?? 'Crystal Bracelet') . ' Detail'
                        ];
                    }
                }
            }
        }
    }
}

if (!empty($galleryBracelets)) {
    foreach ($galleryBracelets as $gItem) {
        if (!empty($gItem['image_path'])) {
            $braceletCarouselImages[] = [
                'src' => $gItem['image_path'],
                'alt' => $gItem['caption'] ?: 'Crystal Gemstone'
            ];
        }
    }
}

$braceletFallbacks = [
    ['src' => 'assets/images/products/amethyst-bracelet.jpg', 'alt' => 'Reiki Amethyst Bracelet'],
    ['src' => 'assets/images/products/7-chakra-bracelet.jpg', 'alt' => '7 Chakra Bracelet'],
    ['src' => 'assets/images/products/rose-quartz-bracelet.jpg', 'alt' => 'Rose Quartz Love Bracelet'],
    ['src' => 'assets/images/products/black-tourmaline-bracelet.jpg', 'alt' => 'Black Tourmaline Bracelet'],
    ['src' => 'assets/images/products/amethyst-1.jpg', 'alt' => 'Natural Amethyst Beads'],
    ['src' => 'assets/images/products/chakra-1.jpg', 'alt' => 'Chakra Alignment Gemstones'],
    ['src' => 'assets/images/products/rosequartz-1.jpg', 'alt' => 'Rose Quartz Energy'],
    ['src' => 'assets/images/products/tourmaline-1.jpg', 'alt' => 'Protection Crystal Beads']
];

if (empty($braceletCarouselImages)) {
    $braceletCarouselImages = $braceletFallbacks;
} else {
    // Deduplicate by image src
    $seen = [];
    $deduped = [];
    foreach ($braceletCarouselImages as $bImg) {
        if (!isset($seen[$bImg['src']])) {
            $seen[$bImg['src']] = true;
            $deduped[] = $bImg;
        }
    }
    $braceletCarouselImages = $deduped;

    // Ensure at least 6 items for smooth infinite auto-scrolling marquee
    if (count($braceletCarouselImages) < 6) {
        $orig = $braceletCarouselImages;
        while (count($braceletCarouselImages) < 6) {
            foreach ($orig as $itm) {
                $braceletCarouselImages[] = $itm;
                if (count($braceletCarouselImages) >= 6) break;
            }
        }
    }
}

// -------------------------------------------------------------------------
// Build Dynamic Healer & Testimonial Carousel List (from Team & Testimonials)
// -------------------------------------------------------------------------
$healerClientCarouselImages = [];

if (!empty($teamMembers)) {
    foreach ($teamMembers as $tm) {
        if (!empty($tm['image'])) {
            $healerClientCarouselImages[] = [
                'src' => $tm['image'],
                'alt' => $tm['name'] ?? 'Reiki Master'
            ];
        }
    }
}

if (!empty($testimonials)) {
    foreach ($testimonials as $t) {
        if (!empty($t['image'])) {
            $healerClientCarouselImages[] = [
                'src' => $t['image'],
                'alt' => $t['client_name'] ?? 'Client Story'
            ];
        }
    }
}

$healerClientFallbacks = [
    ['src' => 'assets/images/testimonials/sunita.jpg', 'alt' => 'Client Sunita'],
    ['src' => 'assets/images/testimonials/rahul.jpg', 'alt' => 'Student Rahul'],
    ['src' => 'assets/images/testimonials/kavita.jpg', 'alt' => 'Client Kavita'],
    ['src' => 'assets/images/team/ananya-sharma.jpg', 'alt' => 'Master Ananya'],
    ['src' => 'assets/images/team/rajesh-varma.jpg', 'alt' => 'Master Rajesh'],
    ['src' => 'assets/images/team/priya-nair.jpg', 'alt' => 'Master Priya']
];

if (empty($healerClientCarouselImages)) {
    $healerClientCarouselImages = $healerClientFallbacks;
} else {
    // Deduplicate by image src
    $seen = [];
    $deduped = [];
    foreach ($healerClientCarouselImages as $hcImg) {
        if (!isset($seen[$hcImg['src']])) {
            $seen[$hcImg['src']] = true;
            $deduped[] = $hcImg;
        }
    }
    $healerClientCarouselImages = $deduped;

    // Ensure at least 6 items for smooth infinite auto-scrolling marquee
    if (count($healerClientCarouselImages) < 6) {
        $orig = $healerClientCarouselImages;
        while (count($healerClientCarouselImages) < 6) {
            foreach ($orig as $itm) {
                $healerClientCarouselImages[] = $itm;
                if (count($healerClientCarouselImages) >= 6) break;
            }
        }
    }
}

// Dynamic Hero Settings from $siteSettings and $siteStats
$heroBadge = !empty($siteSettings['hero_badge']) ? $siteSettings['hero_badge'] : ('● ADAJAN, SURAT · EST. ' . ($siteSettings['founding_year'] ?? '2016'));
$heroTitle = $siteSettings['hero_title'] ?? 'Awaken Inner Harmony.';
$heroTitleGold = $siteSettings['hero_title_gold'] ?? 'Heal. Balance. Transform.';
$heroHeadingRaw = $siteSettings['hero_heading'] ?? '';

// Build dynamic headline
if (!empty($heroHeadingRaw) && empty($siteSettings['hero_title'])) {
    if (strpos($heroHeadingRaw, '<') !== false) {
        $heroTitleHtml = $heroHeadingRaw;
    } else {
        $heroTitleHtml = htmlspecialchars($heroHeadingRaw);
    }
} else {
    $heroTitleHtml = htmlspecialchars($heroTitle);
    if (!empty($heroTitleGold)) {
        $heroTitleHtml .= '<br><span class="hero-gold-text">' . htmlspecialchars($heroTitleGold) . '</span>';
    }
}

$heroSubtext = !empty($siteSettings['hero_subtext']) 
    ? $siteSettings['hero_subtext'] 
    : 'Guided by <strong>Grand Master Ms Anupama Agrawal</strong>: offering Reiki, Chakra Balancing, Guided Meditations, Other Healings & more.';

$heroBookingUrl = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');

// Dynamic Homepage CTAs from Site Settings
$cta1Text = !empty($siteSettings['hero_cta1_text']) ? $siteSettings['hero_cta1_text'] : 'Book Free Session';
$cta1UrlRaw = !empty($siteSettings['hero_cta1_url']) ? $siteSettings['hero_cta1_url'] : $heroBookingUrl;
$cta1Url = (strpos($cta1UrlRaw, 'http') === 0 || strpos($cta1UrlRaw, '#') === 0) ? $cta1UrlRaw : BASE_URL . ltrim($cta1UrlRaw, '/');

$cta2Text = !empty($siteSettings['hero_cta2_text']) ? $siteSettings['hero_cta2_text'] : 'Explore Courses';
$cta2UrlRaw = !empty($siteSettings['hero_cta2_url']) ? $siteSettings['hero_cta2_url'] : 'courses.php';
$cta2Url = (strpos($cta2UrlRaw, 'http') === 0 || strpos($cta2UrlRaw, '#') === 0) ? $cta2UrlRaw : BASE_URL . ltrim($cta2UrlRaw, '/');

// Dynamic Bracelets Section Headings
$braceletsHeading = !empty($siteSettings['bracelets_heading']) ? $siteSettings['bracelets_heading'] : 'Energized Astrological & Custom Crystal Bracelets';
$braceletsDescription = !empty($siteSettings['bracelets_description']) ? $siteSettings['bracelets_description'] : 'Tailored specifically according to your date and place of birth or personalized healing intentions, charged with high-frequency Reiki symbols.';

// Dynamic Hero Trust Counters & Stats
$heroStat1Num = !empty($siteSettings['hero_stat1_num']) ? (int)$siteSettings['hero_stat1_num'] : (!empty($siteStats['lives_healed']['stat_value']) ? (int)$siteStats['lives_healed']['stat_value'] : 15000);
$heroStat1Text = !empty($siteSettings['hero_stat1_text']) ? $siteSettings['hero_stat1_text'] : ($heroStat1Num >= 1000 ? floor($heroStat1Num / 1000) . 'K+' : $heroStat1Num . '+');
$heroStat1Label = !empty($siteSettings['hero_stat1_label']) ? $siteSettings['hero_stat1_label'] : 'LIVES HEALED';

$heroStat2Num = !empty($siteSettings['hero_stat2_num']) ? (int)$siteSettings['hero_stat2_num'] : (!empty($siteStats['years_experience']['stat_value']) ? (int)$siteStats['years_experience']['stat_value'] : 12);
$heroStat2Text = !empty($siteSettings['hero_stat2_text']) ? $siteSettings['hero_stat2_text'] : ($heroStat2Num . '+');
$heroStat2Label = !empty($siteSettings['hero_stat2_label']) ? $siteSettings['hero_stat2_label'] : 'YEARS EXPERIENCE';

$heroStat3Num = !empty($siteSettings['hero_stat3_num']) ? (int)$siteSettings['hero_stat3_num'] : (!empty($siteStats['course_levels']['stat_value']) ? (int)$siteStats['course_levels']['stat_value'] : 10);
$heroStat3Text = !empty($siteSettings['hero_stat3_text']) ? $siteSettings['hero_stat3_text'] : ($heroStat3Num . '+');
$heroStat3Label = !empty($siteSettings['hero_stat3_label']) ? $siteSettings['hero_stat3_label'] : 'COURSES OFFERED';

$heroStat4Num = !empty($siteSettings['hero_stat4_num']) ? (int)$siteSettings['hero_stat4_num'] : (!empty($siteStats['sessions_completed']['stat_value']) ? (int)$siteStats['sessions_completed']['stat_value'] : 8000);
$heroStat4Text = !empty($siteSettings['hero_stat4_text']) ? $siteSettings['hero_stat4_text'] : ($heroStat4Num >= 1000 ? floor($heroStat4Num / 1000) . 'K+' : $heroStat4Num . '+');
$heroStat4Label = !empty($siteSettings['hero_stat4_label']) ? $siteSettings['hero_stat4_label'] : 'SESSIONS COMPLETED';

// Dynamic Hero Background Slides
$heroSlide1 = !empty($siteSettings['hero_slide_1']) ? getCarouselImgUrl($siteSettings['hero_slide_1'], 'assets/images/hero-poster-1.jpg') : BASE_URL . 'assets/images/hero-poster-1.jpg';
$heroSlide2 = !empty($siteSettings['hero_slide_2']) ? getCarouselImgUrl($siteSettings['hero_slide_2'], 'assets/images/hero-poster-2.jpg') : BASE_URL . 'assets/images/hero-poster-2.jpg';
$heroSlide3 = !empty($siteSettings['hero_slide_3']) ? getCarouselImgUrl($siteSettings['hero_slide_3'], 'assets/images/hero-poster-3.jpg') : BASE_URL . 'assets/images/hero-poster-3.jpg';

// Include Header
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION (Luminous White & Golden Sanctuary Theme)
     ========================================================================== -->
<section class="hero-section hero-section-centered" id="hero">
    <!-- Scrolling Background Posters (Full Banner Size Scrolling Right to Left) -->
    <div class="hero-bg-scroller" aria-hidden="true">
        <div class="hero-bg-track">
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide1); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide2); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide3); ?>');"></div>
            <!-- Seamless loop duplicate -->
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide1); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide2); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide3); ?>');"></div>
        </div>
    </div>

    <!-- Dimmed Soft Aura Overlay -->
    <div class="hero-dim-overlay"></div>

    <!-- Ambient Canvas Glow Layer -->
    <canvas id="hero-bg-canvas"></canvas>

    <div class="container hero-container-center animate-on-scroll">
        <span class="hero-location-badge">
            <?php echo htmlspecialchars($heroBadge); ?>
        </span>

        <h1 class="hero-title">
            <?php echo $heroTitleHtml; ?>
        </h1>

        <div class="hero-subtext">
            <?php echo $heroSubtext; ?>
        </div>

        <div class="hero-ctas">
            <a href="<?php echo htmlspecialchars($cta1Url); ?>" target="_blank" rel="noopener" class="btn-gold btn-hero-primary">
                <?php echo htmlspecialchars($cta1Text); ?> <span class="btn-arrow">→</span>
            </a>
            <a href="<?php echo htmlspecialchars($cta2Url); ?>" class="btn-secondary btn-hero-secondary">
                <?php echo htmlspecialchars($cta2Text); ?>
            </a>
        </div>

        <!-- Centered Trust Stats Bar -->
        <div class="hero-trust-bar">
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat1Num; ?>"><?php echo htmlspecialchars($heroStat1Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat1Label); ?></div>
            </div>
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat2Num; ?>"><?php echo htmlspecialchars($heroStat2Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat2Label); ?></div>
            </div>
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat3Num; ?>"><?php echo htmlspecialchars($heroStat3Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat3Label); ?></div>
            </div>
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat4Num; ?>"><?php echo htmlspecialchars($heroStat4Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat4Label); ?></div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     4. SERVICES & 7 CHAKRAS SECTION
     ========================================================================== -->
<section class="services-section" id="services">
    <div id="chakras" style="position: relative; top: -80px; visibility: hidden;"></div>
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
                                <a href="<?php echo BOOKING_URL; ?>" target="_blank" rel="noopener" class="btn-primary btn-book-nav">Book Session →</a>
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
            <?php foreach ($healerClientCarouselImages as $hcItem): ?>
                <div class="testimonial-photo-item">
                    <img src="<?php echo htmlspecialchars(getCarouselImgUrl($hcItem['src'])); ?>" alt="<?php echo htmlspecialchars($hcItem['alt']); ?>" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     6. TESTIMONIALS TEXT SECTION
     ========================================================================== -->
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="testimonials-header-wrap animate-on-scroll">
            <div class="testimonials-header-center">
                <span class="section-label">• STORIES OF HEALING</span>
                <h2 class="section-heading">What Our Students & Clients <em>Say</em></h2>
                <p>Read real life experiences from individuals who restored harmony, vitality, and peace through our Reiki sessions.</p>
            </div>
            <div class="testimonials-nav-btns">
                <button type="button" class="testimonial-nav-btn prev-btn" id="testimonialPrevBtn" onclick="scrollTestimonials('prev')" aria-label="Previous Testimonials">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button type="button" class="testimonial-nav-btn next-btn" id="testimonialNextBtn" onclick="scrollTestimonials('next')" aria-label="Next Testimonials">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>

        <div class="testimonials-single-row" id="testimonialsSlider">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $t): ?>
                    <?php 
                        $ratingNum = max(1, min(5, (int)($t['rating'] ?? 5)));
                        $starsHtml = str_repeat('★', $ratingNum) . str_repeat('☆', 5 - $ratingNum);
                    ?>
                    <div class="testimonial-card-box">
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
                                <span>
                                    <?php if (!empty($t['location'])): ?>
                                        <?php echo htmlspecialchars($t['location']); ?> · 
                                    <?php endif; ?>
                                    <span style="color: #d97706; letter-spacing: 1.5px; font-size: 0.95rem;"><?php echo $starsHtml; ?></span>
                                </span>
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

<script>
function scrollTestimonials(direction) {
    var slider = document.getElementById('testimonialsSlider');
    if (!slider) return;
    var firstCard = slider.querySelector('.testimonial-card-box');
    var step = firstCard ? (firstCard.getBoundingClientRect().width + 28) : 360;
    var maxScroll = slider.scrollWidth - slider.clientWidth;

    if (direction === 'next') {
        if (slider.scrollLeft >= maxScroll - 20) {
            slider.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: step, behavior: 'smooth' });
        }
    } else {
        if (slider.scrollLeft <= 20) {
            slider.scrollTo({ left: maxScroll, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: -step, behavior: 'smooth' });
        }
    }
}
</script>

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
                                <a href="<?php echo BASE_URL; ?><?php echo !empty($course['slug']) ? 'course-detail.php?slug=' . urlencode($course['slug']) : 'courses.php'; ?>" class="btn-primary" style="padding: 7px 18px; font-size: 0.86rem;">View details →</a>
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

        <!-- Horizontal Scrollable Product Cards -->
        <div class="scroll-cards-row products-scroll-row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <a href="<?php echo BASE_URL; ?>product-detail.php?slug=<?php echo htmlspecialchars($prod['slug']); ?>" class="product-card-item animate-on-scroll">
                        <div class="product-img-box">
                            <img src="<?php echo htmlspecialchars($prod['image'] ?: 'assets/images/products/amethyst-bracelet.jpg'); ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" loading="lazy">
                            <?php if (!$prod['in_stock']): ?>
                                <span class="badge badge-danger product-badge-tag">Out of Stock</span>
                            <?php elseif ($prod['discount_percent'] > 0): ?>
                                <span class="badge badge-gold product-badge-tag"><?php echo htmlspecialchars($prod['discount_percent']); ?>% OFF ✦ <?php echo htmlspecialchars($prod['badge_text']); ?></span>
                            <?php elseif (!empty($prod['badge_text'])): ?>
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

<!-- ==========================================================================
     9. INSTAGRAM REELS SHOWCASE & PLAYABLE THEATER SECTION (#instagram-reels)
     ========================================================================== -->
<?php
$instagram_reels = [
    [
        'id' => 'C7nY4MESYo7',
        'title' => '2-Min Sacred Exercise to Relieve Tension & Overthinking',
        'category' => 'Sacred Mudra'
    ],
    [
        'id' => 'C7gIJshyc6G',
        'title' => 'Sacred Mudra Science for Emotional Peace & Mental Clarity',
        'category' => 'Mudra Science'
    ],
    [
        'id' => 'C4La4vmSWaA',
        'title' => 'Reiki & Acupressure Points for Deep Restful Sleep',
        'category' => 'Aura Healing'
    ],
    [
        'id' => 'C6IZ9FcMKV0',
        'title' => 'Learn Reiki Healing: Cosmic Energy & Self-Healing Course',
        'category' => 'Spiritual Wisdom'
    ],
    [
        'id' => 'DZhpe4Cp_D7',
        'title' => 'Reiki Energy Channelization & Divine Blessings',
        'category' => 'Attunement'
    ],
    [
        'id' => 'C4xHuoQyzvp',
        'title' => '5-Minute Daily Mudra Ritual for Chakra Balance',
        'category' => 'Energy Reset'
    ]
];
?>
<section class="reels-section" id="instagram-reels">
    <div class="container">
        <!-- Section Header -->
        <div class="reels-header-wrap animate-on-scroll">
            <div class="reels-title-box">
                <div class="insta-live-pill">
                    <span class="insta-pulse-dot"></span>
                    <svg class="insta-gradient-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    <span>@reiki_bliss · 104K Spiritual Seekers</span>
                </div>
                <h2 class="section-heading">Watch Our Healing <em>Reels &amp; Stories</em></h2>
                <p class="reels-subtext">
                    Daily energy resets, sacred mudras, and real healing wisdom shared by Reiki Grandmaster Anupama Agrawal. Tap any reel to play directly on this website.
                </p>
            </div>
            
            <div class="reels-header-cta-group">
                <a href="https://www.instagram.com/reiki_bliss/" target="_blank" rel="noopener noreferrer" class="btn-insta-brand">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    Follow on Instagram →
                </a>
            </div>
        </div>

        <!-- Reels Directly Playable Grid -->
        <div class="reels-cards-grid animate-on-scroll">
            <?php foreach ($instagram_reels as $idx => $reel): ?>
                <div class="reel-embed-card" data-reel-id="<?php echo htmlspecialchars($reel['id']); ?>">
                    <div class="reel-floating-header">
                        <span class="reel-badge-pill">✨ <?php echo htmlspecialchars($reel['category']); ?></span>
                        <span class="reel-brand-pill">
                            <span class="reel-pulse-dot"></span>
                            @reiki_bliss
                        </span>
                    </div>
                    <div class="reel-embed-frame">
                        <iframe 
                            class="reel-direct-iframe"
                            src="https://www.instagram.com/reel/<?php echo htmlspecialchars($reel['id']); ?>/embed/" 
                            frameborder="0" 
                            scrolling="no" 
                            allowtransparency="true" 
                            allowfullscreen="true" 
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                            loading="lazy"
                            title="<?php echo htmlspecialchars($reel['title']); ?>">
                        </iframe>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function() {
    var row = document.querySelector('.reels-cards-grid');
    var section = document.getElementById('instagram-reels');
    if (!row) return;

    var isHovered = false;
    var isVideoPlaying = false;
    var videoStartTime = 0;
    var speed = 0.8;
    var pos = 0;

    // 1. Pause on hover over reels section, resume when mouse leaves
    if (section) {
        section.addEventListener('mouseenter', function() { isHovered = true; });
        section.addEventListener('mouseleave', function() { 
            isHovered = false; 
            // If user moves mouse away from the entire section, allow resuming
            if (isVideoPlaying && (Date.now() - videoStartTime > 5000)) {
                isVideoPlaying = false;
            }
        });
    }

    // 2. Pause on touch
    row.addEventListener('touchstart', function() { isHovered = true; }, { passive: true });
    row.addEventListener('touchend', function() {
        setTimeout(function() { isHovered = false; }, 2000);
    }, { passive: true });

    // 3. Immediate window blur listener when clicking into an iframe
    window.addEventListener('blur', function() {
        setTimeout(function() {
            var active = document.activeElement;
            if (active && (active.tagName === 'IFRAME' || row.contains(active))) {
                isVideoPlaying = true;
                videoStartTime = Date.now();
            }
        }, 50);
    });

    // 4. Click outside reels section allows resuming scroll
    document.addEventListener('click', function(e) {
        if (!row.contains(e.target)) {
            isVideoPlaying = false;
        }
    });

    // 5. 60fps auto-scroll engine with active frame guard
    function step() {
        // Continuous check: if an iframe inside row has user focus, video is playing!
        var active = document.activeElement;
        if (active && active.tagName === 'IFRAME' && row.contains(active)) {
            if (!isVideoPlaying) {
                isVideoPlaying = true;
                videoStartTime = Date.now();
            }
        }

        // After full reel finishes (25 seconds), allow resuming scroll
        if (isVideoPlaying && videoStartTime > 0 && (Date.now() - videoStartTime > 25000)) {
            isVideoPlaying = false;
            videoStartTime = 0;
        }

        // Scroll only when NOT playing video and NOT hovered
        if (!isHovered && !isVideoPlaying) {
            pos += speed;
            if (pos >= 1) {
                var p = Math.floor(pos);
                row.scrollLeft += p;
                pos -= p;

                var max = row.scrollWidth - row.clientWidth;
                if (max > 0 && row.scrollLeft >= max - 2) {
                    row.scrollLeft = 0;
                }
            }
        }

        requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
})();
</script>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
