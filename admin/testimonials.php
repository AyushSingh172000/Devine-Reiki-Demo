<?php
// Admin Testimonials CRUD Management
$pageTitle = "Manage Testimonials";

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/upload-helper.php';

$msg = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// Handle Delete Request
if ($action === 'delete' && $editId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/testimonials.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting testimonial: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Testimonial deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Testimonial saved successfully!";
}

// Handle Form Submission (Add / Edit)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $clientName = trim($_POST['client_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $isActive = (int)($_POST['is_active'] ?? 1);
    $currentImage = $_POST['current_image'] ?? '';

    // Handle Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'testimonials');
        if ($newImage) {
            $currentImage = $newImage;
        }
    } catch (Exception $e) {
        $error = "Image upload failed: " . $e->getMessage();
    }

    if (empty($clientName)) {
        $error = "Client name is required.";
    }

    if (empty($content)) {
        $error = "Testimonial content is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE testimonials SET client_name = ?, location = ?, content = ?, rating = ?, image = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$clientName, $location, $content, $rating, $currentImage, $isActive, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO testimonials (client_name, location, content, rating, image, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$clientName, $location, $content, $rating, $currentImage, $isActive]);
            }
            header("Location: " . BASE_URL . "admin/testimonials.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Testimonials
$testimonials = [];
try {
    $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
    $testimonials = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching testimonials: " . $e->getMessage());
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

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- ADD / EDIT FORM VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Testimonial' : 'Add New Testimonial'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/testimonials.php" class="btn-admin btn-admin-secondary">← Back to List</a>
        </div>

        <form action="testimonials.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Client Name *</label>
                    <input type="text" name="client_name" class="form-input" value="<?php echo htmlspecialchars($editItem['client_name'] ?? ''); ?>" required placeholder="e.g. Meera Mehta">
                </div>

                <div class="form-group">
                    <label class="form-label">Location / City</label>
                    <input type="text" name="location" class="form-input" value="<?php echo htmlspecialchars($editItem['location'] ?? ''); ?>" placeholder="e.g. Surat, Gujarat">
                </div>

                <div class="form-group">
                    <label class="form-label">Star Rating (1 - 5)</label>
                    <select name="rating" class="form-select">
                        <option value="5" <?php echo (($editItem['rating'] ?? 5) == 5) ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5 Stars)</option>
                        <option value="4" <?php echo (($editItem['rating'] ?? 5) == 4) ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4 Stars)</option>
                        <option value="3" <?php echo (($editItem['rating'] ?? 5) == 3) ? 'selected' : ''; ?>>⭐⭐⭐ (3 Stars)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?php echo (($editItem['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (($editItem['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Client Photo File</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <?php if (!empty($editItem['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image']); ?>" width="60" height="60" style="object-fit: cover; border-radius: 50%;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Testimonial Review Content *</label>
                    <textarea name="content" class="form-textarea" style="min-height: 120px;" required><?php echo htmlspecialchars($editItem['content'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Testimonial →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- LIST TABLE VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Client Testimonials (<?php echo count($testimonials); ?>)</h2>
            <a href="testimonials.php?action=add" class="btn-admin btn-admin-primary">💬 Add Testimonial</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Client Name</th>
                        <th>Location</th>
                        <th>Rating</th>
                        <th>Review Excerpt</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($testimonials)): ?>
                        <?php foreach ($testimonials as $tst): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($tst['image'] ?: 'assets/images/testimonials/client-1.jpg'); ?>" width="44" height="44" style="object-fit: cover; border-radius: 50%;">
                                </td>
                                <td><strong><?php echo htmlspecialchars($tst['client_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($tst['location']); ?></td>
                                <td><?php echo str_repeat('⭐', $tst['rating']); ?></td>
                                <td style="max-width: 300px; font-size: 0.85rem; color: var(--admin-muted);">
                                    <?php echo htmlspecialchars(substr($tst['content'], 0, 80)) . '...'; ?>
                                </td>
                                <td>
                                    <?php echo ($tst['is_active']) ? '<span class="badge-status badge-completed">Active</span>' : '<span class="badge-status badge-unread">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <a href="testimonials.php?action=edit&id=<?php echo $tst['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                    <a href="testimonials.php?action=delete&id=<?php echo $tst['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this testimonial?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">No testimonials found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
