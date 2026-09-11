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

<!-- Scoped Inquiries Styling -->
<style>
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
}
.inquiry-table thead th {
    font-size: 0.74rem;
    padding: 10px 10px;
    letter-spacing: 0.5px;
    background: #f8fafc;
}
.inquiry-table tbody td {
    vertical-align: middle !important;
    padding: 10px 10px;
}
.inquiry-msg-preview {
    max-width: 280px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    cursor: pointer;
    transition: color 0.15s ease;
}
.inquiry-msg-preview:hover {
    color: var(--gold-dark);
}
.admin-table tbody tr.row-unread {
    background: #fefdf9 !important;
}
.admin-table tbody tr.row-unread td:first-child::before {
    display: none !important;
}
.bulk-actions-bar {
    background: #ffffff;
    border: 1px solid var(--card-border);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
    border-radius: 12px;
    padding: 12px 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    animation: modalFadeIn 0.2s ease-out;
}
.badge-unread-pill {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-read-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
}

/* Luxury View Detail Modal */
.modal-overlay {
    padding: 16px !important;
}
.inquiry-modal-dialog {
    max-width: 600px;
    width: 100%;
    margin: auto;
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid rgba(212, 175, 55, 0.25);
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(212, 175, 55, 0.1);
    padding: 20px 24px;
    max-height: min(90vh, 740px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.inquiry-modal-dialog .modal-header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.inquiry-modal-dialog .modal-close {
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
.inquiry-modal-dialog .modal-close:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
    transform: scale(1.05);
}
.inquiry-modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: rgba(212, 175, 55, 0.35) transparent;
}
.inquiry-modal-body::-webkit-scrollbar {
    width: 5px;
}
.inquiry-modal-body::-webkit-scrollbar-track {
    background: transparent;
}
.inquiry-modal-body::-webkit-scrollbar-thumb {
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
.modal-message-box {
    background: #faf9f5;
    border: 1px solid #eee8da;
    border-left: 4px solid var(--gold);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 14px;
    max-height: 200px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(212, 175, 55, 0.3) transparent;
}
.modal-message-box::-webkit-scrollbar {
    width: 5px;
}
.modal-message-box::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.3);
    border-radius: 10px;
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
.inquiry-modal-footer {
    flex-shrink: 0;
    border-top: 1px solid var(--card-border);
    padding-top: 12px;
    margin-top: 4px;
}

/* Responsive Rules */
@media (max-width: 640px) {
    .modal-overlay {
        padding: 8px !important;
    }
    .inquiry-modal-dialog {
        width: 100%;
        max-height: calc(100vh - 16px);
        padding: 14px 12px;
        border-radius: 16px;
    }
    .modal-client-banner {
        flex-direction: row;
        text-align: left;
        gap: 12px;
        padding: 10px 12px;
        margin-bottom: 10px;
    }
    .modal-avatar {
        width: 42px;
        height: 42px;
        font-size: 1.15rem;
    }
    .modal-client-banner h2 {
        font-size: 1.05rem !important;
    }
    .modal-contact-grid {
        grid-template-columns: 1fr;
        gap: 6px;
        margin-bottom: 10px;
    }
    .modal-contact-card {
        padding: 8px 10px;
    }
    .modal-message-box {
        padding: 10px 12px;
        margin-bottom: 10px;
        max-height: 140px;
    }
    .modal-reply-group {
        flex-direction: column;
        gap: 8px;
    }
    .btn-luxury-wa, .btn-luxury-email {
        padding: 8px 14px;
        font-size: 0.84rem;
    }
    .inquiry-modal-footer {
        padding-top: 10px;
    }
    .search-bar.flex-between {
        flex-direction: column;
        align-items: stretch !important;
    }
    .search-input-wrapper {
        max-width: 100% !important;
        width: 100% !important;
    }
}
</style>

<!-- Top Filter & Stats Bar -->
<div class="search-bar flex-between mb-3" style="align-items: center; gap: 14px; flex-wrap: wrap;">
    <div class="flex gap-2" style="flex: 1; flex-wrap: wrap; align-items: center;">
        <!-- Search -->
        <form action="inquiries.php" method="GET" class="search-input-wrapper" style="min-width: 250px; max-width: 300px;">
            <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Search inquiries..." 
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
                <option value="Read" <?= $statusFilter === 'Read' ? 'selected' : '' ?>>Read</option>
            </select>
        </form>

        <!-- Stats Mini-Bar -->
        <?php if ($unreadTotal > 0): ?>
            <span class="badge badge-danger flex items-center gap-1" style="font-size: 0.8rem; padding: 6px 12px; font-weight: 600;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #ef4444; display: inline-block; box-shadow: 0 0 0 2px rgba(239,68,68,0.2);"></span>
                <?= $unreadTotal ?> Unread <?= $unreadTotal === 1 ? 'Inquiry' : 'Inquiries' ?>
            </span>
        <?php else: ?>
            <span class="badge badge-success flex items-center gap-1" style="font-size: 0.8rem; padding: 6px 12px; font-weight: 600;">
                <i data-lucide="check-check" style="width: 14px; height: 14px;"></i> All Inquiries Read
            </span>
        <?php endif; ?>
    </div>

    <!-- Mark All Read Form -->
    <?php if ($unreadTotal > 0): ?>
        <form action="inquiries.php" method="POST" onsubmit="return confirm('Mark all unread inquiries as read?');">
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-outline btn-sm flex items-center gap-1" style="border-color: rgba(179,139,45,0.4); color: var(--gold); font-weight: 600; background: rgba(179,139,45,0.05); padding: 8px 14px;">
                <i data-lucide="check-check" style="width: 15px; height: 15px;"></i> Mark All Read
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

    <div class="admin-card" style="padding: 16px 16px;">
        <?php if (empty($inquiriesList)): ?>
            <div style="text-align: center; padding: 50px 20px;">
                <div style="margin-bottom: 12px;">
                    <i data-lucide="inbox" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
                </div>
                <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 6px;">No inquiries found</h3>
                <p class="text-muted" style="font-size: 0.88rem;">
                    <?= (!empty($search) || $statusFilter !== 'All') ? 'No messages match your current filters.' : 'Your contact form inbox is currently clear.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table inquiry-table">
                    <thead>
                        <tr>
                            <th style="width: 32px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" title="Select All">
                            </th>
                            <th style="width: 135px;">Client Name</th>
                            <th style="width: 145px;">Email</th>
                            <th style="width: 105px;">Phone</th>
                            <th style="min-width: 170px;">Message Preview</th>
                            <th style="width: 75px; text-align: center;">Status</th>
                            <th style="width: 100px;">Received Date</th>
                            <th style="text-align: right; width: 115px; padding-right: 10px; white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiriesList as $inq): ?>
                            <?php 
                                $isUnread = empty($inq['is_read']);
                                $msgSnippet = $inq['message'] ?? '';
                            ?>
                            <tr class="<?= $isUnread ? 'row-unread' : '' ?>" id="inqRow_<?= $inq['id'] ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" name="inquiry_ids[]" value="<?= $inq['id'] ?>" class="inq-checkbox">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <?php if ($isUnread): ?>
                                            <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #ef4444; flex-shrink: 0; box-shadow: 0 0 0 2px rgba(239,68,68,0.2);"></span>
                                        <?php endif; ?>
                                        <strong style="color: var(--text-primary); font-size: 0.9rem; cursor: pointer;" onclick='openInquiryModal(<?= json_encode($inq) ?>)'>
                                            <?= htmlspecialchars($inq['name']) ?>
                                        </strong>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" class="text-muted" style="text-decoration: none; font-size: 0.84rem;" title="Send Email">
                                        <?= htmlspecialchars($inq['email']) ?>
                                    </a>
                                </td>
                                <td class="text-muted" style="font-size: 0.84rem; white-space: nowrap;">
                                    <?php if (!empty($inq['phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($inq['phone']) ?>" style="color: inherit; text-decoration: none;">
                                            <?= htmlspecialchars($inq['phone']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="inquiry-msg-preview" onclick='openInquiryModal(<?= json_encode($inq) ?>)' title="Click to view full message">
                                        <?= htmlspecialchars($msgSnippet) ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($isUnread): ?>
                                        <span class="badge badge-unread-pill" id="statusBadge_<?= $inq['id'] ?>">Unread</span>
                                    <?php else: ?>
                                        <span class="badge badge-read-pill" id="statusBadge_<?= $inq['id'] ?>">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); white-space: nowrap;">
                                        <?= !empty($inq['created_at']) ? date('M d, Y', strtotime($inq['created_at'])) : '—' ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.74rem; white-space: nowrap;">
                                        <?= !empty($inq['created_at']) ? date('h:i A', strtotime($inq['created_at'])) : '' ?>
                                    </div>
                                </td>
                                <td style="text-align: right; padding-right: 14px;">
                                    <div class="flex gap-1" style="justify-content: flex-end;">
                                        <!-- View Modal Eye Button -->
                                        <button 
                                            type="button" 
                                            class="btn btn-purple btn-sm flex items-center gap-1" 
                                            title="View Full Message & Reply"
                                            onclick='openInquiryModal(<?= json_encode($inq) ?>)'
                                        >
                                            <i data-lucide="eye" style="width: 13px; height: 13px;"></i> View
                                        </button>

                                        <!-- Toggle Read / Unread -->
                                        <button 
                                            type="button" 
                                            class="btn btn-outline btn-icon btn-sm flex items-center justify-center" 
                                            title="<?= $isUnread ? 'Mark as Read' : 'Mark as Unread' ?>"
                                            onclick="toggleInquiryStatus(<?= $inq['id'] ?>)"
                                        >
                                            <i data-lucide="<?= $isUnread ? 'check' : 'mail' ?>" style="width: 13px; height: 13px;"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            type="button" 
                                            class="btn btn-danger btn-icon btn-sm flex items-center justify-center" 
                                            title="Delete Inquiry"
                                            onclick="deleteInquirySingle(<?= $inq['id'] ?>)"
                                        >
                                            <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
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
    <div class="modal inquiry-modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2" style="font-size: 1.05rem; font-weight: 600;">
                <i data-lucide="mail-open" style="width: 18px; height: 18px; color: var(--gold);"></i> Contact Inquiry
            </h3>
            <button type="button" class="modal-close flex items-center justify-center" onclick="closeInquiryModal()" title="Close dialog">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div class="inquiry-modal-body">
            <!-- Client Profile Banner -->
            <div class="modal-client-banner">
                <div class="modal-avatar" id="modalAvatarInitial">
                    A
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <h2 id="modalClientName" style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0; line-height: 1.2;"></h2>
                        <span id="modalStatusBadge" class="badge"></span>
                    </div>
                    <div class="text-muted flex items-center gap-1 mt-1" style="font-size: 0.8rem;">
                        <i data-lucide="clock" style="width: 13px; height: 13px; color: var(--gold);"></i>
                        <span id="modalReceivedDate"></span>
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
                        <button type="button" class="copy-mini-btn" id="copyEmailBtn" onclick="copyContactValue('modalEmailLink', this)" title="Copy email">
                            <i data-lucide="copy" style="width: 12px; height: 12px;"></i> Copy
                        </button>
                    </div>
                    <a id="modalEmailLink" href="" class="text-gold" style="text-decoration: none; font-weight: 600; font-size: 0.88rem; word-break: break-all;"></a>
                </div>

                <!-- Phone Card -->
                <div class="modal-contact-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                            <i data-lucide="phone" style="width: 12px; height: 12px; color: #10b981;"></i> Phone Number
                        </span>
                        <button type="button" class="copy-mini-btn" id="copyPhoneBtn" onclick="copyContactValue('modalPhoneLink', this)" title="Copy phone">
                            <i data-lucide="copy" style="width: 12px; height: 12px;"></i> Copy
                        </button>
                    </div>
                    <a id="modalPhoneLink" href="" style="color: var(--text-primary); text-decoration: none; font-weight: 600; font-size: 0.88rem;"></a>
                </div>
            </div>

            <!-- Message Card -->
            <div class="modal-message-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="color: var(--gold-dark); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="message-square" style="width: 13px; height: 13px;"></i> Client Inquiry Message
                    </span>
                    <span id="modalMessageLen" class="text-muted" style="font-size: 0.72rem;"></span>
                </div>
                <div id="modalFullMessage" style="color: var(--text-primary); font-size: 0.93rem; line-height: 1.65; white-space: pre-wrap; word-break: break-word;"></div>
            </div>

            <!-- Action Buttons: WhatsApp & Email Reply -->
            <div class="flex gap-2 modal-reply-group" style="margin-bottom: 6px;">
                <a id="modalWhatsAppBtn" href="" target="_blank" class="btn btn-luxury-wa flex-1 flex items-center justify-center gap-2">
                    <i data-lucide="message-circle" style="width: 17px; height: 17px;"></i> Reply on WhatsApp
                </a>
                <a id="modalEmailBtn" href="" class="btn btn-luxury-email flex-1 flex items-center justify-center gap-2">
                    <i data-lucide="send" style="width: 17px; height: 17px;"></i> Reply via Email
                </a>
            </div>
        </div>

        <!-- Footer: Close & Delete -->
        <div class="inquiry-modal-footer flex-between">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeInquiryModal()" style="font-weight: 600; padding: 8px 18px;">
                Close
            </button>
            <button type="button" class="btn btn-danger btn-sm flex items-center gap-1" id="modalDeleteBtn" style="font-weight: 600; padding: 8px 16px;">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Delete Inquiry
            </button>
        </div>
    </div>
</div>

<script>
// Format date into executive human readable string
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
function copyContactValue(elemId, btn) {
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
    // Monogram Initial
    const nameStr = (inq.name || 'Client').trim();
    document.getElementById('modalAvatarInitial').textContent = nameStr.charAt(0).toUpperCase();
    document.getElementById('modalClientName').textContent = nameStr;
    document.getElementById('modalReceivedDate').textContent = 'Received on ' + formatHumanDate(inq.created_at);
    
    // Status Badge in modal
    const modalBadge = document.getElementById('modalStatusBadge');
    if (inq.is_read == 0) {
        modalBadge.className = 'badge badge-unread-pill';
        modalBadge.textContent = 'Unread';
    } else {
        modalBadge.className = 'badge badge-read-pill';
        modalBadge.textContent = 'Read';
    }

    // Email links
    document.getElementById('modalEmailLink').href = 'mailto:' + inq.email;
    document.getElementById('modalEmailLink').textContent = inq.email;

    const emailSubject = encodeURIComponent('Re: Your Inquiry with Reiki Bliss');
    const emailBody = encodeURIComponent(`Dear ${nameStr},\n\nThank you for reaching out to Reiki Bliss. Regarding your inquiry:\n"${inq.message}"\n\n`);
    document.getElementById('modalEmailBtn').href = `mailto:${inq.email}?subject=${emailSubject}&body=${emailBody}`;

    // Phone & WhatsApp links
    const rawPhone = (inq.phone || '').replace(/[^\d+]/g, '');
    if (rawPhone) {
        document.getElementById('modalPhoneLink').href = 'tel:' + rawPhone;
        document.getElementById('modalPhoneLink').textContent = inq.phone;
        
        const waText = encodeURIComponent(`Hello ${nameStr}, thank you for contacting Reiki Bliss regarding your inquiry: "${(inq.message || '').substring(0, 80)}...". How may we assist you?`);
        document.getElementById('modalWhatsAppBtn').href = `https://wa.me/${rawPhone.replace('+', '')}?text=${waText}`;
        document.getElementById('modalWhatsAppBtn').style.display = 'inline-flex';
    } else {
        document.getElementById('modalPhoneLink').textContent = 'Not provided';
        document.getElementById('modalWhatsAppBtn').style.display = 'none';
    }

    // Full Message and Length
    const msg = inq.message || '';
    document.getElementById('modalFullMessage').textContent = msg;
    const lenEl = document.getElementById('modalMessageLen');
    if (lenEl) {
        lenEl.textContent = msg.length > 0 ? `${msg.length} characters` : '';
    }

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
                modalBadge.className = 'badge badge-read-pill';
                modalBadge.textContent = 'Read';
                const row = document.getElementById('inqRow_' + inq.id);
                if (row) row.classList.remove('row-unread');
                const badge = document.getElementById('statusBadge_' + inq.id);
                if (badge) {
                    badge.className = 'badge badge-read-pill';
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

// Close on backdrop click
const inqModalEl = document.getElementById('inquiryDetailModal');
if (inqModalEl) {
    inqModalEl.addEventListener('click', function(e) {
        if (e.target === this) {
            closeInquiryModal();
        }
    });
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && inqModalEl && inqModalEl.classList.contains('active')) {
        closeInquiryModal();
    }
});

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
