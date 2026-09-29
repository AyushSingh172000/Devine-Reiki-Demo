<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
// Admin Session Authentication Check & Database Initialization
require_once __DIR__ . '/../auth-check.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../../config/db.php';
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$pageTitle = $pageTitle ?? 'Dashboard';

// Fetch unread inquiries count
$unreadCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE is_read = 0");
    $unreadCount = $stmt ? (int)$stmt->fetchColumn() : 0;
} catch (PDOException $e) {
    error_log("Error fetching unread inquiries count: " . $e->getMessage());
    $unreadCount = 0;
}

// Fetch site branding settings dynamically
$siteSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $siteSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching site_settings: " . $e->getMessage());
}

$rawFavicon = !empty($siteSettings['favicon_path']) ? ltrim($siteSettings['favicon_path'], '/') : 'assets/images/favicon-circle.png';
$adminFavicon = '../' . $rawFavicon;
$faviconVersion = file_exists(__DIR__ . '/../../' . $rawFavicon) ? filemtime(__DIR__ . '/../../' . $rawFavicon) : time();

$rawLogo = !empty($siteSettings['logo_path']) ? ltrim($siteSettings['logo_path'], '/') : 'assets/images/reikilogo1.png';
$adminLogo = '../' . $rawLogo;
$logoVersion = file_exists(__DIR__ . '/../../' . $rawLogo) ? filemtime(__DIR__ . '/../../' . $rawLogo) : time();

$adminUser = $_SESSION['admin_user'] ?? ($_SESSION['admin_username'] ?? 'Admin');
$adminInitial = strtoupper(substr($adminUser, 0, 1));

// Super Admin Permission Matrix
$saPerms = !empty($siteSettings['superadmin_admin_permissions']) ? json_decode($siteSettings['superadmin_admin_permissions'], true) : [];
$saAllowedMenus = $saPerms['allowed_menus'] ?? null;
$isMenuAllowed = function($menuKey) use ($saAllowedMenus) {
    if ($saAllowedMenus === null) return true;
    return in_array($menuKey, $saAllowedMenus);
};

