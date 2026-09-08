<?php
// Admin Header & Navigation Component
require_once __DIR__ . '/../../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../../config/db.php';
}

// Session Authentication Check
require_once __DIR__ . '/../auth-check.php';

// Fetch Unread Inquiries & Pending Orders Counts for Sidebar Badges
$unreadInquiriesCount = 0;
$pendingOrdersCount = 0;

try {
    $inqStmt = $pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE is_read = 0");
    $unreadInquiriesCount = (int)($inqStmt ? $inqStmt->fetchColumn() : 0);

    $ordStmt = $pdo->query("SELECT COUNT(*) FROM bracelet_orders WHERE status = 'pending'");
    $pendingOrdersCount = (int)($ordStmt ? $ordStmt->fetchColumn() : 0);
} catch (PDOException $e) {
    error_log("Error fetching admin sidebar counts: " . $e->getMessage());
}

$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Dashboard'; ?> | Divine Reiki</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/assets/css/admin.css">
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="admin-sidebar">
    <div>
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="sidebar-brand">
            <img src="<?php echo BASE_URL; ?>assets/images/reikilogo1.png" alt="Reiki Bliss Admin Logo" class="admin-brand-logo-img">
            <div class="sidebar-brand-title">
                Reiki Bliss
                <span>Admin Control</span>
            </div>
        </a>

        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'index.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>📊</span>
                        <span class="menu-title">Dashboard</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/services.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'services.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>🧘</span>
                        <span class="menu-title">Services</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/courses.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'courses.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>🎓</span>
                        <span class="menu-title">Courses</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/products.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'products.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>💎</span>
                        <span class="menu-title">Products</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/gallery.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'gallery.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>🖼️</span>
                        <span class="menu-title">Gallery</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/blog.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'blog.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>📝</span>
                        <span class="menu-title">Blog</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/testimonials.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'testimonials.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>💬</span>
                        <span class="menu-title">Testimonials</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/team.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'team.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>👥</span>
                        <span class="menu-title">Team</span>
                    </div>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/inquiries.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'inquiries.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>✉️</span>
                        <span class="menu-title">Inquiries</span>
                    </div>
                    <?php if ($unreadInquiriesCount > 0): ?>
                        <span class="menu-badge"><?php echo $unreadInquiriesCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/orders.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'orders.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>🛍️</span>
                        <span class="menu-title">Orders</span>
                    </div>
                    <?php if ($pendingOrdersCount > 0): ?>
                        <span class="menu-badge"><?php echo $pendingOrdersCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>admin/settings.php" class="sidebar-menu-link <?php echo ($currentAdminPage == 'settings.php') ? 'active' : ''; ?>">
                    <div class="menu-item-left">
                        <span>⚙️</span>
                        <span class="menu-title">Site Settings</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>

    <div>
        <a href="<?php echo BASE_URL; ?>admin/logout.php" class="sidebar-menu-link" style="color: #f87171;">
            <div class="menu-item-left">
                <span>🚪</span>
                <span class="menu-title">Logout</span>
            </div>
        </a>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="admin-main-wrapper">
    <!-- Top Navigation Bar -->
    <header class="admin-topbar">
        <div style="font-weight: 600; font-size: 1.1rem; color: var(--admin-purple);">
            <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard Overview'; ?>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="<?php echo BASE_URL; ?>index.php" target="_blank" rel="noopener" class="btn-admin btn-admin-secondary btn-admin-sm">
                🌐 View Main Website
            </a>

            <div class="topbar-user">
                <div class="topbar-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <strong style="display: block; line-height: 1.2;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>
                    <span style="font-size: 0.76rem; color: var(--admin-muted);">Administrator</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="admin-content-container">
