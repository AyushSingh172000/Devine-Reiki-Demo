<?php
// Main Header Component
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
require_once __DIR__ . '/../config/constants.php';

// Default page metadata fallbacks
$pageTitle = $pageTitle ?? SITE_NAME . ' | Authentic Usui Reiki & Energy Healing';
$pageDescription = $pageDescription ?? 'Discover authentic Usui Reiki healing sessions, certified courses, and Reiki-charged crystal bracelets at Reiki Bliss.';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri = $_SERVER['REQUEST_URI'] ?? '/reikibliss/';
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$scheme = $isHttps ? 'https' : 'http';
$currentUrl = "{$scheme}://{$host}{$uri}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <?php
    $faviconUrl = !empty($siteSettings['favicon_path']) ? BASE_URL . ltrim($siteSettings['favicon_path'], '/') : BASE_URL . 'assets/images/favicon-circle.png';
    $logoUrl = !empty($siteSettings['logo_path']) ? BASE_URL . ltrim($siteSettings['logo_path'], '/') : BASE_URL . 'assets/images/reikilogo1.png';
    $docRoot = dirname(__DIR__);
    $appleTouchUrl = !empty($siteSettings['apple_touch_icon_path']) && file_exists($docRoot . '/' . ltrim($siteSettings['apple_touch_icon_path'], '/'))
        ? BASE_URL . ltrim($siteSettings['apple_touch_icon_path'], '/')
        : $faviconUrl;
    
    // Resolve Open Graph Social Image (Priority: Page Override -> Settings OG Image -> Apple Touch Icon -> og-image.jpg -> og-image.png -> Logo)
    if (!empty($pageOgImage)) {
        $rawOgImage = $pageOgImage;
    } elseif (!empty($siteSettings['og_image_path']) && file_exists($docRoot . '/' . ltrim($siteSettings['og_image_path'], '/'))) {
        $rawOgImage = $siteSettings['og_image_path'];
    } elseif (!empty($siteSettings['apple_touch_icon_path']) && file_exists($docRoot . '/' . ltrim($siteSettings['apple_touch_icon_path'], '/'))) {
        $rawOgImage = $siteSettings['apple_touch_icon_path'];
    } elseif (file_exists($docRoot . '/assets/images/og-image.jpg')) {
        $rawOgImage = 'assets/images/og-image.jpg';
    } elseif (file_exists($docRoot . '/assets/images/og-image.png')) {
        $rawOgImage = 'assets/images/og-image.png';
    } elseif (!empty($siteSettings['logo_path']) && file_exists($docRoot . '/' . ltrim($siteSettings['logo_path'], '/'))) {
        $rawOgImage = $siteSettings['logo_path'];
    } else {
        $rawOgImage = 'assets/images/logo.png';
    }

    if (strpos($rawOgImage, 'http://') === 0 || strpos($rawOgImage, 'https://') === 0) {
        $ogImageUrl = $rawOgImage;
    } else {
        $ogImageUrl = BASE_URL . ltrim($rawOgImage, '/');
    }

    // Secure HTTPS image URL for WhatsApp / Facebook
    $ogImageSecureUrl = preg_replace('/^http:\/\//i', 'https://', $ogImageUrl);

    // Compute dimensions, MIME type and cache-busting version
    $localOgPath = $docRoot . '/' . ltrim(parse_url($rawOgImage, PHP_URL_PATH) ?? $rawOgImage, '/');
    $ogWidth = 1200;
    $ogHeight = 630;
    $ogMime = 'image/jpeg';
    $ogVer = time();

    if (file_exists($localOgPath)) {
        $ogVer = filemtime($localOgPath);
        $imgDetails = @getimagesize($localOgPath);
        if ($imgDetails && !empty($imgDetails[0]) && !empty($imgDetails[1])) {
            $ogWidth = $imgDetails[0];
            $ogHeight = $imgDetails[1];
            $ogMime = $imgDetails['mime'] ?? 'image/jpeg';
        }
    } else {
        $ext = strtolower(pathinfo(parse_url($ogImageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if ($ext === 'png') {
            $ogMime = 'image/png';
        } elseif ($ext === 'webp') {
            $ogMime = 'image/webp';
        }
    }

    // Append version hash so WhatsApp / Facebook crawlers never serve stale cached image on update
    $ogImageUrlVersioned = $ogImageUrl . (strpos($ogImageUrl, '?') !== false ? '&' : '?') . 'v=' . $ogVer;
    $ogImageSecureUrlVersioned = $ogImageSecureUrl . (strpos($ogImageSecureUrl, '?') !== false ? '&' : '?') . 'v=' . $ogVer;

    $bookingHref = !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : (defined('BOOKING_URL') ? BOOKING_URL : '#');
    ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($appleTouchUrl); ?>">

    <!-- Open Graph / WhatsApp / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(SITE_NAME); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImageUrlVersioned); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($ogImageSecureUrlVersioned); ?>">
    <meta property="og:image:type" content="<?php echo htmlspecialchars($ogMime); ?>">
    <meta property="og:image:width" content="<?php echo (int)$ogWidth; ?>">
    <meta property="og:image:height" content="<?php echo (int)$ogHeight; ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:locale" content="en_US">

    <!-- Schema.org item tags for messaging crawlers (WhatsApp, Telegram, Slack) -->
    <meta itemprop="name" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta itemprop="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta itemprop="image" content="<?php echo htmlspecialchars($ogImageUrlVersioned); ?>">

    <!-- Twitter (X) Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImageUrlVersioned); ?>">

    <!-- WhatsApp Legacy Mobile Thumbnail Fallback -->
    <link rel="image_src" href="<?php echo htmlspecialchars($ogImageUrlVersioned); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>">

    <!-- JSON-LD Structured Data -->
    <?php if ($currentPage == 'index.php' || $currentPage == ''): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HealthAndBeautyBusiness",
      "name": "Reiki Bliss",
      "alternateName": "Reiki Bliss Healing Center",
      "url": "<?php echo htmlspecialchars($currentUrl); ?>",
      "logo": "<?php echo BASE_URL; ?>assets/images/favicon.svg",
      "image": "<?php echo BASE_URL; ?>assets/images/hero-bg.jpg",
      "description": "<?php echo htmlspecialchars($pageDescription); ?>",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "108 Healing Touch Way, Spiritual Enclave",
        "addressLocality": "Adajan, Surat",
        "addressRegion": "Gujarat",
        "postalCode": "395009",
        "addressCountry": "IN"
      },
      "telephone": "<?php echo SITE_PHONE; ?>",
      "priceRange": "₹₹",
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
          "opens": "07:00",
          "closes": "18:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Sunday",
          "opens": "09:00",
          "closes": "13:00"
        }
      ]
    }
    </script>
    <?php else: ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Home",
          "item": "<?php echo BASE_URL; ?>"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "<?php echo htmlspecialchars($pageTitle); ?>",
          "item": "<?php echo htmlspecialchars($currentUrl); ?>"
        }
      ]
    }
    </script>
    <?php endif; ?>

    <!-- Google Fonts: Cormorant Garamond (Headings) & Plus Jakarta Sans (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets with Dynamic Cache-Busting -->
    <?php
    $cssDir = dirname(__DIR__) . '/assets/css';
    $styleVer = file_exists($cssDir . '/style.css') ? filemtime($cssDir . '/style.css') : time();
    $headerVer = file_exists($cssDir . '/header.css') ? filemtime($cssDir . '/header.css') : time();
    $footerVer = file_exists($cssDir . '/footer.css') ? filemtime($cssDir . '/footer.css') : time();
    $homeVer = file_exists($cssDir . '/home.css') ? filemtime($cssDir . '/home.css') : time();
    $respVer = file_exists($cssDir . '/responsive.css') ? filemtime($cssDir . '/responsive.css') : time();
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $styleVer; ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css?v=<?php echo $headerVer; ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css?v=<?php echo $footerVer; ?>">
    <?php if ($currentPage == 'index.php' || $currentPage == ''): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/home.css?v=<?php echo $homeVer; ?>">
    <?php endif; ?>
    <?php if ($currentPage == 'about.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/about.css?v=<?php echo file_exists($cssDir . '/about.css') ? filemtime($cssDir . '/about.css') : time(); ?>">
    <?php endif; ?>
    <?php if ($currentPage == 'services.php' || $currentPage == 'service-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/services.css?v=<?php echo file_exists($cssDir . '/services.css') ? filemtime($cssDir . '/services.css') : time(); ?>">
    <?php endif; ?>
    <?php if ($currentPage == 'courses.php' || $currentPage == 'course-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/courses.css?v=<?php echo file_exists($cssDir . '/courses.css') ? filemtime($cssDir . '/courses.css') : time(); ?>">
    <?php endif; ?>
    <?php if ($currentPage == 'products.php' || $currentPage == 'shop.php' || $currentPage == 'product-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/products.css?v=<?php echo file_exists($cssDir . '/products.css') ? filemtime($cssDir . '/products.css') : time(); ?>">
    <?php endif; ?>
    <?php if ($currentPage == 'contact.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/contact.css?v=<?php echo file_exists($cssDir . '/contact.css') ? filemtime($cssDir . '/contact.css') : time(); ?>">
    <?php endif; ?>
    <?php if ($currentPage == 'order-bracelet.php' || $currentPage == 'custom-bracelet.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/order-bracelet.css?v=<?php echo file_exists($cssDir . '/order-bracelet.css') ? filemtime($cssDir . '/order-bracelet.css') : time(); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css?v=<?php echo $respVer; ?>">
    
    <!-- Google Calendar Appointment Scheduling Stylesheet -->
    <link href="https://calendar.google.com/calendar/scheduling-button-script.css" rel="stylesheet">
</head>
<body>

<!-- Header & Navigation Bar -->
<header class="site-header" id="site-header">
    <!-- Main Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container navbar-container">
            <!-- Brand Logo -->
            <a href="<?php echo BASE_URL; ?>" class="navbar-logo">
                <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" class="brand-logo-img">
                <span class="logo-text">
                    <strong class="logo-title"><?php echo htmlspecialchars(SITE_NAME); ?></strong>
                    <span class="logo-subtitle"><?php echo htmlspecialchars(SITE_TAGLINE); ?></span>
                </span>
            </a>

            <!-- Desktop Navigation Links (Original Structure with Light Theme) -->
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo ($currentPage == 'index.php' || $currentPage == '') ? 'active' : ''; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>services.php" class="nav-link <?php echo ($currentPage == 'services.php' || $currentPage == 'service-detail.php') ? 'active' : ''; ?>">Services</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>courses.php" class="nav-link <?php echo ($currentPage == 'courses.php' || $currentPage == 'course-detail.php') ? 'active' : ''; ?>">Courses</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>products.php" class="nav-link <?php echo ($currentPage == 'products.php' || $currentPage == 'shop.php' || $currentPage == 'product-detail.php') ? 'active' : ''; ?>">Shop</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>about.php" class="nav-link <?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>">About Us</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>contact.php" class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">Contact Us</a>
                </li>
            </ul>

            <!-- Navbar Actions (CTA & Hamburger) -->
            <div class="navbar-actions">
                <a href="<?php echo htmlspecialchars($bookingHref); ?>" target="_blank" rel="noopener" class="btn-primary btn-book-nav">Book Session</a>
                
                <!-- Mobile Hamburger Toggle -->
                <button class="hamburger-toggle" id="hamburger-toggle" aria-label="Toggle Navigation Menu">
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Full-Screen Overlay Navigation -->
    <div class="mobile-nav-overlay" id="mobile-nav-overlay">
        <div class="mobile-nav-content">
            <ul class="mobile-nav-menu">
                <li><a href="<?php echo BASE_URL; ?>index.php" class="mobile-nav-link">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php" class="mobile-nav-link">Services</a></li>
                <li><a href="<?php echo BASE_URL; ?>courses.php" class="mobile-nav-link">Courses</a></li>
                <li><a href="<?php echo BASE_URL; ?>products.php" class="mobile-nav-link">Shop</a></li>
                <li><a href="<?php echo BASE_URL; ?>about.php" class="mobile-nav-link">About Us</a></li>
                <li><a href="<?php echo BASE_URL; ?>contact.php" class="mobile-nav-link">Contact Us</a></li>
            </ul>

            <div class="mobile-nav-cta">
                <a href="<?php echo htmlspecialchars($bookingHref); ?>" target="_blank" rel="noopener" class="btn-primary w-full">Book Session</a>
            </div>
        </div>
    </div>
</header>
