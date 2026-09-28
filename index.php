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

<?php
// =========================================================================
// SUPER ADMIN GLOBAL SITE CONTROLS & OVERRIDES
// =========================================================================
$saToggles = !empty($siteSettings['superadmin_site_toggles']) ? json_decode($siteSettings['superadmin_site_toggles'], true) : [];
$isSuperAdmin = !empty($_SESSION['superadmin_logged_in']);

// Maintenance Mode Check
if (!empty($saToggles['maintenance_mode']) && !$isSuperAdmin) {
    $maintMsg = !empty($saToggles['maintenance_msg']) ? $saToggles['maintenance_msg'] : null;
    $maintFile = __DIR__ . '/superadmin/maintenance.php';
    if (file_exists($maintFile)) {
        include $maintFile;
    }
    exit;
}

// Global Top Announcement Ribbon
if (!empty($saToggles['announcement_enabled']) && !empty($saToggles['announcement_text'])): ?>
    <div style="background: linear-gradient(90deg, #d97706, #f59e0b, #b45309); color: #000; font-size: 0.86rem; font-weight: 700; text-align: center; padding: 8px 16px; letter-spacing: 0.03em; z-index: 9999; position: relative;">
        <?= htmlspecialchars($saToggles['announcement_text']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($saToggles['maintenance_mode']) && $isSuperAdmin): ?>
    <div style="background: #ef4444; color: #fff; font-size: 0.8rem; font-weight: 700; text-align: center; padding: 6px; z-index: 9999; position: relative;">
        ⚠️ MAINTENANCE MODE IS ACTIVE &mdash; Bypassed because you are logged in as Super Admin.
    </div>
<?php endif; ?>

<?php
// =========================================================================
// SUPER ADMIN DYNAMIC HOMEPAGE SECTION ARCHITECTURE ENGINE
// =========================================================================
$defaultHomepageLayout = [
    ['id' => 'hero', 'name' => 'Hero Banner & Trust Stats', 'visible' => true, 'align' => 'center'],
    ['id' => 'services', 'name' => 'Core Services & Modalities', 'visible' => true, 'align' => 'left'],
    ['id' => 'testimonials', 'name' => 'Testimonials & Student Reviews', 'visible' => true, 'align' => 'center'],
    ['id' => 'courses', 'name' => 'Reiki & Energy Courses', 'visible' => true, 'align' => 'left'],
    ['id' => 'products', 'name' => 'Featured Products & Crystals', 'visible' => true, 'align' => 'left'],
    ['id' => 'reels', 'name' => 'Instagram Reels Showcase', 'visible' => true, 'align' => 'left']
];

$activeHomepageLayout = $defaultHomepageLayout;
if (!empty($siteSettings['superadmin_homepage_layout'])) {
    $customLayout = json_decode($siteSettings['superadmin_homepage_layout'], true);
    if (is_array($customLayout) && !empty($customLayout)) {
        $activeHomepageLayout = $customLayout;
    }
}

// Render dynamic sections in Super Admin designated order
foreach ($activeHomepageLayout as $sectionItem) {
    if (empty($sectionItem['visible'])) continue;
    $secId = $sectionItem['id'] ?? '';
    $secAlign = $sectionItem['align'] ?? 'left';
    $sectionFile = __DIR__ . '/superadmin/sections/' . $secId . '.php';

    if (file_exists($sectionFile)) {
        include $sectionFile;
    }
}
?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