// Super Admin Dynamic Sidebar Names & Screenshot Defaults
$customSidebarLabels = !empty($siteSettings['superadmin_sidebar_names']) ? json_decode($siteSettings['superadmin_sidebar_names'], true) : [];
$defaultSidebarLabels = [
    'brand_title'           => 'ADMIN PANEL',
    'menu_dashboard'        => 'Dashboard',
    'section_content'       => 'CONTENT',
    'menu_homepage_hero'    => 'Homepage Hero',
    'menu_about_us'         => 'About Us Page',
    'menu_testimonials'     => 'Testimonials',
    'section_commerce'      => 'COMMERCE',
    'menu_products'         => 'Products',
    'menu_services'         => 'Services',
    'menu_courses'          => 'Courses',
    'menu_orders'           => 'Orders',
    'section_communication' => 'COMMUNICATION',
    'menu_inquiries'        => 'Inquiries',
    'menu_whatsapp'         => 'WhatsApp Templates',
    'section_settings'      => 'SETTINGS',
    'menu_settings'         => 'Site Settings',
    'menu_footer'           => 'Footer & Legal',
    'menu_logout'           => 'Logout',
];
$sbLabel = function($key) use ($customSidebarLabels, $defaultSidebarLabels) {
    if (isset($customSidebarLabels[$key]) && trim((string)$customSidebarLabels[$key]) !== '') {
        return htmlspecialchars(trim((string)$customSidebarLabels[$key]));
    }
    return htmlspecialchars($defaultSidebarLabels[$key] ?? $key);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Admin | Reiki Bliss</title>
    <meta name="robots" content="noindex, nofollow">
    <!-- Favicon (Matched with Public Website) -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($adminFavicon) ?>?v=<?= $faviconVersion ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($adminFavicon) ?>?v=<?= $faviconVersion ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($adminFavicon) ?>?v=<?= $faviconVersion ?>">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <base href="<?= ADMIN_URL ?>">
    <!-- Admin CSS with Cache-Busting -->
    <?php
    $adminCssFile = __DIR__ . '/../assets/css/admin.css';
    $adminCssVer = file_exists($adminCssFile) ? filemtime($adminCssFile) : time();
    ?>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= $adminCssVer ?>">
    <!-- Lucide Icons -->
    <script src="assets/js/lucide.min.js"></script>
</head>
<body class="admin-body">
<!-- Particle Background Animation Canvas -->
<canvas id="adminParticles" class="admin-particles-canvas"></canvas>

<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
      <a href="./">
        <img src="<?= htmlspecialchars($adminLogo) ?>?v=<?= $logoVersion ?>" alt="Reiki Bliss">
      </a>
      <span class="sidebar-label"><?= $sbLabel('brand_title') ?></span>
    </div>
    <nav class="sidebar-nav">
      <li>
        <a href="./" class="<?= ($currentPage === 'index.php' || $currentPage === '') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span> <?= $sbLabel('menu_dashboard') ?>
        </a>
      </li>

      <div class="sidebar-section-divider"><i data-lucide="layers"></i> <?= $sbLabel('section_content') ?></div>
      <?php /*
      <li>
        <a href="gallery" class="<?= ($currentPage === 'gallery.php' || $currentPage === 'gallery') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="images"></i></span> Gallery
        </a>
      </li>
      */ ?>
      <?php /*
      <li>
        <a href="team" class="<?= ($currentPage === 'team.php' || $currentPage === 'team') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="users"></i></span> Team Members
        </a>
      </li>
      */ ?>
      <li>
        <a href="settings?tab=homepage" class="<?= ($currentPage === 'settings.php' && ($_GET['tab'] ?? '') === 'homepage') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="sparkles"></i></span> <?= $sbLabel('menu_homepage_hero') ?>
        </a>
      </li>
      <li>
        <a href="settings?tab=about" class="<?= ($currentPage === 'settings.php' && ($_GET['tab'] ?? '') === 'about') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="book-open"></i></span> <?= $sbLabel('menu_about_us') ?>
        </a>
      </li>
      <?php if ($isMenuAllowed('testimonials')): ?>
      <li>
        <a href="testimonials" class="<?= ($currentPage === 'testimonials.php' || $currentPage === 'testimonials') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="message-circle"></i></span> <?= $sbLabel('menu_testimonials') ?>
        </a>
      </li>
      <?php endif; ?>

      <div class="sidebar-section-divider"><i data-lucide="store"></i> <?= $sbLabel('section_commerce') ?></div>
      <?php if ($isMenuAllowed('products')): ?>
      <li>
        <a href="products" class="<?= ($currentPage === 'products.php' || $currentPage === 'products') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="shopping-bag"></i></span> <?= $sbLabel('menu_products') ?>
        </a>
      </li>
      <li>
        <a href="product-categories" class="<?= ($currentPage === 'product-categories.php' || $currentPage === 'product-categories') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="tags"></i></span> Categories
        </a>
      </li>
      <?php endif; ?>

      <?php if ($isMenuAllowed('services')): ?>
      <li>
        <a href="services" class="<?= ($currentPage === 'services.php' || $currentPage === 'services') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="clipboard-list"></i></span> <?= $sbLabel('menu_services') ?>
        </a>
      </li>
      <?php endif; ?>

      <?php if ($isMenuAllowed('courses')): ?>
      <li>
        <a href="courses" class="<?= ($currentPage === 'courses.php' || $currentPage === 'courses') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="graduation-cap"></i></span> <?= $sbLabel('menu_courses') ?>
        </a>
      </li>
      <?php endif; ?>

      <li>
        <a href="orders" class="<?= ($currentPage === 'orders.php' || $currentPage === 'orders') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="package"></i></span> <?= $sbLabel('menu_orders') ?>
        </a>
      </li>

      <div class="sidebar-section-divider"><i data-lucide="radio"></i> <?= $sbLabel('section_communication') ?></div>
      <?php if ($isMenuAllowed('inquiries')): ?>
      <li>
        <a href="inquiries" class="<?= ($currentPage === 'inquiries.php' || $currentPage === 'inquiries') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="mail"></i></span> <?= $sbLabel('menu_inquiries') ?>
          <?php if ($unreadCount > 0): ?>
            <span class="sidebar-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endif; ?>

      <?php if ($isMenuAllowed('whatsapp')): ?>
      <li>
        <a href="settings?tab=whatsapp" class="<?= ($currentPage === 'settings.php' && ($_GET['tab'] ?? '') === 'whatsapp') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="message-square"></i></span> <?= $sbLabel('menu_whatsapp') ?>
        </a>
      </li>
      <?php endif; ?>

      <div class="sidebar-section-divider"><i data-lucide="sliders-horizontal"></i> <?= $sbLabel('section_settings') ?></div>
      <?php if ($isMenuAllowed('settings')): ?>
      <li>
        <a href="settings" class="<?= ($currentPage === 'settings.php' && empty($_GET['tab'])) ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="settings"></i></span> <?= $sbLabel('menu_settings') ?>
        </a>
      </li>
      <?php endif; ?>

      <?php if ($isMenuAllowed('footer')): ?>
      <li>
        <a href="settings?tab=footer" class="<?= ($currentPage === 'settings.php' && ($_GET['tab'] ?? '') === 'footer') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="panel-bottom"></i></span> <?= $sbLabel('menu_footer') ?>
        </a>
      </li>
      <?php endif; ?>
      <?php /* Hidden: Logo & Favicon can be managed directly inside Site Settings -> Branding & Assets tab
      <li>
        <a href="branding" class="<?= ($currentPage === 'branding.php' || ($currentPage === 'settings.php' && isset($_GET['tab']) && $_GET['tab'] === 'branding')) ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="image"></i></span> Logo &amp; Favicon
        </a>
      </li>
      */ ?>
      <li>
        <a href="logout" class="<?= ($currentPage === 'logout.php' || $currentPage === 'logout') ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="log-out"></i></span> <?= $sbLabel('menu_logout') ?>
        </a>
      </li>
    </nav>
  </aside>

  <!-- MAIN AREA -->
  <div class="admin-main">
    <header class="admin-header">
      <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle navigation">
        <i data-lucide="menu"></i>
      </button>
      <h1 class="header-title"><?= htmlspecialchars($pageTitle) ?></h1>
      <div class="header-right">
        <a href="inquiries" class="notification-bell" title="<?= $unreadCount ?> unread inquiries">
          <i data-lucide="bell"></i>
          <?php if ($unreadCount > 0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
        </a>
        <div class="admin-user">
          <span class="user-avatar"><?= $adminInitial ?></span>
          <span class="user-name"><?= htmlspecialchars($adminUser) ?></span>
        </div>
        <a href="../" target="_blank" class="btn btn-outline btn-sm" title="View Public Website">
          <i data-lucide="external-link"></i> <span class="btn-text">View Site</span>
        </a>
        <a href="logout" class="btn btn-outline btn-sm" title="Logout">
          <i data-lucide="log-out"></i> <span class="btn-text">Logout</span>
        </a>
      </div>
    </header>
    <main class="admin-content">
      <!-- Session Flash Messages -->
      <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
          <i data-lucide="check-circle-2"></i> <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-error">
          <i data-lucide="alert-triangle"></i> <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['flash_warning'])): ?>
        <div class="alert" style="background-color: #fefce8; border: 1px solid #fef08a; color: #854d0e; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
          <i data-lucide="alert-circle" style="color: #eab308; width: 18px; height: 18px; flex-shrink: 0;"></i> <?= htmlspecialchars($_SESSION['flash_warning']); unset($_SESSION['flash_warning']); ?>
        </div>
      <?php endif; ?>
