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
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$host}{$uri}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- Favicon (Circular Logo) -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-circle.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-circle.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/favicon-circle.png">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(SITE_NAME); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>assets/images/hero-bg.jpg">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>assets/images/hero-bg.jpg">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>">

    <!-- JSON-LD Structured Data -->
    <?php if ($currentPage == 'index.php' || $currentPage == ''): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HealthAndBeautyBusiness",
      "name": "Shree Sai Reiki & Yog Centre",
      "alternateName": "Divine Reiki & Energy Healing Center",
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

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
    <?php if ($currentPage == 'index.php' || $currentPage == ''): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/home.css">
    <?php endif; ?>
    <?php if ($currentPage == 'about.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/about.css">
    <?php endif; ?>
    <?php if ($currentPage == 'services.php' || $currentPage == 'service-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/services.css">
    <?php endif; ?>
    <?php if ($currentPage == 'courses.php' || $currentPage == 'course-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/courses.css">
    <?php endif; ?>
    <?php if ($currentPage == 'products.php' || $currentPage == 'shop.php' || $currentPage == 'product-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/products.css">
    <?php endif; ?>
    <?php if ($currentPage == 'gallery.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/gallery.css">
    <?php endif; ?>
    <?php if ($currentPage == 'blog.php' || $currentPage == 'blog-detail.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/blog.css">
    <?php endif; ?>
    <?php if ($currentPage == 'contact.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/contact.css">
    <?php endif; ?>
    <?php if ($currentPage == 'order-bracelet.php' || $currentPage == 'custom-bracelet.php'): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/order-bracelet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css">
</head>
<body>

<!-- Header & Navigation Bar -->
<header class="site-header" id="site-header">
    <!-- Main Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container navbar-container">
            <!-- Brand Logo -->
            <a href="<?php echo BASE_URL; ?>" class="navbar-logo">
                <img src="<?php echo BASE_URL; ?>assets/images/reikilogo.jpg" alt="Reiki Bliss Logo" class="brand-logo-img">
                <span class="logo-text">
                    <strong class="logo-title">Reiki Bliss</strong>
                    <span class="logo-subtitle">Healing Center</span>
                </span>
            </a>

            <!-- Desktop Navigation Links -->
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo ($currentPage == 'index.php' || $currentPage == '') ? 'active' : ''; ?>">Home</a>
                </li>
                <li class="nav-item has-dropdown">
                    <a href="<?php echo BASE_URL; ?>products.php" class="nav-link <?php echo ($currentPage == 'products.php' || $currentPage == 'shop.php' || $currentPage == 'product-detail.php') ? 'active' : ''; ?>">
                        Shop <span class="dropdown-arrow">▾</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo BASE_URL; ?>products.php?category=bracelets">Reiki Charged Bracelets</a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>products.php?category=protection">Protection Bracelets</a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>products.php?category=chakra">7 Chakra Collection</a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>custom-bracelet.php">Custom Birth-Chart Bracelet</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>services.php" class="nav-link <?php echo ($currentPage == 'services.php') ? 'active' : ''; ?>">Services</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>courses.php" class="nav-link <?php echo ($currentPage == 'courses.php') ? 'active' : ''; ?>">Courses</a>
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
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn-primary btn-book-nav">Book Session</a>
                
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
                <li class="mobile-has-submenu">
                    <span class="mobile-nav-link-group">
                        <a href="<?php echo BASE_URL; ?>products.php" class="mobile-nav-link">Shop</a>
                    </span>
                    <ul class="mobile-submenu">
                        <li><a href="<?php echo BASE_URL; ?>products.php?category=bracelets">Reiki Charged Bracelets</a></li>
                        <li><a href="<?php echo BASE_URL; ?>products.php?category=protection">Protection Bracelets</a></li>
                        <li><a href="<?php echo BASE_URL; ?>products.php?category=chakra">7 Chakra Collection</a></li>
                        <li><a href="<?php echo BASE_URL; ?>custom-bracelet.php">Custom Birth-Chart Bracelet</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo BASE_URL; ?>services.php" class="mobile-nav-link">Services</a></li>
                <li><a href="<?php echo BASE_URL; ?>courses.php" class="mobile-nav-link">Courses</a></li>
                <li><a href="<?php echo BASE_URL; ?>about.php" class="mobile-nav-link">About Us</a></li>
                <li><a href="<?php echo BASE_URL; ?>contact.php" class="mobile-nav-link">Contact Us</a></li>
            </ul>

            <div class="mobile-nav-cta">
                <a href="<?php echo BASE_URL; ?>services.php#book" class="btn-primary w-full">Book Session</a>
            </div>
        </div>
    </div>
</header>
