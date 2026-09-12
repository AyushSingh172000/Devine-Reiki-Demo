<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? 'All');
$statusFilter = trim($_GET['status'] ?? 'All');
$viewId = (int)($_GET['view'] ?? 0);
$error = '';

// =========================================================================
// 1. HANDLE POST ACTIONS (Status Update, Delete)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // A. UPDATE ORDER STATUS
    if ($postAction === 'update_status') {
        $orderId = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'pending');
        if ($orderId > 0 && in_array($newStatus, ['pending', 'contacted', 'completed'])) {
            try {
                $stmt = $pdo->prepare("UPDATE bracelet_orders SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                $_SESSION['flash_success'] = "Order status updated to " . ucfirst($newStatus) . "!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error updating order status: " . $e->getMessage();
            }
        }
        header("Location: orders.php" . ($viewId > 0 ? "?view=$viewId" : ""));
        exit;
    }

    // B. DELETE ORDER
    if ($postAction === 'delete') {
        $orderId = (int)($_POST['id'] ?? 0);
        if ($orderId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM bracelet_orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $_SESSION['flash_success'] = "Bracelet order deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error deleting order: " . $e->getMessage();
            }
        }
        header("Location: orders.php");
        exit;
    }
}

// =========================================================================
// 2. FETCH ORDERS & PENDING COUNT
// =========================================================================

