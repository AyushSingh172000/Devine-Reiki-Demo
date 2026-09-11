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

$pageTitle = 'Bracelet Orders';
require_once 'includes/admin-header.php';
?>

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

<div class="admin-card">
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
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="min-width: 190px;">Customer Name</th>
                        <th style="width: 130px;">Order Type</th>
                        <th style="width: 125px;">Phone</th>
                        <th style="width: 125px;">WhatsApp</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 160px;">Received Date</th>
                        <th style="text-align: right; width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordersList as $order): ?>
                        <?php 
                            $orderType = $order['order_type'] ?? 'customized';
                            $status = $order['status'] ?? 'pending';
                            $dateStr = !empty($order['created_at']) 
                                ? date('M d, Y · h:i A', strtotime($order['created_at'])) 
                                : '—';
                        ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-primary); cursor: pointer;" onclick='openOrderDetailModal(<?= json_encode($order) ?>)'>
                                    <?= htmlspecialchars($order['name']) ?>
                                </strong>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    <?= htmlspecialchars($order['email'] ?? '—') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($orderType === 'birth-chart'): ?>
                                    <span class="badge" style="background: rgba(99,102,241,0.08); color: var(--purple-accent); border: 1px solid rgba(99,102,241,0.2);">
                                        Birth Chart
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-gold">
                                        Customized
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                                <a href="tel:<?= htmlspecialchars($order['phone']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($order['phone'] ?: '—') ?>
                                </a>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                                <?= htmlspecialchars($order['whatsapp'] ?: '—') ?>
                            </td>
                            <td>
                                <?php if ($status === 'completed'): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php elseif ($status === 'contacted'): ?>
                                    <span class="badge badge-info">Contacted</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 0.8rem; white-space: nowrap;">
                                <?= $dateStr ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <button 
                                        type="button" 
                                        class="btn btn-purple btn-sm flex items-center gap-1" 
                                        title="View Full Order Details"
                                        onclick='openOrderDetailModal(<?= json_encode($order) ?>)'
                                    >
                                        <i data-lucide="eye" style="width: 14px; height: 14px;"></i> View
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn btn-danger btn-icon btn-sm flex items-center justify-center" 
                                        title="Delete Order"
                                        onclick="deleteOrderSingle(<?= $order['id'] ?>)"
                                    >
                                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden Form for Delete -->
<form id="deleteOrderForm" action="orders.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteOrderId">
</form>

<!-- VIEW DETAIL MODAL -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2">
                <i data-lucide="package" style="width: 18px; height: 18px; color: var(--gold);"></i> Order Information
            </h3>
            <button type="button" class="modal-close flex items-center justify-center" onclick="closeOrderDetailModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div>
                    <h2 id="modalOrderCustomer" style="font-size: 1.4rem; color: var(--text-primary); margin-bottom: 4px;"></h2>
                    <span id="modalOrderDate" class="text-muted" style="font-size: 0.82rem;"></span>
                </div>
                <div class="flex gap-1">
                    <span id="modalOrderTypeBadge" class="badge"></span>
                    <span id="modalOrderStatusBadge" class="badge"></span>
                </div>
            </div>

            <!-- Customer Contact Information -->
            <div class="detail-grid" style="border-bottom: 1px solid var(--card-border); padding-bottom: 14px;">
                <span class="detail-label">Email Address:</span>
                <span class="detail-value">
                    <a id="modalOrderEmail" href="" class="text-gold" style="text-decoration: none;"></a>
                </span>

                <span class="detail-label">Phone Number:</span>
                <span class="detail-value">
                    <a id="modalOrderPhone" href="" style="color: #60a5fa; text-decoration: none;"></a>
                </span>

                <span class="detail-label">WhatsApp Number:</span>
                <span class="detail-value" id="modalOrderWhatsapp"></span>
            </div>

            <!-- Specific Order Data (Birth Chart vs Customized) -->
            <div id="birthChartSection" style="background: rgba(124,107,196,0.08); border: 1px solid rgba(124,107,196,0.3); border-radius: 10px; padding: 16px; margin: 16px 0; display: none;">
                <h4 style="font-size: 0.85rem; color: var(--gold); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                    Astrological Birth Details
                </h4>
                <div class="detail-grid" style="margin-bottom: 0;">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value" id="modalOrderDOB"></span>

                    <span class="detail-label">Time of Birth:</span>
                    <span class="detail-value" id="modalOrderTOB"></span>

                    <span class="detail-label">Place of Birth:</span>
                    <span class="detail-value" id="modalOrderPOB"></span>
                </div>
            </div>

            <div id="customizedSection" style="background: rgba(201,168,76,0.08); border: 1px solid rgba(201,168,76,0.3); border-radius: 10px; padding: 16px; margin: 16px 0; display: none;">
                <h4 style="font-size: 0.85rem; color: var(--gold); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                    Custom Healing Intention
                </h4>
                <div id="modalOrderIntention" style="color: var(--text-primary); font-size: 0.95rem; font-weight: 500;"></div>
            </div>

            <!-- Additional Client Message -->
            <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                <label class="form-label" style="color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase;">
                    Additional Client Notes &amp; Requests
                </label>
                <div id="modalOrderMessage" style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;"></div>
            </div>

            <!-- Status Update Controller -->
            <form action="orders.php" method="POST" style="background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="modalStatusOrderId">
                
                <div class="flex items-center gap-2">
                    <label class="form-label" style="margin-bottom: 0; white-space: nowrap;">Change Status:</label>
                    <select name="status" id="modalStatusSelect" class="form-control" style="width: 160px; padding: 7px 12px; font-size: 0.85rem;">
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-gold btn-sm">
                    Update Status
                </button>
            </form>

            <!-- Direct Contact Actions -->
            <div class="flex gap-2" style="flex-wrap: wrap; margin-bottom: 16px;">
                <a id="modalOrderWhatsAppBtn" href="" target="_blank" class="btn btn-whatsapp flex-1 flex items-center justify-center gap-1" style="text-decoration: none;">
                    <i data-lucide="message-circle" style="width: 16px; height: 16px;"></i> Contact via WhatsApp
                </a>
                <a id="modalOrderEmailBtn" href="" class="btn btn-email flex-1 flex items-center justify-center gap-1" style="text-decoration: none;">
                    <i data-lucide="mail" style="width: 16px; height: 16px;"></i> Contact via Email
                </a>
            </div>

            <div class="flex-between" style="border-top: 1px solid var(--card-border); padding-top: 16px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeOrderDetailModal()">
                    Close Window
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="modalOrderDeleteBtn">
                    Delete Order
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteOrderSingle(id) {
    if (confirm('Are you sure you want to delete this bracelet order?')) {
        document.getElementById('deleteOrderId').value = id;
        document.getElementById('deleteOrderForm').submit();
    }
}

