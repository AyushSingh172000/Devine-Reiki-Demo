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

$adminUser = $_SESSION['admin_user'] ?? ($_SESSION['admin_username'] ?? 'Admin');
$adminInitial = strtoupper(substr($adminUser, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Admin | Reiki Bliss</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- Lucide Icons (CDN with local fallback) -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>if (!window.lucide) { document.write('<script src="assets/js/lucide.min.js"><\/script>'); }</script>
</head>
<body class="admin-body">
<!-- Particle Background Animation Canvas -->
<canvas id="adminParticles" class="admin-particles-canvas"></canvas>

<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
      <a href="index.php">
        <img src="../assets/images/logo.png" alt="Logo">
      </a>
      <span class="sidebar-label">Admin Panel</span>
    </div>
    <nav class="sidebar-nav">
      <li>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span> Dashboard
        </a>
      </li>

      <div class="sidebar-section-divider"><i data-lucide="layers"></i> CONTENT</div>
      <li>
        <a href="blog.php" class="<?= $currentPage === 'blog.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="file-text"></i></span> Blog Posts
        </a>
      </li>
      <li>
        <a href="gallery.php" class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="images"></i></span> Gallery
        </a>
      </li>
      <li>
        <a href="team.php" class="<?= $currentPage === 'team.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="users"></i></span> Team Members
        </a>
      </li>
      <li>
        <a href="testimonials.php" class="<?= $currentPage === 'testimonials.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="message-circle"></i></span> Testimonials
        </a>
      </li>

      <div class="sidebar-section-divider"><i data-lucide="store"></i> COMMERCE</div>
      <li>
        <a href="products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="shopping-bag"></i></span> Products
        </a>
      </li>
      <li>
        <a href="services.php" class="<?= $currentPage === 'services.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="clipboard-list"></i></span> Services
        </a>
      </li>
      <li>
        <a href="courses.php" class="<?= $currentPage === 'courses.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="graduation-cap"></i></span> Courses
        </a>
      </li>
      <li>
        <a href="orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="package"></i></span> Bracelet Orders
        </a>
      </li>

      <div class="sidebar-section-divider"><i data-lucide="radio"></i> COMMUNICATION</div>
      <li>
        <a href="inquiries.php" class="<?= $currentPage === 'inquiries.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="mail"></i></span> Inquiries
          <?php if ($unreadCount > 0): ?>
            <span class="sidebar-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
      </li>

      <div class="sidebar-section-divider"><i data-lucide="sliders-horizontal"></i> SETTINGS</div>
      <li>
        <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="settings"></i></span> Site Settings
        </a>
      </li>
      <li>
        <a href="logout.php" class="<?= $currentPage === 'logout.php' ? 'active' : '' ?>">
          <span class="nav-icon"><i data-lucide="log-out"></i></span> Logout
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
        <a href="inquiries.php" class="notification-bell" title="<?= $unreadCount ?> unread inquiries">
          <i data-lucide="bell"></i>
          <?php if ($unreadCount > 0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
        </a>
        <div class="admin-user">
          <span class="user-avatar"><?= $adminInitial ?></span>
          <span class="user-name"><?= htmlspecialchars($adminUser) ?></span>
        </div>
        <a href="../index.php" target="_blank" class="btn btn-outline btn-sm" title="View Public Website">
          <i data-lucide="external-link"></i> View Site
        </a>
        <a href="logout.php" class="btn btn-outline btn-sm">
          <i data-lucide="log-out"></i> Logout
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