$pendingTotal = 0;
try {
    $pendingTotal = (int)$pdo->query("SELECT COUNT(*) FROM bracelet_orders WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {}

$whereParts = [];
$params = [];

if (!empty($search)) {
    $whereParts[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR whatsapp LIKE ? OR intention LIKE ? OR place_of_birth LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($typeFilter === 'birth-chart') {
    $whereParts[] = "order_type = 'birth-chart'";
} elseif ($typeFilter === 'customized') {
    $whereParts[] = "order_type = 'customized'";
}

if (in_array($statusFilter, ['pending', 'contacted', 'completed'])) {
    $whereParts[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';
$sql = "SELECT * FROM bracelet_orders {$whereClause} ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ordersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ordersList = [];
    $error = "Error loading orders: " . $e->getMessage();
}

$ordersMap = [];
foreach ($ordersList as $ord) {
    $ordersMap[$ord['id']] = $ord;
}

$pageTitle = 'Bracelet Orders';
require_once 'includes/admin-header.php';
?>

<!-- Scoped Orders Styling -->
<style>
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
}
.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
}
.orders-table thead th {
    font-size: 0.74rem;
    padding: 12px 14px;
    background: #f8fafc;
    border-bottom: 1px solid var(--card-border);
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.orders-table tbody td {
    padding: 12px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.orders-table tbody tr {
    transition: background-color 0.15s ease;
}
.orders-table tbody tr:hover {
    background: #fafbfc;
}
.orders-table tbody tr:last-child td {
    border-bottom: none;
}
.order-cust-title {
    color: var(--text-primary);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: color 0.15s ease;
}
.order-cust-title:hover {
    color: var(--gold);
}

/* Luxury View Detail Modal */
.modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    z-index: 99999 !important;
    display: none;
    align-items: center !important;
    justify-content: center !important;
    padding: 24px 16px !important;
    box-sizing: border-box !important;
    overflow-y: auto !important;
    overscroll-behavior: contain !important;
}
.modal-overlay.active {
    display: flex !important;
}
.order-modal-dialog {
    max-width: 620px;
    width: 100%;
    margin: auto !important;
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid rgba(212, 175, 55, 0.25);
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(212, 175, 55, 0.1);
    padding: 18px 22px !important;
    box-sizing: border-box !important;
    max-height: calc(100vh - 48px) !important;
    max-height: calc(100dvh - 48px) !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    position: relative !important;
    transform: none !important;
}
.order-modal-dialog .modal-header {
    flex-shrink: 0 !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.order-modal-footer {
    flex-shrink: 0 !important;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}
.order-modal-dialog .modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.order-modal-dialog .modal-close:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
    transform: scale(1.05);
}
.order-modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: rgba(212, 175, 55, 0.35) transparent;
}
.order-modal-body::-webkit-scrollbar {
    width: 5px;
}
.order-modal-body::-webkit-scrollbar-track {
    background: transparent;
}
.order-modal-body::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.35);
    border-radius: 10px;
}
.modal-client-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: linear-gradient(135deg, #fdfcf9 0%, #ffffff 100%);
    border: 1px solid #f3ece0;
    border-radius: 14px;
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}
.modal-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e8d08d 0%, #b38b22 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
    border: 2px solid #ffffff;
    box-shadow: 0 4px 10px rgba(179, 139, 34, 0.25);
}
.modal-contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}
.modal-contact-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.modal-contact-card:hover {
    border-color: var(--gold);
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.08);
}
.copy-mini-btn {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.copy-mini-btn:hover {
    background: #ffffff;
    color: var(--gold-dark);
    border-color: var(--gold);
}
.order-section-card {
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 14px;
}
.birth-chart-card {
    background: linear-gradient(135deg, #fbfaff 0%, #ffffff 100%);
    border: 1px solid rgba(99, 102, 241, 0.25);
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.04);
}
.customized-card {
    background: linear-gradient(135deg, #fffdf8 0%, #ffffff 100%);
    border: 1px solid rgba(212, 175, 55, 0.3);
    box-shadow: 0 2px 8px rgba(212, 175, 55, 0.04);
}
.notes-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.section-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.section-card-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 6px;
}
.birth-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.birth-detail-item {
    background: #ffffff;
    border: 1px solid #ede9fe;
    border-radius: 8px;
    padding: 8px 12px;
}
.birth-detail-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--purple-accent);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}
.birth-detail-value {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-word;
}
.intention-text {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--text-primary);
    font-weight: 500;
    background: #ffffff;
    border: 1px solid #fef3c7;
    border-left: 3px solid var(--gold);
    border-radius: 8px;
    padding: 10px 14px;
    word-break: break-word;
}
.notes-text {
    font-size: 0.88rem;
    line-height: 1.6;
    color: var(--text-secondary);
    white-space: pre-wrap;
    word-break: break-word;
}
.order-status-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.modal-reply-group {
    margin-bottom: 6px;
}
.btn-luxury-wa {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: #ffffff !important;
    border: none !important;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 10px 16px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-luxury-wa:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(37, 211, 102, 0.35);
    color: #ffffff !important;
}
.btn-luxury-email {
    background: linear-gradient(135deg, #d4af37 0%, #b38b22 100%);
    color: #ffffff !important;
    border: none !important;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 10px 16px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.25);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-luxury-email:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(212, 175, 55, 0.35);
    color: #ffffff !important;
}
.order-modal-footer {
    flex-shrink: 0;
    border-top: 1px solid var(--card-border);
    padding-top: 12px;
    margin-top: 4px;
}
@media (max-width: 640px) {
    .modal-overlay {
        padding: 8px !important;
    }
    .order-modal-dialog {
        width: 100%;
        max-height: calc(100vh - 16px);
        padding: 14px 12px;
        border-radius: 16px;
    }
    .modal-client-banner {
        padding: 10px 12px;
        gap: 12px;
    }
    .modal-avatar {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    .modal-contact-grid {
        grid-template-columns: 1fr;
    }
    .birth-details-grid {
        grid-template-columns: 1fr;
    }
    .order-status-card {
        flex-direction: column;
        align-items: stretch;
    }
    .modal-reply-group {
        flex-direction: column;
    }
}
</style>

<!-- Top Filter & Stats Bar -->
<div class="search-bar flex-between mb-3">
    <div class="flex gap-2" style="flex: 1; flex-wrap: wrap; align-items: center;">
        <!-- Search -->
        <form action="orders.php" method="GET" class="search-input-wrapper" style="max-width: 320px;">
            <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Search orders by customer or detail..." 
                value="<?= htmlspecialchars($search) ?>"
            >
            <input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php if (!empty($search)): ?>
                <a href="orders.php?type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 2px;">
                    <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
                </a>
            <?php endif; ?>
        </form>

        <!-- Filter by Type -->
        <form action="orders.php" method="GET" id="typeFilterForm">
            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <select name="type" class="filter-select" onchange="this.form.submit()">
                <option value="All" <?= $typeFilter === 'All' ? 'selected' : '' ?>>All Order Types</option>
                <option value="birth-chart" <?= $typeFilter === 'birth-chart' ? 'selected' : '' ?>>Birth Chart Bracelets</option>
                <option value="customized" <?= $typeFilter === 'customized' ? 'selected' : '' ?>>Customized Intentions</option>
            </select>
        </form>

        <!-- Filter by Status -->
        <form action="orders.php" method="GET" id="statusFilterForm">
            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>">
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Statuses (<?= count($ordersList) ?>)</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending (<?= $pendingTotal ?>)</option>
                <option value="contacted" <?= $statusFilter === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </form>

        <!-- Stats Counter -->
        <?php if ($pendingTotal > 0): ?>
            <span class="badge badge-warning flex items-center gap-1" style="font-size: 0.82rem; padding: 6px 12px;">
                <i data-lucide="package" style="width: 14px; height: 14px;"></i> <?= $pendingTotal ?> Pending <?= $pendingTotal === 1 ? 'Order' : 'Orders' ?>
            </span>
        <?php else: ?>
            <span class="badge badge-success flex items-center gap-1" style="font-size: 0.82rem; padding: 6px 12px;">
                <i data-lucide="check" style="width: 14px; height: 14px;"></i> All Orders Handled
            </span>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card" style="padding: 16px 16px;">
    <?php if (empty($ordersList)): ?>
        <div style="text-align: center; padding: 50px 20px;">
            <div style="margin-bottom: 12px;">
                <i data-lucide="package" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
            </div>
            <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 6px;">No bracelet orders found</h3>
            <p class="text-muted" style="font-size: 0.88rem;">
                <?= (!empty($search) || $typeFilter !== 'All' || $statusFilter !== 'All') ? 'No orders match your selected filters.' : 'There are currently no custom bracelet requests.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table orders-table">
                <thead>
                    <tr>
                        <th style="min-width: 170px;">Customer Name</th>
                        <th style="width: 120px;">Order Type</th>
                        <th style="width: 140px;">Contact</th>
                        <th style="width: 100px; text-align: center;">Status</th>
                        <th style="width: 125px;">Received Date</th>
                        <th style="text-align: right; width: 115px; padding-right: 14px; white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordersList as $order): ?>
                        <?php 
                            $orderType = $order['order_type'] ?? 'customized';
                            $status = $order['status'] ?? 'pending';
                        ?>
                        <tr id="orderRow_<?= $order['id'] ?>">
                            <td>
                                <div>
                                    <strong class="order-cust-title" onclick="openOrderDetailModal(<?= (int)$order['id'] ?>)">
                                        <?= htmlspecialchars($order['name']) ?>
                                    </strong>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <?= htmlspecialchars($order['email'] ?? '—') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($orderType === 'birth-chart'): ?>
                                    <span class="badge" style="background: rgba(99,102,241,0.08); color: var(--purple-accent); border: 1px solid rgba(99,102,241,0.2); font-size: 0.78rem;">
                                        Birth Chart
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-gold" style="font-size: 0.78rem;">
                                        Customized
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                                <?php if (!empty($order['phone'])): ?>
                                    <div>
                                        <a href="tel:<?= htmlspecialchars($order['phone']) ?>" style="color: inherit; text-decoration: none; font-weight: 500;">
                                            <?= htmlspecialchars($order['phone']) ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div>—</div>
                                <?php endif; ?>

                                <?php if (!empty($order['whatsapp']) && $order['whatsapp'] !== $order['phone']): ?>
                                    <div class="flex items-center gap-1 text-muted" style="font-size: 0.74rem; margin-top: 2px;">
                                        <i data-lucide="message-circle" style="width: 11px; height: 11px; color: #25D366;"></i>
                                        <?= htmlspecialchars($order['whatsapp']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($status === 'completed'): ?>
                                    <span class="badge badge-success" style="font-size: 0.78rem;">Completed</span>
                                <?php elseif ($status === 'contacted'): ?>
                                    <span class="badge badge-info" style="font-size: 0.78rem;">Contacted</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size: 0.78rem;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); white-space: nowrap;">
                                    <?= !empty($order['created_at']) ? date('M d, Y', strtotime($order['created_at'])) : '—' ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.74rem; white-space: nowrap;">
                                    <?= !empty($order['created_at']) ? date('h:i A', strtotime($order['created_at'])) : '' ?>
                                </div>
                            </td>
                            <td style="text-align: right; white-space: nowrap; padding-right: 14px;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <!-- View Button -->
                                    <button 
                                        type="button" 
                                        class="btn btn-purple btn-sm flex items-center gap-1" 
                                        title="View Full Order Details"
                                        onclick="openOrderDetailModal(<?= (int)$order['id'] ?>)"
                                    >
                                        <i data-lucide="eye" style="width: 13px; height: 13px;"></i> View
                                    </button>

                                    <!-- Delete Button (Integrated with admin.js global confirm modal) -->
                                    <form id="deleteOrderForm_<?= $order['id'] ?>" action="orders.php" method="POST" class="delete-form" style="display: inline;" data-item-name="<?= htmlspecialchars($order['name']) ?>'s Bracelet Order">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                                        <button 
                                            type="submit" 
                                            class="btn btn-danger btn-icon btn-sm flex items-center justify-center" 
                                            title="Delete Order"
                                        >
                                            <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Fallback Form for Programmatic Delete -->
<form id="deleteOrderForm" action="orders.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteOrderId">
</form>

<!-- LUXURY VIEW DETAIL MODAL -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal order-modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2" style="font-size: 1.05rem; font-weight: 600;">
                <i data-lucide="package" style="width: 18px; height: 18px; color: var(--gold);"></i> Bracelet Order Details
            </h3>
            <button type="button" class="modal-close flex items-center justify-center" onclick="closeOrderDetailModal()" title="Close dialog">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div class="order-modal-body">
            <!-- Client Profile Banner -->
            <div class="modal-client-banner">
                <div class="modal-avatar" id="modalOrderAvatarInitial">A</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <h2 id="modalOrderCustomer" style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0; line-height: 1.2;"></h2>
                        <div class="flex items-center gap-1">
                            <span id="modalOrderTypeBadge" class="badge"></span>
                            <span id="modalOrderStatusBadge" class="badge"></span>
                        </div>
                    </div>
                    <div class="text-muted flex items-center gap-1 mt-1" style="font-size: 0.8rem;">
                        <i data-lucide="clock" style="width: 13px; height: 13px; color: var(--gold);"></i>
                        <span id="modalOrderDate"></span>
                    </div>
                </div>
            </div>

            <!-- Contact Details Cards Grid -->
            <div class="modal-contact-grid">
                <!-- Email Card -->
                <div class="modal-contact-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                            <i data-lucide="mail" style="width: 12px; height: 12px; color: var(--gold);"></i> Email Address
                        </span>
                        <button type="button" class="copy-mini-btn" onclick="copyOrderValue('modalOrderEmail', this)" title="Copy email">
                            <i data-lucide="copy" style="width: 12px; height: 12px;"></i> Copy
                        </button>
                    </div>
                    <a id="modalOrderEmail" href="" class="text-gold" style="text-decoration: none; font-weight: 600; font-size: 0.88rem; word-break: break-all;"></a>
                </div>

                <!-- Phone & WhatsApp Card -->
                <div class="modal-contact-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                            <i data-lucide="phone" style="width: 12px; height: 12px; color: #10b981;"></i> Phone & WhatsApp
                        </span>
                        <button type="button" class="copy-mini-btn" onclick="copyOrderValue('modalOrderPhone', this)" title="Copy phone">
                            <i data-lucide="copy" style="width: 12px; height: 12px;"></i> Copy
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <a id="modalOrderPhone" href="" style="color: var(--text-primary); text-decoration: none; font-weight: 600; font-size: 0.88rem;"></a>
                        <span id="modalOrderWhatsappTag" style="font-size: 0.75rem; color: var(--text-muted);"></span>
                    </div>
                </div>
            </div>

            <!-- Specific Order Section (Birth Chart vs Customized) -->
            <div id="birthChartSection" class="order-section-card birth-chart-card" style="display: none;">
                <div class="section-card-header">
                    <span class="section-card-title">
                        <i data-lucide="sparkles" style="width: 14px; height: 14px; color: var(--purple-accent);"></i> Astrological Birth Details
                    </span>
                    <span class="badge badge-purple-subtle" style="font-size: 0.72rem; background: rgba(99,102,241,0.1); color: var(--purple-accent);">Birth Chart</span>
                </div>
                <div class="birth-details-grid">
                    <div class="birth-detail-item">
                        <span class="birth-detail-label"><i data-lucide="calendar" style="width: 12px; height: 12px;"></i> Date of Birth</span>
                        <strong class="birth-detail-value" id="modalOrderDOB">—</strong>
                    </div>
                    <div class="birth-detail-item">
                        <span class="birth-detail-label"><i data-lucide="clock" style="width: 12px; height: 12px;"></i> Time of Birth</span>
                        <strong class="birth-detail-value" id="modalOrderTOB">—</strong>
                    </div>
                    <div class="birth-detail-item" style="grid-column: 1 / -1;">
                        <span class="birth-detail-label"><i data-lucide="map-pin" style="width: 12px; height: 12px;"></i> Place of Birth</span>
                        <strong class="birth-detail-value" id="modalOrderPOB">—</strong>
                    </div>
                </div>
            </div>

            <div id="customizedSection" class="order-section-card customized-card" style="display: none;">
                <div class="section-card-header">
                    <span class="section-card-title">
                        <i data-lucide="gem" style="width: 14px; height: 14px; color: var(--gold);"></i> Custom Healing Intention
                    </span>
                    <span class="badge badge-gold" style="font-size: 0.72rem;">Custom Intention</span>
                </div>
                <div id="modalOrderIntention" class="intention-text"></div>
            </div>

            <!-- Client Additional Message Card -->
            <div id="clientNotesSection" class="order-section-card notes-card" style="display: none;">
                <div class="section-card-header">
                    <span class="section-card-title" style="color: var(--text-muted);">
                        <i data-lucide="message-square" style="width: 13px; height: 13px; color: var(--gold);"></i> Additional Client Notes & Requests
                    </span>
                </div>
                <div id="modalOrderMessage" class="notes-text"></div>
            </div>

            <!-- Status Update Controller -->
            <form action="orders.php" method="POST" class="order-status-card">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="modalStatusOrderId">
                <div class="flex items-center gap-2">
                    <label class="form-label" style="margin-bottom: 0; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); white-space: nowrap;">
                        Order Status:
                    </label>
                    <select name="status" id="modalStatusSelect" class="form-control" style="width: 160px; padding: 7px 12px; font-size: 0.85rem;">
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-gold btn-sm" style="padding: 7px 16px;">
                    Update Status
                </button>
            </form>

            <!-- Direct WhatsApp & Email Outreach Buttons -->
            <div class="flex gap-2 modal-reply-group">
                <a id="modalOrderWhatsAppBtn" href="" target="_blank" class="btn btn-luxury-wa flex-1 flex items-center justify-center gap-2">
                    <i data-lucide="message-circle" style="width: 17px; height: 17px;"></i> Contact via WhatsApp
                </a>
                <a id="modalOrderEmailBtn" href="" class="btn btn-luxury-email flex-1 flex items-center justify-center gap-2">
                    <i data-lucide="send" style="width: 17px; height: 17px;"></i> Send Email
                </a>
            </div>
        </div>

        <!-- Footer: Close & Delete -->
        <div class="order-modal-footer flex-between">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeOrderDetailModal()" style="font-weight: 600; padding: 8px 18px;">
                Close
            </button>
            <button type="button" class="btn btn-danger btn-sm flex items-center gap-1" id="modalOrderDeleteBtn" style="font-weight: 600; padding: 8px 16px;">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Delete Order
            </button>
        </div>
    </div>
</div>

<script>
// Orders Data Map (immune to quotes, newlines, and escaping issues)
const ordersData = <?= json_encode($ordersMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let currentModalOrderId = null;

// Format date utility
function formatHumanDate(dateStr) {
    if (!dateStr) return 'Recently';
    try {
        const d = new Date(dateStr.replace(/-/g, '/'));
        if (isNaN(d.getTime())) return dateStr;
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const day = String(d.getDate()).padStart(2, '0');
        const year = d.getFullYear();
        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const strTime = String(hours).padStart(2, '0') + ':' + minutes + ' ' + ampm;
        return `${month} ${day}, ${year} · ${strTime}`;
    } catch(e) {
        return dateStr;
    }
}

// Copy to clipboard utility
function copyOrderValue(elemId, btn) {
    const el = document.getElementById(elemId);
    if (!el) return;
    const text = el.textContent || el.innerText;
    if (!text || text === 'Not provided') return;
    navigator.clipboard.writeText(text).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="check" style="width: 12px; height: 12px; color: #10b981;"></i> Copied';
        if (window.lucide) lucide.createIcons();
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            if (window.lucide) lucide.createIcons();
        }, 1800);
    });
}

