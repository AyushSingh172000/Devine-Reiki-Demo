<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
$pageTitle = 'Dashboard';

// Database queries for statistics
$totalServices = 0;
$totalProducts = 0;
$totalCourses = 0;
$unreadInquiries = 0;

$totalBlogPosts = 0;
$pendingOrders = 0;
$totalGallery = 0;

$recentInquiries = [];
$recentOrders = [];

try {
    // 1. Stats Row 1
    $totalServices = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalCourses = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $unreadInquiries = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE is_read = 0")->fetchColumn();

    // 2. Stats Row 2
    $totalBlogPosts = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
    $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM bracelet_orders WHERE status = 'pending'")->fetchColumn();
    $totalGallery = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();

    // 3. Recent 5 Inquiries
    $inqStmt = $pdo->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC LIMIT 5");
    $recentInquiries = $inqStmt ? $inqStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // 4. Recent 5 Bracelet Orders
    $ordStmt = $pdo->query("SELECT * FROM bracelet_orders ORDER BY created_at DESC LIMIT 5");
    $recentOrders = $ordStmt ? $ordStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
}

require_once 'includes/admin-header.php';
?>

<!-- 1. STATS ROW (Grid of 4 Stat Cards) -->
<div class="grid-4 mb-3">
    <!-- Total Services -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($totalServices) ?></span>
            <span class="stat-label">Total Services</span>
        </div>
        <div class="stat-icon purple" title="Total Active Services">
            <i data-lucide="clipboard-list"></i>
        </div>
    </div>

    <!-- Total Products -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($totalProducts) ?></span>
            <span class="stat-label">Total Products</span>
        </div>
        <div class="stat-icon gold" title="Total Shop Products">
            <i data-lucide="shopping-bag"></i>
        </div>
    </div>

    <!-- Total Courses -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($totalCourses) ?></span>
            <span class="stat-label">Total Courses</span>
        </div>
        <div class="stat-icon blue" title="Total Healing Courses">
            <i data-lucide="graduation-cap"></i>
        </div>
    </div>

    <!-- Unread Inquiries -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($unreadInquiries) ?></span>
            <span class="stat-label">Unread Inquiries</span>
        </div>
        <div class="stat-icon red <?= ($unreadInquiries > 0) ? 'pulse' : '' ?>" title="<?= $unreadInquiries ?> Pending Inquiries">
            <i data-lucide="mail-warning"></i>
        </div>
    </div>
</div>

<!-- 2. SECOND ROW (Grid of 3 Stat Cards) -->
<div class="grid-3 mb-4">
    <!-- Total Blog Posts -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($totalBlogPosts) ?></span>
            <span class="stat-label">Blog Posts</span>
        </div>
        <div class="stat-icon purple">
            <i data-lucide="newspaper"></i>
        </div>
    </div>

    <!-- Pending Bracelet Orders -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($pendingOrders) ?></span>
            <span class="stat-label">Pending Orders</span>
        </div>
        <div class="stat-icon gold">
            <i data-lucide="package-check"></i>
        </div>
    </div>

    <!-- Total Gallery Images -->
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-number"><?= number_format($totalGallery) ?></span>
            <span class="stat-label">Gallery Images</span>
        </div>
        <div class="stat-icon gold">
            <i data-lucide="image"></i>
        </div>
    </div>
</div>

<!-- 3. QUICK ACTIONS ROW -->
<div class="mb-4">
    <div class="quick-actions-grid">
        <a href="products.php?action=add" class="quick-action-card">
            <div class="action-icon"><i data-lucide="shopping-bag"></i></div>
            <div>
                <div>Add Product</div>
                <small class="text-muted" style="font-weight: normal; font-size: 0.78rem;">New crystal / item</small>
            </div>
        </a>

        <a href="services.php?action=add" class="quick-action-card">
            <div class="action-icon"><i data-lucide="clipboard-list"></i></div>
            <div>
                <div>Add Service</div>
                <small class="text-muted" style="font-weight: normal; font-size: 0.78rem;">New healing therapy</small>
            </div>
        </a>

        <a href="courses.php?action=add" class="quick-action-card">
            <div class="action-icon"><i data-lucide="graduation-cap"></i></div>
            <div>
                <div>Add Course</div>
                <small class="text-muted" style="font-weight: normal; font-size: 0.78rem;">New training program</small>
            </div>
        </a>

        <a href="blog.php?action=add" class="quick-action-card">
            <div class="action-icon"><i data-lucide="file-text"></i></div>
            <div>
                <div>Add Blog Post</div>
                <small class="text-muted" style="font-weight: normal; font-size: 0.78rem;">Publish healing article</small>
            </div>
        </a>
    </div>
