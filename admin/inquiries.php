<?php
// Admin Contact Inquiries Management
$pageTitle = "Contact Inquiries";

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

$msg = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$inqId = (int)($_GET['id'] ?? 0);

// Handle Mark as Read / Unread
if ($action === 'toggle_read' && $inqId > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 - is_read WHERE id = ?");
        $stmt->execute([$inqId]);
        header("Location: " . BASE_URL . "admin/inquiries.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle Delete Request
if ($action === 'delete' && $inqId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id = ?");
        $stmt->execute([$inqId]);
        header("Location: " . BASE_URL . "admin/inquiries.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting inquiry: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Inquiry deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $msg = "Inquiry status updated!";
}

// Fetch single inquiry for viewing
$viewInquiry = null;
if ($inqId > 0 && ($action === 'view' || !empty($_GET['id']))) {
    $stmt = $pdo->prepare("SELECT * FROM contact_inquiries WHERE id = ?");
    $stmt->execute([$inqId]);
    $viewInquiry = $stmt->fetch(PDO::FETCH_ASSOC);

    // Auto-mark as read when viewed
    if ($viewInquiry && !$viewInquiry['is_read']) {
        $upd = $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id = ?");
        $upd->execute([$inqId]);
        $viewInquiry['is_read'] = 1;
    }
}

// Fetch All Inquiries
$inquiries = [];
try {
    $stmt = $pdo->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC");
    $inquiries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching inquiries: " . $e->getMessage());
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

<?php if ($viewInquiry): ?>
    <!-- VIEW INQUIRY DETAILS PANEL -->
    <div class="admin-card" style="margin-bottom: 32px;">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Inquiry Details #<?php echo $viewInquiry['id']; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/inquiries.php" class="btn-admin btn-admin-secondary">← Back to All Inquiries</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; background: var(--admin-bg); padding: 20px; border-radius: 14px;">
            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Sender Name</span>
                <strong style="display: block; font-size: 1.1rem; color: var(--admin-purple);"><?php echo htmlspecialchars($viewInquiry['name']); ?></strong>
            </div>

            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Email Address</span>
                <a href="mailto:<?php echo htmlspecialchars($viewInquiry['email']); ?>" style="display: block; font-size: 0.95rem; color: var(--admin-text); font-weight: 500; text-decoration: none;">
                    <?php echo htmlspecialchars($viewInquiry['email']); ?>
                </a>
            </div>

            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 600;">Phone / WhatsApp</span>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $viewInquiry['phone']); ?>" target="_blank" rel="noopener" style="display: block; font-size: 0.95rem; color: #16a34a; font-weight: 600; text-decoration: none;">
                    💬 <?php echo htmlspecialchars($viewInquiry['phone']); ?>
                </a>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="font-size: 0.84rem; text-transform: uppercase; color: var(--admin-muted); font-weight: 700; display: block; margin-bottom: 8px;">Message / Inquiry Content:</label>
            <div style="background: var(--admin-bg); padding: 20px; border-radius: 14px; font-size: 1rem; line-height: 1.7; white-space: pre-wrap; color: var(--admin-text);">
                <?php echo htmlspecialchars($viewInquiry['message']); ?>
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <a href="inquiries.php?action=toggle_read&id=<?php echo $viewInquiry['id']; ?>" class="btn-admin btn-admin-secondary">
                Mark as <?php echo ($viewInquiry['is_read']) ? 'Unread' : 'Read'; ?>
            </a>
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $viewInquiry['phone']); ?>?text=Hello%20<?php echo urlencode($viewInquiry['name']); ?>%20regarding%20your%20inquiry..." target="_blank" rel="noopener" class="btn-admin" style="background-color: #25D366; color: #fff;">
                💬 Reply on WhatsApp
            </a>
            <a href="inquiries.php?action=delete&id=<?php echo $viewInquiry['id']; ?>" class="btn-admin" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Delete this inquiry?');">
                Delete
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- INQUIRIES LIST TABLE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">All Contact Inquiries (<?php echo count($inquiries); ?>)</h2>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message Snippet</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inquiries)): ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr style="<?php echo (!$inq['is_read']) ? 'font-weight: 600; background-color: #fefce8;' : ''; ?>">
                            <td style="white-space: nowrap; color: var(--admin-muted); font-size: 0.82rem;">
                                <?php echo date('M j, Y g:i A', strtotime($inq['created_at'])); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($inq['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($inq['email']); ?></td>
                            <td><?php echo htmlspecialchars($inq['phone']); ?></td>
                            <td style="max-width: 250px; font-size: 0.84rem; color: var(--admin-muted);">
                                <?php echo htmlspecialchars(substr($inq['message'], 0, 70)) . '...'; ?>
                            </td>
                            <td>
                                <?php if ($inq['is_read']): ?>
                                    <span class="badge-status badge-read">Read</span>
                                <?php else: ?>
                                    <span class="badge-status badge-unread">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="inquiries.php?id=<?php echo $inq['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">View</a>
                                <a href="inquiries.php?action=toggle_read&id=<?php echo $inq['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
                                    <?php echo ($inq['is_read']) ? 'Unread' : 'Mark Read'; ?>
                                </a>
                                <a href="inquiries.php?action=delete&id=<?php echo $inq['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Delete this inquiry?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">No contact inquiries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