function openOrderDetailModal(order) {
    document.getElementById('modalOrderCustomer').textContent = order.name;
    document.getElementById('modalOrderDate').textContent = 'Submitted on ' + (order.created_at || 'Recently');
    document.getElementById('modalStatusOrderId').value = order.id;
    document.getElementById('modalStatusSelect').value = order.status || 'pending';

    // Type Badge
    const typeBadge = document.getElementById('modalOrderTypeBadge');
    if (order.order_type === 'birth-chart') {
        typeBadge.className = 'badge';
        typeBadge.style.cssText = 'background: rgba(99,102,241,0.08); color: var(--purple-accent); border: 1px solid rgba(99,102,241,0.2);';
        typeBadge.textContent = 'Birth Chart';
    } else {
        typeBadge.className = 'badge badge-gold';
        typeBadge.style.cssText = '';
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
    document.getElementById('modalOrderEmail').href = 'mailto:' + (order.email || '');
    document.getElementById('modalOrderEmail').textContent = order.email || 'Not provided';
    document.getElementById('modalOrderPhone').href = 'tel:' + (order.phone || '');
    document.getElementById('modalOrderPhone').textContent = order.phone || 'Not provided';
    document.getElementById('modalOrderWhatsapp').textContent = order.whatsapp || 'Same as phone';

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

        document.getElementById('modalOrderIntention').textContent = order.intention || 'General energetic balance and wellness';
    }

    // Additional message
    document.getElementById('modalOrderMessage').textContent = order.message || 'No additional notes entered by client.';

    // Pre-filled WhatsApp and Email outreach
    const phoneClean = (order.whatsapp || order.phone || '').replace(/[^\d+]/g, '');
    const typeLabel = (order.order_type === 'birth-chart') ? 'Birth Chart' : 'Customized';
    
    if (phoneClean) {
        const waMsg = encodeURIComponent(`Namaste ${order.name}, thank you for your order of a ${typeLabel} Reiki Bracelet with Reiki Bliss! We are preparing your energetic customization. Could we confirm a few details with you?`);
        document.getElementById('modalOrderWhatsAppBtn').href = `https://wa.me/${phoneClean.replace('+', '')}?text=${waMsg}`;
        document.getElementById('modalOrderWhatsAppBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('modalOrderWhatsAppBtn').style.display = 'none';
    }

    const emailSub = encodeURIComponent(`Reiki Bliss: Update on your ${typeLabel} Bracelet Order`);
    const emailBody = encodeURIComponent(`Dear ${order.name},\n\nThank you for ordering your ${typeLabel} Bracelet from Reiki Bliss Healing Center.\n\nWe are reviewing your energetic preferences to craft your energized gemstone piece.\n\nWarm regards,\nReiki Bliss Healers`);
    document.getElementById('modalOrderEmailBtn').href = `mailto:${order.email}?subject=${emailSub}&body=${emailBody}`;

    // Delete in modal
    document.getElementById('modalOrderDeleteBtn').onclick = function() {
        deleteOrderSingle(order.id);
    };

    document.getElementById('orderDetailModal').classList.add('active');
    if (window.lucide) lucide.createIcons();
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal').classList.remove('active');
}

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
        openOrderDetailModal(<?= json_encode($autoOrder) ?>);
    <?php endif; ?>
<?php endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>