</div>

<!-- 4. TWO-COLUMN LAYOUT FOR RECENT TABLES -->
<div class="grid-2 gap-3 mb-4">
    <!-- RECENT INQUIRIES TABLE -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title flex items-center gap-2">
                <i data-lucide="mail"></i> Recent Inquiries
            </div>
            <a href="inquiries.php" class="btn-outline btn-sm text-gold" style="text-decoration: none;">
                View All <i data-lucide="chevron-right" style="width: 14px; height: 14px; margin-right: 0;"></i>
            </a>
        </div>

        <?php if (empty($recentInquiries)): ?>
            <p class="text-muted" style="text-align: center; padding: 24px 0;">No inquiries received yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentInquiries as $inq): ?>
                            <?php 
                                $isUnread = empty($inq['is_read']);
                                $truncatedMsg = mb_strlen($inq['message'] ?? '') > 50 
                                    ? mb_substr($inq['message'], 0, 47) . '...' 
                                    : ($inq['message'] ?? '');
                                $dateFormatted = !empty($inq['created_at']) 
                                    ? date('M d, Y', strtotime($inq['created_at'])) 
                                    : '—';
                            ?>
                            <tr class="<?= $isUnread ? 'row-unread' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($inq['name'] ?? 'Guest') ?></strong>
                                </td>
                                <td class="text-muted" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($inq['email'] ?? '—') ?>
                                </td>
                                <td style="font-size: 0.84rem;">
                                    <?= htmlspecialchars($truncatedMsg) ?>
                                </td>
                                <td class="text-muted" style="font-size: 0.8rem; white-space: nowrap;">
                                    <?= $dateFormatted ?>
                                </td>
                                <td>
                                    <?php if ($isUnread): ?>
                                        <span class="badge badge-warning">Unread</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Read</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- RECENT BRACELET ORDERS TABLE -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title flex items-center gap-2">
                <i data-lucide="package"></i> Recent Bracelet Orders
            </div>
            <a href="orders.php" class="btn-outline btn-sm text-gold" style="text-decoration: none;">
                View All <i data-lucide="chevron-right" style="width: 14px; height: 14px; margin-right: 0;"></i>
            </a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <p class="text-muted" style="text-align: center; padding: 24px 0;">No bracelet orders recorded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php 
                                $orderType = $order['order_type'] ?? 'customized';
                                $orderStatus = $order['status'] ?? 'pending';
                                $orderDate = !empty($order['created_at']) 
                                    ? date('M d, Y', strtotime($order['created_at'])) 
                                    : '—';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($order['name'] ?? 'Anonymous') ?></strong>
                                </td>
                                <td>
                                    <?php if ($orderType === 'birth-chart'): ?>
                                        <span class="badge badge-purple" style="background: rgba(124,107,196,0.18); color: #c4b8ff; border: 1px solid rgba(124,107,196,0.3);">
                                            Birth Chart
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-gold">
                                            Customized
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($order['phone'] ?? '—') ?>
                                </td>
                                <td>
                                    <?php if ($orderStatus === 'completed'): ?>
                                        <span class="badge badge-success">Completed</span>
                                    <?php elseif ($orderStatus === 'contacted'): ?>
                                        <span class="badge badge-info">Contacted</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted" style="font-size: 0.8rem; white-space: nowrap;">
                                    <?= $orderDate ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