// Delete Single (triggers dedicated .delete-form for global modal confirmation)
function deleteOrderSingle(id) {
    const targetForm = document.getElementById('deleteOrderForm_' + id);
    if (targetForm) {
        targetForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    } else {
        if (confirm('Are you sure you want to delete this bracelet order?')) {
            document.getElementById('deleteOrderId').value = id;
            document.getElementById('deleteOrderForm').submit();
        }
    }
}

// Open Order Detail Modal
function openOrderDetailModal(orderOrId) {
    const order = (typeof orderOrId === 'object' && orderOrId !== null) ? orderOrId : ordersData[orderOrId];
    if (!order) return;
    currentModalOrderId = order.id;

    // Customer Name & Monogram Avatar
    const nameStr = (order.name || 'Customer').trim();
    document.getElementById('modalOrderAvatarInitial').textContent = nameStr.charAt(0).toUpperCase();
    document.getElementById('modalOrderCustomer').textContent = nameStr;
    document.getElementById('modalOrderDate').textContent = 'Submitted on ' + formatHumanDate(order.created_at);
    
    // Status Form values
    document.getElementById('modalStatusOrderId').value = order.id;
    document.getElementById('modalStatusSelect').value = order.status || 'pending';

    // Type Badge
    const typeBadge = document.getElementById('modalOrderTypeBadge');
    if (order.order_type === 'birth-chart') {
        typeBadge.className = 'badge';
        typeBadge.style.cssText = 'background: rgba(99,102,241,0.1); color: var(--purple-accent); border: 1px solid rgba(99,102,241,0.25); font-size: 0.75rem;';
        typeBadge.textContent = 'Birth Chart';
    } else {
        typeBadge.className = 'badge badge-gold';
        typeBadge.style.cssText = 'font-size: 0.75rem;';
        typeBadge.textContent = 'Customized';
    }

    // Status Badge
    const statusBadge = document.getElementById('modalOrderStatusBadge');
    if (order.status === 'completed') {
        statusBadge.className = 'badge badge-success';
        statusBadge.textContent = 'Completed';
    } else if (order.status === 'contacted') {
        statusBadge.className = 'badge badge-info';
        statusBadge.textContent = 'Contacted';
    } else {
        statusBadge.className = 'badge badge-warning';
        statusBadge.textContent = 'Pending';
    }

    // Contact info
    const emailEl = document.getElementById('modalOrderEmail');
    if (order.email) {
        emailEl.href = 'mailto:' + order.email;
        emailEl.textContent = order.email;
    } else {
        emailEl.removeAttribute('href');
        emailEl.textContent = 'Not provided';
    }

    const phoneEl = document.getElementById('modalOrderPhone');
    if (order.phone) {
        phoneEl.href = 'tel:' + order.phone;
        phoneEl.textContent = order.phone;
    } else {
        phoneEl.removeAttribute('href');
        phoneEl.textContent = 'Not provided';
    }

    const waTag = document.getElementById('modalOrderWhatsappTag');
    if (order.whatsapp && order.whatsapp !== order.phone) {
        waTag.textContent = '(WA: ' + order.whatsapp + ')';
    } else {
        waTag.textContent = '';
    }

    // Birth chart vs Customized specifics
    const birthSection = document.getElementById('birthChartSection');
    const customSection = document.getElementById('customizedSection');

    if (order.order_type === 'birth-chart') {
        birthSection.style.display = 'block';
        customSection.style.display = 'none';

        document.getElementById('modalOrderDOB').textContent = order.date_of_birth || '—';
        document.getElementById('modalOrderTOB').textContent = order.time_of_birth || '—';
        document.getElementById('modalOrderPOB').textContent = order.place_of_birth || '—';
    } else {
        birthSection.style.display = 'none';
        customSection.style.display = 'block';

        document.getElementById('modalOrderIntention').textContent = order.intention || 'General energetic balance, spiritual grounding, and healing wellness';
    }

    // Additional message
    const notesSection = document.getElementById('clientNotesSection');
    if (order.message && order.message.trim() !== '') {
        notesSection.style.display = 'block';
        document.getElementById('modalOrderMessage').textContent = order.message;
    } else {
        notesSection.style.display = 'none';
    }

    // Pre-filled WhatsApp and Email outreach
    const phoneClean = (order.whatsapp || order.phone || '').replace(/[^\d+]/g, '');
    const typeLabel = (order.order_type === 'birth-chart') ? 'Birth Chart' : 'Customized';
    
    if (phoneClean) {
        const waMsg = encodeURIComponent(`Namaste ${nameStr}, thank you for your order of a ${typeLabel} Reiki Bracelet with Reiki Bliss! We are preparing your energetic customization. Could we confirm a few details with you?`);
        document.getElementById('modalOrderWhatsAppBtn').href = `https://wa.me/${phoneClean.replace('+', '')}?text=${waMsg}`;
        document.getElementById('modalOrderWhatsAppBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('modalOrderWhatsAppBtn').style.display = 'none';
    }

    const emailSub = encodeURIComponent(`Reiki Bliss: Update on your ${typeLabel} Bracelet Order`);
    const emailBody = encodeURIComponent(`Dear ${nameStr},\n\nThank you for ordering your ${typeLabel} Bracelet from Reiki Bliss Healing Center.\n\nWe are reviewing your energetic preferences to craft your energized gemstone piece.\n\nWarm regards,\nReiki Bliss Healers`);
    document.getElementById('modalOrderEmailBtn').href = `mailto:${order.email || ''}?subject=${emailSub}&body=${emailBody}`;

    // Delete in modal
    document.getElementById('modalOrderDeleteBtn').onclick = function() {
        deleteOrderSingle(order.id);
    };

    const modal = document.getElementById('orderDetailModal');
    if (modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    if (window.lucide) lucide.createIcons();
}

function closeOrderDetailModal() {
    const modal = document.getElementById('orderDetailModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close on backdrop click
const ordModalEl = document.getElementById('orderDetailModal');
if (ordModalEl) {
    ordModalEl.addEventListener('click', function(e) {
        if (e.target === this) {
            closeOrderDetailModal();
        }
    });
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && ordModalEl && ordModalEl.classList.contains('active')) {
        closeOrderDetailModal();
    }
});

// Auto open modal if URL has ?view=ID
<?php if ($viewId > 0): ?>
    <?php 
        $autoOrder = null;
        foreach ($ordersList as $o) {
            if ($o['id'] == $viewId) {
                $autoOrder = $o;
                break;
            }
        }
    ?>
    <?php if ($autoOrder): ?>
        openOrderDetailModal(<?= (int)$autoOrder['id'] ?>);
    <?php endif; ?>
<?php endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>
