<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'All');
$viewId = (int)($_GET['view'] ?? 0);
$error = '';

// =========================================================================
// 1. AJAX & AUTO MARK-READ HANDLER
// =========================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'mark_read' && isset($_GET['id'])) {
    $ajaxId = (int)$_GET['id'];
    if ($ajaxId > 0) {
        $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id = ?")->execute([$ajaxId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Auto-mark as read if opened via ?view=ID
if ($viewId > 0) {
    try {
        $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id = ?")->execute([$viewId]);
    } catch (PDOException $e) {}
}

// =========================================================================
// 2. POST ACTIONS (Mark All Read, Single Actions, Bulk Actions)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // A. MARK ALL AS READ
    if ($postAction === 'mark_all_read') {
        try {
            $pdo->query("UPDATE contact_inquiries SET is_read = 1 WHERE is_read = 0");
            $_SESSION['flash_success'] = "All unread inquiries marked as read!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error updating inquiries: " . $e->getMessage();
        }
        header("Location: inquiries.php");
        exit;
    }

    // B. TOGGLE READ / UNREAD STATUS (Single)
    if ($postAction === 'toggle_read') {
        $inqId = (int)($_POST['id'] ?? 0);
        if ($inqId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 - is_read WHERE id = ?");
                $stmt->execute([$inqId]);
                $_SESSION['flash_success'] = "Inquiry status updated!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error updating status: " . $e->getMessage();
            }
        }
        header("Location: inquiries.php");
        exit;
    }

    // C. DELETE SINGLE INQUIRY
    if ($postAction === 'delete') {
        $inqId = (int)($_POST['id'] ?? 0);
        if ($inqId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id = ?");
                $stmt->execute([$inqId]);
                $_SESSION['flash_success'] = "Inquiry deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error deleting inquiry: " . $e->getMessage();
            }
        }
        header("Location: inquiries.php");
        exit;
    }

    // D. BULK ACTIONS (Mark Selected Read or Delete Selected)
    if (isset($_POST['bulk_action']) && !empty($_POST['inquiry_ids']) && is_array($_POST['inquiry_ids'])) {
        $ids = array_filter(array_map('intval', $_POST['inquiry_ids']));
        if (!empty($ids)) {
            $inClause = implode(',', array_fill(0, count($ids), '?'));
            
            if ($_POST['bulk_action'] === 'mark_read') {
                try {
                    $stmt = $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id IN ($inClause)");
                    $stmt->execute($ids);
                    $_SESSION['flash_success'] = count($ids) . " inquiries marked as read!";
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Bulk update error: " . $e->getMessage();
                }
            } elseif ($_POST['bulk_action'] === 'delete') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id IN ($inClause)");
                    $stmt->execute($ids);
                    $_SESSION['flash_success'] = count($ids) . " inquiries deleted successfully!";
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Bulk delete error: " . $e->getMessage();
                }
            }
        }
        header("Location: inquiries.php");
        exit;
    }
}

// =========================================================================
// 3. FETCH INQUIRIES & UNREAD COUNT
// =========================================================================

$unreadTotal = 0;
try {
    $unreadTotal = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE is_read = 0")->fetchColumn();
} catch (PDOException $e) {}

$whereParts = [];
$params = [];

if (!empty($search)) {
    $whereParts[] = "(name LIKE ? OR email LIKE ? OR message LIKE ? OR phone LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($statusFilter === 'Unread') {
    $whereParts[] = "is_read = 0";
} elseif ($statusFilter === 'Read') {
    $whereParts[] = "is_read = 1";
}

$whereClause = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';
$sql = "SELECT * FROM contact_inquiries {$whereClause} ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inquiriesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $inquiriesList = [];
    $error = "Error loading inquiries: " . $e->getMessage();
}

$pageTitle = 'Contact Inquiries';
require_once 'includes/admin-header.php';
?>

