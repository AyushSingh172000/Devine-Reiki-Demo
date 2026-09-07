<?php
// Admin Custom Bracelet Orders Management
$pageTitle = "Custom Bracelet Orders";

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

$msg = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$orderId = (int)($_GET['id'] ?? 0);
$statusFilter = $_GET['status'] ?? 'all';

// Handle Status Update Request
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_status'])) {
    $targetId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status'] ?? 'pending');
    if (in_array($newStatus, ['pending', 'contacted', 'completed'])) {
        try {
            $stmt = $pdo->prepare("UPDATE bracelet_orders SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $targetId]);
            header("Location: " . BASE_URL . "admin/orders.php?msg=status_updated");
            exit;
        } catch (PDOException $e) {
            $error = "Error updating order status: " . $e->getMessage();
        }
    }
}

// Handle Delete Request
if ($action === 'delete' && $orderId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM bracelet_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        header("Location: " . BASE_URL . "admin/orders.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting order: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Order deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated') {
    $msg = "Order status updated successfully!";
}

// Build Filter SQL Query
$whereClause = "";
$params = [];

if (in_array($statusFilter, ['pending', 'contacted', 'completed'])) {
    $whereClause = "WHERE status = ?";
    $params[] = $statusFilter;
}

// Fetch single order for viewing
$viewOrder = null;
if ($orderId > 0 && ($action === 'view' || !empty($_GET['id']))) {
    $stmt = $pdo->prepare("SELECT * FROM bracelet_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $viewOrder = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Bracelet Orders
$orders = [];
try {
    $sql = "SELECT * FROM bracelet_orders {$whereClause} ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
}

include __DIR__ . '/includes/admin-header.php';
?>

<?php if (!empty($msg)): ?>
    <div style="background-color: #dcfce7; color: #16a34a; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background-color: #fee2e2; color: #dc2626; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($viewOrder): ?>
    <!-- VIEW ORDER DETAILS PANEL -->
    <div class="admin-card" style="margin-bottom: 32px;">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Order Details #<?php echo $viewOrder['id']; ?> (<?php echo strtoupper($viewOrder['order_type']); ?>)</h2>
            <a href="<?php echo BASE_URL; ?>admin/orders.php" class="btn-admin btn-admin-secondary">← Back to All Orders</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; background: var(--admin-bg); padding: 20px; border-radius: 14px;">
            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Customer Name</span>
                <strong style="display: block; font-size: 1.1rem; color: var(--admin-purple);"><?php echo htmlspecialchars($viewOrder['name']); ?></strong>
            </div>

            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Email Address</span>
                <span style="display: block; font-size: 0.95rem;"><?php echo htmlspecialchars($viewOrder['email']); ?></span>
            </div>

            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Phone / WhatsApp</span>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $viewOrder['phone']); ?>" target="_blank" rel="noopener" style="display: block; font-size: 0.95rem; color: #16a34a; font-weight: 600; text-decoration: none;">
                    💬 <?php echo htmlspecialchars($viewOrder['phone']); ?>
                </a>
            </div>

            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Current Status</span>
                <form action="orders.php" method="POST" style="margin-top: 4px; display: flex; gap: 8px;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                    <select name="status" class="form-select" onchange="this.form.submit();" style="padding: 4px 10px; font-size: 0.82rem;">
                        <option value="pending" <?php echo ($viewOrder['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="contacted" <?php echo ($viewOrder['status'] === 'contacted') ? 'selected' : ''; ?>>Contacted</option>
                        <option value="completed" <?php echo ($viewOrder['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </form>
            </div>
        </div>

        <?php if ($viewOrder['order_type'] === 'birth-chart'): ?>
            <div style="margin-bottom: 24px; background: var(--admin-bg); padding: 20px; border-radius: 14px;">
                <h4 style="font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--admin-purple); margin-bottom: 12px;">⭐ Astrology Birth Chart Details:</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; font-size: 0.95rem;">
                    <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($viewOrder['date_of_birth']); ?></div>
                    <div><strong>Time of Birth:</strong> <?php echo htmlspecialchars($viewOrder['time_of_birth'] ?: 'Not specified'); ?></div>
                    <div><strong>Place of Birth:</strong> <?php echo htmlspecialchars($viewOrder['place_of_birth']); ?></div>
                </div>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 24px; background: var(--admin-bg); padding: 20px; border-radius: 14px;">
                <h4 style="font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--admin-purple); margin-bottom: 8px;">💎 Intention Details:</h4>
                <div style="font-size: 1.05rem; font-weight: 600; color: var(--admin-gold-dark);">
                    <?php echo htmlspecialchars($viewOrder['intention']); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($viewOrder['message'])): ?>
            <div style="margin-bottom: 24px;">
                <label style="font-size: 0.84rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 700; display: block; margin-bottom: 8px;">Additional Notes / Wrist Size:</label>
                <div style="background: var(--admin-bg); padding: 18px; border-radius: 14px; font-size: 0.95rem; color: var(--admin-text);">
                    <?php echo htmlspecialchars($viewOrder['message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 12px;">
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $viewOrder['phone']); ?>?text=Hello%20<?php echo urlencode($viewOrder['name']); ?>%20regarding%20your%20custom%20bracelet%20order..." target="_blank" rel="noopener" class="btn-admin" style="background-color: #25D366; color: #fff;">
                💬 Contact Customer on WhatsApp
            </a>
            <a href="orders.php?action=delete&id=<?php echo $viewOrder['id']; ?>" class="btn-admin" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Delete this order?');">
                Delete Order
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ORDERS LIST TABLE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Custom Bracelet Orders (<?php echo count($orders); ?>)</h2>
        
        <!-- Filter Tabs -->
        <div style="display: flex; gap: 10px;">
            <a href="orders.php?status=all" class="btn-admin <?php echo ($statusFilter === 'all') ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-admin-sm">All</a>
            <a href="orders.php?status=pending" class="btn-admin <?php echo ($statusFilter === 'pending') ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-admin-sm">Pending</a>
            <a href="orders.php?status=contacted" class="btn-admin <?php echo ($statusFilter === 'contacted') ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-admin-sm">Contacted</a>
            <a href="orders.php?status=completed" class="btn-admin <?php echo ($statusFilter === 'completed') ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-admin-sm">Completed</a>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Details</th>
                    <th>Status Action</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td style="white-space: nowrap; color: var(--admin-muted); font-size: 0.82rem;">
                                <?php echo date('M j, Y', strtotime($ord['created_at'])); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($ord['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ord['phone']); ?></td>
                            <td>
                                <span style="font-size: 0.82rem; font-weight: 600;">
                                    <?php echo ($ord['order_type'] === 'birth-chart') ? '⭐ Birth Chart' : '💎 Intention'; ?>
                                </span>
                            </td>
                            <td style="max-width: 220px; font-size: 0.82rem; color: var(--admin-muted);">
                                <?php if ($ord['order_type'] === 'birth-chart'): ?>
                                    DOB: <?php echo htmlspecialchars($ord['date_of_birth'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($ord['place_of_birth'] ?? ''); ?>)
                                <?php else: ?>
                                    <?php echo htmlspecialchars($ord['intention'] ?? ''); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="orders.php?status=<?php echo urlencode($statusFilter); ?>" method="POST" style="margin: 0;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                    <select name="status" class="form-select" onchange="this.form.submit();" style="padding: 4px 8px; font-size: 0.8rem;">
                                        <option value="pending" <?php echo ($ord['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="contacted" <?php echo ($ord['status'] === 'contacted') ? 'selected' : ''; ?>>Contacted</option>
                                        <option value="completed" <?php echo ($ord['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="orders.php?id=<?php echo $ord['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">View</a>
                                <a href="orders.php?action=delete&id=<?php echo $ord['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Delete this order?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">No custom bracelet orders found matching filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
