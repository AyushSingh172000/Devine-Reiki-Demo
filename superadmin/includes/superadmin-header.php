<?php
// Ensure auth-check is performed before including header
if (!defined('SUPERADMIN_ACCESS')) {
    require_once __DIR__ . '/../auth-check.php';
}

$currentPage = basename($_SERVER['PHP_SELF']);
$adminFullName = htmlspecialchars($superAdminUser['full_name'] ?? 'Super Admin');
$adminUsername = htmlspecialchars($superAdminUser['username'] ?? 'superadmin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Super Admin Studio') ?> | Reiki Bliss Master Control</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Super Admin Stylesheet -->
    <link rel="stylesheet" href="assets/css/superadmin.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

    <!-- =========================================================================
         SIDEBAR NAVIGATION
         ========================================================================= -->
    <aside class="sa-sidebar">
        <!-- Brand Header -->
        <div class="sa-brand">
            <div class="sa-brand-logo">RB</div>
            <div class="sa-brand-text">
                <h2>Reiki Bliss</h2>
                <span>Super Admin</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="sa-nav">
            <div class="sa-nav-section-title">
                <i data-lucide="layout-grid" style="width: 14px; height: 14px;"></i>
                Core Architecture
            </div>

            <a href="index.php" class="sa-nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i data-lucide="gauge"></i>
                <span>Master Dashboard</span>
            </a>

            <a href="homepage-manager.php" class="sa-nav-item <?= $currentPage === 'homepage-manager.php' ? 'active' : '' ?>">
                <i data-lucide="layers"></i>
                <span>Homepage Sections</span>
            </a>

            <a href="services.php" class="sa-nav-item <?= $currentPage === 'services.php' ? 'active' : '' ?>">
                <i data-lucide="sparkles"></i>
                <span>Services Manager</span>
            </a>

            <div class="sa-nav-section-title">
                <i data-lucide="shield-alert" style="width: 14px; height: 14px;"></i>
                Governance &amp; Access
            </div>

            <a href="client-permissions.php" class="sa-nav-item <?= $currentPage === 'client-permissions.php' ? 'active' : '' ?>">
                <i data-lucide="sliders"></i>
                <span>Client Admin Rights</span>
            </a>

            <a href="sidebar-manager.php" class="sa-nav-item <?= $currentPage === 'sidebar-manager.php' ? 'active' : '' ?>">
                <i data-lucide="edit-3"></i>
                <span>Admin Sidebar Names</span>
            </a>

            <a href="global-controls.php" class="sa-nav-item <?= $currentPage === 'global-controls.php' ? 'active' : '' ?>">
                <i data-lucide="power"></i>
                <span>Global Site Controls</span>
            </a>

            <a href="admin-users.php" class="sa-nav-item <?= $currentPage === 'admin-users.php' ? 'active' : '' ?>">
                <i data-lucide="users"></i>
                <span>Client Admin Accounts</span>
            </a>

            <div class="sa-nav-section-title">
                <i data-lucide="settings" style="width: 14px; height: 14px;"></i>
                Master Security
            </div>

            <a href="profile.php" class="sa-nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                <i data-lucide="user-cog"></i>
                <span>Super Admin Profile</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sa-sidebar-footer">
            <div class="sa-user-pill">
                <div class="sa-avatar-circle">SA</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $adminFullName ?></div>
                    <div style="font-size: 0.72rem; color: var(--sa-gold); font-weight: 500;">@<?= $adminUsername ?></div>
                </div>
                <a href="logout.php" title="Logout" style="color: var(--sa-text-dim); padding: 4px; display: flex; align-items: center;">
                    <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                </a>
            </div>
            <a href="../admin/" target="_blank" class="sa-quick-btn" style="justify-content: center; font-size: 0.75rem;">
                <i data-lucide="arrow-up-right" style="width: 13px; height: 13px;"></i> Open Client Admin
            </a>
        </div>
    </aside>

    <!-- =========================================================================
         MAIN CONTENT SHELL
         ========================================================================= -->
    <main class="sa-main-content">
        <!-- Top App Bar -->
        <header class="sa-topbar">
            <div class="sa-topbar-left">
                <h1 class="sa-page-heading">
                    <?= htmlspecialchars($pageTitle ?? 'Master Studio') ?>
                </h1>
            </div>

            <div class="sa-topbar-right">
                <a href="../index.php" target="_blank" class="sa-quick-btn gold">
                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i> View Live Site
                </a>
                <a href="logout.php" class="sa-quick-btn">
                    <i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Logout
                </a>
            </div>
        </header>

        <div class="sa-container">