<!-- Top Filter & Stats Bar -->
<div class="search-bar flex-between mb-3">
    <div class="flex gap-2" style="flex: 1; flex-wrap: wrap; align-items: center;">
        <!-- Search -->
        <form action="inquiries.php" method="GET" class="search-input-wrapper" style="max-width: 340px;">
            <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Search by name, email, or message..." 
                value="<?= htmlspecialchars($search) ?>"
            >
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php if (!empty($search)): ?>
                <a href="inquiries.php?status=<?= urlencode($statusFilter) ?>" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 2px;">
                    <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
                </a>
            <?php endif; ?>
        </form>

        <!-- Status Filter -->
        <form action="inquiries.php" method="GET" id="statusFilterForm">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Inquiries (<?= count($inquiriesList) ?>)</option>
                <option value="Unread" <?= $statusFilter === 'Unread' ? 'selected' : '' ?>>Unread (<?= $unreadTotal ?>)</option>
                <option value="Read" <?= $statusFilter === 'Read' ? 'selected' : '' ?>>Read Inquiries</option>
            </select>
        </form>

        <!-- Stats Mini-Bar -->
        <?php if ($unreadTotal > 0): ?>
            <span class="badge badge-danger flex items-center gap-1" style="font-size: 0.82rem; padding: 6px 12px;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #ffffff; display: inline-block;"></span>
                <?= $unreadTotal ?> Unread <?= $unreadTotal === 1 ? 'Inquiry' : 'Inquiries' ?>
            </span>
        <?php else: ?>
            <span class="badge badge-success flex items-center gap-1" style="font-size: 0.82rem; padding: 6px 12px;">
                <i data-lucide="check-check" style="width: 14px; height: 14px;"></i> All Inquiries Read
            </span>
        <?php endif; ?>
    </div>

    <!-- Mark All Read Form -->
    <?php if ($unreadTotal > 0): ?>
        <form action="inquiries.php" method="POST" onsubmit="return confirm('Mark all unread inquiries as read?');">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-outline btn-sm text-gold flex items-center gap-1">
                <i data-lucide="check-check" style="width: 14px; height: 14px;"></i> Mark All Read
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Bulk Actions Form -->
<form action="inquiries.php" method="POST" id="bulkForm">
    <!-- Floating Bulk Action Bar (Hidden by default, appears when rows selected) -->
    <div id="bulkActionBar" class="bulk-actions-bar" style="display: none;">
        <div class="flex items-center gap-2">
            <strong style="color: var(--gold);"><span id="selectedCountDisplay">0</span> inquiries selected</strong>
        </div>
        <div class="flex gap-2">
            <button type="submit" name="bulk_action" value="mark_read" class="btn btn-purple btn-sm flex items-center gap-1">
                <i data-lucide="check" style="width: 14px; height: 14px;"></i> Mark Selected as Read
            </button>
            <button type="submit" name="bulk_action" value="delete" class="btn btn-danger btn-sm flex items-center gap-1" onclick="return confirm('Delete all selected inquiries? This cannot be undone.');">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="admin-card">
        <?php if (empty($inquiriesList)): ?>
            <div style="text-align: center; padding: 50px 20px;">
                <div style="margin-bottom: 12px;">
                    <i data-lucide="inbox" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
                </div>
                <h3 style="font-size: 1.15rem; color: #ffffff; margin-bottom: 6px;">No inquiries found</h3>
                <p class="text-muted" style="font-size: 0.88rem;">
                    <?= (!empty($search) || $statusFilter !== 'All') ? 'No messages match your current filters.' : 'Your contact form inbox is currently clear.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" title="Select All">
                            </th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Received Date</th>
                            <th style="text-align: right; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiriesList as $inq): ?>
                            <?php 
                                $isUnread = empty($inq['is_read']);
                                $msgSnippet = mb_strlen($inq['message'] ?? '') > 60 
                                    ? mb_substr($inq['message'], 0, 57) . '...' 
                                    : ($inq['message'] ?? '');
                                $dateStr = !empty($inq['created_at']) 
                                    ? date('M d, Y · h:i A', strtotime($inq['created_at'])) 
                                    : '—';
                            ?>
                            <tr class="<?= $isUnread ? 'row-unread' : '' ?>" id="inqRow_<?= $inq['id'] ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" name="inquiry_ids[]" value="<?= $inq['id'] ?>" class="inq-checkbox">
                                </td>
                                <td>
                                    <strong style="color: #ffffff; cursor: pointer;" onclick='openInquiryModal(<?= json_encode($inq) ?>)'>
                                        <?= htmlspecialchars($inq['name']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" class="text-muted" style="text-decoration: none; font-size: 0.85rem;" title="Send Email">
                                        <?= htmlspecialchars($inq['email']) ?>
                                    </a>
                                </td>
                                <td class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                                    <?= htmlspecialchars($inq['phone'] ?: '—') ?>
                                </td>
                                <td style="font-size: 0.85rem; color: #e2e8f0; cursor: pointer;" onclick='openInquiryModal(<?= json_encode($inq) ?>)'>
                                    <?= htmlspecialchars($msgSnippet) ?>
                                </td>
                                <td>
                                    <?php if ($isUnread): ?>
                                        <span class="badge badge-danger" id="statusBadge_<?= $inq['id'] ?>">Unread</span>
                                    <?php else: ?>
                                        <span class="badge badge-outline text-muted" style="border: 1px solid var(--card-border);" id="statusBadge_<?= $inq['id'] ?>">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted" style="font-size: 0.8rem; white-space: nowrap;">
                                    <?= $dateStr ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="flex gap-1" style="justify-content: flex-end;">
                                        <!-- View Modal Eye Button -->
                                        <button 
                                            type="button" 
                                            class="btn btn-outline btn-icon btn-sm flex items-center justify-center" 
                                            title="View Full Message & Reply"
                                            onclick='openInquiryModal(<?= json_encode($inq) ?>)'
                                        >
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                        </button>

                                        <!-- Toggle Read / Unread -->
                                        <button 
                                            type="button" 
                                            class="btn btn-purple btn-icon btn-sm flex items-center justify-center" 
                                            title="<?= $isUnread ? 'Mark as Read' : 'Mark as Unread' ?>"
                                            onclick="toggleInquiryStatus(<?= $inq['id'] ?>)"
                                        >
                                            <?php if ($isUnread): ?>
                                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                            <?php else: ?>
                                                <i data-lucide="mail" style="width: 14px; height: 14px;"></i>
                                            <?php endif; ?>
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            type="button" 
                                            class="btn btn-danger btn-icon btn-sm flex items-center justify-center" 
                                            title="Delete Inquiry"
                                            onclick="deleteInquirySingle(<?= $inq['id'] ?>)"
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
</form>

<!-- Hidden Form for Single Actions -->
<form id="singleActionForm" action="inquiries.php" method="POST" style="display: none;">
    <input type="hidden" name="action" id="singleActionType">
    <input type="hidden" name="id" id="singleActionId">
</form>

<!-- VIEW DETAIL MODAL -->
<div class="modal-overlay" id="inquiryDetailModal">
    <div class="modal" style="max-width: 650px;">
        <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2">
                <i data-lucide="mail" style="width: 18px; height: 18px; color: var(--gold);"></i> Inquiry Details
            </h3>
            <button type="button" class="modal-close flex items-center justify-center" onclick="closeInquiryModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <h2 id="modalClientName" style="font-size: 1.45rem; color: #ffffff; margin-bottom: 4px;"></h2>
                    <span id="modalReceivedDate" class="text-muted" style="font-size: 0.82rem;"></span>
                </div>
                <span id="modalStatusBadge" class="badge"></span>
            </div>

            <div class="detail-grid">
                <span class="detail-label">Email Address:</span>
                <span class="detail-value">
                    <a id="modalEmailLink" href="" class="text-gold" style="text-decoration: none;"></a>
                </span>

                <span class="detail-label">Phone Number:</span>
                <span class="detail-value">
                    <a id="modalPhoneLink" href="" style="color: #60a5fa; text-decoration: none;"></a>
                </span>
            </div>

            <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 18px; margin-bottom: 24px;">
                <label class="form-label" style="color: var(--gold); font-size: 0.8rem; letter-spacing: 1px; text-transform: uppercase;">
                    Message Content
                </label>
                <div id="modalFullMessage" style="color: #ffffff; font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap;"></div>
            </div>

            <!-- Action Buttons: WhatsApp & Email Reply -->
            <div class="flex gap-2" style="flex-wrap: wrap; margin-bottom: 16px;">
                <a id="modalWhatsAppBtn" href="" target="_blank" class="btn btn-whatsapp flex-1 flex items-center justify-center gap-1" style="text-decoration: none;">
                    <i data-lucide="message-circle" style="width: 16px; height: 16px;"></i> Reply via WhatsApp
                </a>
                <a id="modalEmailBtn" href="" class="btn btn-email flex-1 flex items-center justify-center gap-1" style="text-decoration: none;">
                    <i data-lucide="mail" style="width: 16px; height: 16px;"></i> Reply via Email
                </a>
            </div>

            <div class="flex-between" style="border-top: 1px solid var(--card-border); padding-top: 16px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeInquiryModal()">
                    Close Window
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="modalDeleteBtn">
                    Delete Inquiry
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Read / Unread Single
function toggleInquiryStatus(id) {
    document.getElementById('singleActionType').value = 'toggle_read';
    document.getElementById('singleActionId').value = id;
    document.getElementById('singleActionForm').submit();
}

// Delete Single
function deleteInquirySingle(id) {
    if (confirm('Are you sure you want to delete this inquiry?')) {
        document.getElementById('singleActionType').value = 'delete';
        document.getElementById('singleActionId').value = id;
        document.getElementById('singleActionForm').submit();
    }
}

// Open Detail Modal
function openInquiryModal(inq) {
    document.getElementById('modalClientName').textContent = inq.name;
    document.getElementById('modalReceivedDate').textContent = 'Received on ' + (inq.created_at || 'Recently');
    
    // Email links
    document.getElementById('modalEmailLink').href = 'mailto:' + inq.email;
    document.getElementById('modalEmailLink').textContent = inq.email;

    const emailSubject = encodeURIComponent('Re: Your Inquiry with Shree Sai Reiki & Healing Center');
    const emailBody = encodeURIComponent(`Dear ${inq.name},\n\nThank you for reaching out to Shree Sai Reiki. Regarding your message:\n"${inq.message}"\n\n`);
    document.getElementById('modalEmailBtn').href = `mailto:${inq.email}?subject=${emailSubject}&body=${emailBody}`;

    // Phone & WhatsApp links
    const rawPhone = (inq.phone || '').replace(/[^\d+]/g, '');
    if (rawPhone) {
        document.getElementById('modalPhoneLink').href = 'tel:' + rawPhone;
        document.getElementById('modalPhoneLink').textContent = inq.phone;
        
        const waText = encodeURIComponent(`Hello ${inq.name}, thank you for contacting Shree Sai Reiki Center regarding: "${inq.message.substring(0, 80)}...". How may we assist you?`);
        document.getElementById('modalWhatsAppBtn').href = `https://wa.me/${rawPhone.replace('+', '')}?text=${waText}`;
        document.getElementById('modalWhatsAppBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('modalPhoneLink').textContent = 'Not provided';
        document.getElementById('modalWhatsAppBtn').style.display = 'none';
    }

    document.getElementById('modalFullMessage').textContent = inq.message;

    // Delete in modal
    document.getElementById('modalDeleteBtn').onclick = function() {
        deleteInquirySingle(inq.id);
    };

    // Auto-mark as read in background if unread
    if (inq.is_read == 0) {
        fetch('inquiries.php?ajax=mark_read&id=' + inq.id)
            .then(res => res.json())
            .then(() => {
                inq.is_read = 1;
                const row = document.getElementById('inqRow_' + inq.id);
                if (row) row.classList.remove('row-unread');
                const badge = document.getElementById('statusBadge_' + inq.id);
                if (badge) {
                    badge.className = 'badge badge-outline text-muted';
                    badge.textContent = 'Read';
                }
            })
            .catch(() => {});
    }

    document.getElementById('inquiryDetailModal').classList.add('active');
    if (window.lucide) lucide.createIcons();
}

function closeInquiryModal() {
    document.getElementById('inquiryDetailModal').classList.remove('active');
}

// Bulk Checkboxes Handler
const selectAll = document.getElementById('selectAllCheckbox');
const inqCheckboxes = document.querySelectorAll('.inq-checkbox');
const bulkBar = document.getElementById('bulkActionBar');
const countDisplay = document.getElementById('selectedCountDisplay');

function updateBulkState() {
    let checkedCount = 0;
    inqCheckboxes.forEach(cb => {
        if (cb.checked) checkedCount++;
    });

    if (checkedCount > 0) {
        bulkBar.style.display = 'flex';
        countDisplay.textContent = checkedCount;
    } else {
        bulkBar.style.display = 'none';
    }
}

if (selectAll) {
    selectAll.addEventListener('change', function() {
        inqCheckboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateBulkState();
    });
}

inqCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkState);
});

// Auto open modal if URL has ?view=ID
<?php if ($viewId > 0): ?>
    <?php 
        $viewItem = null;
        foreach ($inquiriesList as $item) {
            if ($item['id'] == $viewId) {
                $viewItem = $item;
                break;
            }
        }
    ?>
    <?php if ($viewItem): ?>
        openInquiryModal(<?= json_encode($viewItem) ?>);
    <?php endif; ?>
<?php endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>
