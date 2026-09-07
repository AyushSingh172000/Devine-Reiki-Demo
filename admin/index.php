<?php
// Admin Dashboard Controller - Divine Reiki Portal
$pageTitle = "Dashboard Overview";

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

// Fetch Counts for Stat Cards
$totalServices = 0;
$totalCourses = 0;
$totalProducts = 0;
$totalBlogPosts = 0;
$unreadInquiries = 0;
$pendingOrders = 0;

try {
    $totalServices = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $totalCourses = (int)$pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalBlogPosts = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
    $unreadInquiries = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE is_read = 0")->fetchColumn();
    $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM bracelet_orders WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    error_log("Database count error in admin/index.php: " . $e->getMessage());
}

// Fetch Recent 5 Inquiries
$recentInquiries = [];
try {
    $inqStmt = $pdo->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC LIMIT 5");
    $recentInquiries = $inqStmt ? $inqStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error fetching inquiries: " . $e->getMessage());
}

// Fetch Recent 5 Bracelet Orders
$recentOrders = [];
try {
    $ordStmt = $pdo->query("SELECT * FROM bracelet_orders ORDER BY created_at DESC LIMIT 5");
    $recentOrders = $ordStmt ? $ordStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error fetching orders: " . $e->getMessage());
}

// Include Admin Header
include __DIR__ . '/includes/admin-header.php';
?>

<!-- 1. SUMMARY STAT CARDS GRID -->
<div class="stat-cards-grid">
    <!-- Stat 1: Total Services -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Services</span>
            <span class="stat-icon">🧘</span>
        </div>
        <div class="stat-number"><?php echo number_format($totalServices); ?></div>
    </div>

    <!-- Stat 2: Total Courses -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Courses</span>
            <span class="stat-icon">🎓</span>
        </div>
        <div class="stat-number"><?php echo number_format($totalCourses); ?></div>
    </div>

    <!-- Stat 3: Total Products -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Products</span>
            <span class="stat-icon">💎</span>
        </div>
        <div class="stat-number"><?php echo number_format($totalProducts); ?></div>
    </div>

    <!-- Stat 4: Total Blog Posts -->
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Blog Posts</span>
            <span class="stat-icon">📝</span>
        </div>
        <div class="stat-number"><?php echo number_format($totalBlogPosts); ?></div>
    </div>

    <!-- Stat 5: Unread Inquiries -->
    <div class="stat-card <?php echo ($unreadInquiries > 0) ? 'alert' : ''; ?>">
        <div class="stat-header">
            <span class="stat-title">Unread Inquiries</span>
            <span class="stat-icon">✉️</span>
        </div>
        <div class="stat-number"><?php echo number_format($unreadInquiries); ?></div>
    </div>

    <!-- Stat 6: Pending Orders -->
    <div class="stat-card <?php echo ($pendingOrders > 0) ? 'highlight' : ''; ?>">
        <div class="stat-header">
            <span class="stat-title">Pending Orders</span>
            <span class="stat-icon">🛍️</span>
        </div>
        <div class="stat-number"><?php echo number_format($pendingOrders); ?></div>
    </div>
</div>

<!-- 2. DASHBOARD TABLES GRID (RECENT INQUIRIES & RECENT ORDERS) -->
<div class="dashboard-grid-2col">
    
    <!-- Recent Contact Inquiries -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Recent Inquiries</h2>
            <a href="<?php echo BASE_URL; ?>admin/inquiries.php" class="btn-admin btn-admin-secondary btn-admin-sm">
                View All (<?php echo $unreadInquiries; ?> Unread) →
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentInquiries)): ?>
                        <?php foreach ($recentInquiries as $inq): ?>
                            <tr>
                                <td style="white-space: nowrap; color: var(--admin-muted); font-size: 0.82rem;">
                                    <?php echo date('M j, Y', strtotime($inq['created_at'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($inq['name']); ?></strong>
                                    <span style="display: block; font-size: 0.78rem; color: var(--admin-muted);"><?php echo htmlspecialchars($inq['phone'] ?: $inq['email']); ?></span>
                                </td>
                                <td>
                                    <?php if ($inq['is_read']): ?>
                                        <span class="badge-status badge-read">Read</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-unread">Unread</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/inquiries.php?id=<?php echo $inq['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--admin-muted); padding: 20px;">No recent inquiries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Bracelet Orders -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Recent Custom Orders</h2>
            <a href="<?php echo BASE_URL; ?>admin/orders.php" class="btn-admin btn-admin-secondary btn-admin-sm">
                View All (<?php echo $pendingOrders; ?> Pending) →
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $ord): ?>
                            <tr>
                                <td style="white-space: nowrap; color: var(--admin-muted); font-size: 0.82rem;">
                                    <?php echo date('M j, Y', strtotime($ord['created_at'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ord['name']); ?></strong>
                                    <span style="display: block; font-size: 0.78rem; color: var(--admin-muted);"><?php echo htmlspecialchars($ord['phone']); ?></span>
                                </td>
                                <td>
                                    <span style="font-size: 0.82rem; font-weight: 500;">
                                        <?php echo ($ord['order_type'] === 'birth-chart') ? '⭐ Birth Chart' : '💎 Intention'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ord['status'] === 'pending'): ?>
                                        <span class="badge-status badge-pending">Pending</span>
                                    <?php elseif ($ord['status'] === 'contacted'): ?>
                                        <span class="badge-status badge-contacted">Contacted</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-completed">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--admin-muted); padding: 20px;">No recent custom orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 3. QUICK ACTIONS GRID -->
<div class="admin-card" style="margin-bottom: 36px;">
    <h2 class="admin-card-title" style="margin-bottom: 20px;">Quick Content Management Shortcuts</h2>
    <div style="display: flex; gap: 14px; flex-wrap: wrap;">
        <a href="<?php echo BASE_URL; ?>admin/services.php?action=add" class="btn-admin btn-admin-primary">
            ➕ Add New Service
        </a>
        <a href="<?php echo BASE_URL; ?>admin/courses.php?action=add" class="btn-admin btn-admin-primary">
            ➕ Add New Course
        </a>
        <a href="<?php echo BASE_URL; ?>admin/products.php?action=add" class="btn-admin btn-admin-primary">
            ➕ Add New Product
        </a>
        <a href="<?php echo BASE_URL; ?>admin/blog.php?action=add" class="btn-admin btn-admin-primary">
            ✍️ Write Blog Article
        </a>
        <a href="<?php echo BASE_URL; ?>admin/gallery.php?action=add" class="btn-admin btn-admin-secondary">
            📷 Upload Gallery Image
        </a>
        <a href="<?php echo BASE_URL; ?>admin/settings.php" class="btn-admin btn-admin-secondary">
            ⚙️ Edit Site Settings
        </a>
    </div>
</div>

<!-- Include Admin Footer -->
<?php include __DIR__ . '/includes/admin-footer.php'; ?>
