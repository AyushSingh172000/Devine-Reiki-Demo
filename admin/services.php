<?php
// Admin Services CRUD Management
$pageTitle = "Manage Services";

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
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/services.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting service: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Service deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Service saved successfully!";
}

// Handle Form Submission (Add / Edit)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = generateSlug($title);
    }
    $shortDesc = trim($_POST['short_description'] ?? '');
    $fullDesc = trim($_POST['full_description'] ?? '');
    $price = ($_POST['is_free'] ?? '0') === '1' ? null : (float)($_POST['price'] ?? 0);
    $duration = (int)($_POST['duration_minutes'] ?? 60);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isFree = (int)($_POST['is_free'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 1);
    $currentImage = $_POST['current_image'] ?? '';

    // Handle Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'services');
        if ($newImage) {
            $currentImage = $newImage;
        }
    } catch (Exception $e) {
        $error = "Image upload failed: " . $e->getMessage();
    }

    if (empty($title)) {
        $error = "Service title is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE services SET title = ?, slug = ?, short_description = ?, full_description = ?, price = ?, duration_minutes = ?, image = ?, sort_order = ?, is_free = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $price, $duration, $currentImage, $sortOrder, $isFree, $isActive, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO services (title, slug, short_description, full_description, price, duration_minutes, image, sort_order, is_free, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $price, $duration, $currentImage, $sortOrder, $isFree, $isActive]);
            }
            header("Location: " . BASE_URL . "admin/services.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Services for Table View
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, created_at DESC");
    $services = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
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
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Service' : 'Add New Service'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/services.php" class="btn-admin btn-admin-secondary">← Back to Services List</a>
        </div>

        <form action="services.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Service Title *</label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required placeholder="e.g. Usui Reiki Healing Session">
                </div>

                <div class="form-group">
                    <label class="form-label">URL Slug (Leave blank to auto-generate)</label>
                    <input type="text" name="slug" class="form-input" value="<?php echo htmlspecialchars($editItem['slug'] ?? ''); ?>" placeholder="reiki-healing-session">
                </div>

                <div class="form-group">
                    <label class="form-label">Price (INR) — Leave empty if free</label>
                    <input type="number" step="0.01" name="price" class="form-input" value="<?php echo htmlspecialchars($editItem['price'] ?? ''); ?>" placeholder="1500.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Duration (Minutes)</label>
                    <input type="number" name="duration_minutes" class="form-input" value="<?php echo htmlspecialchars($editItem['duration_minutes'] ?? 60); ?>" placeholder="60">
                </div>

                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-input" value="<?php echo htmlspecialchars($editItem['sort_order'] ?? 1); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Service Image File</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <?php if (!empty($editItem['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image']); ?>" width="80" height="60" style="object-fit: cover; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Is Free Session?</label>
                    <select name="is_free" class="form-select">
                        <option value="0" <?php echo (($editItem['is_free'] ?? 0) == 0) ? 'selected' : ''; ?>>No (Paid Service)</option>
                        <option value="1" <?php echo (($editItem['is_free'] ?? 0) == 1) ? 'selected' : ''; ?>>Yes (Free Session)</option>
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
                    <label class="form-label">Short Description *</label>
                    <textarea name="short_description" class="form-textarea" style="min-height: 80px;" required><?php echo htmlspecialchars($editItem['short_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Full Detailed Description (HTML or Paragraphs)</label>
                    <textarea name="full_description" class="form-textarea" style="min-height: 160px;"><?php echo htmlspecialchars($editItem['full_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Service →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- LIST TABLE VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">All Healing Services (<?php echo count($services); ?>)</h2>
            <a href="services.php?action=add" class="btn-admin btn-admin-primary">➕ Add New Service</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $srv): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($srv['image'] ?: 'assets/images/services/reiki-healing.jpg'); ?>" width="54" height="42" style="object-fit: cover; border-radius: 8px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($srv['title']); ?></strong>
                                    <span style="display: block; font-size: 0.78rem; color: var(--admin-muted);"><?php echo htmlspecialchars($srv['slug']); ?></span>
                                </td>
                                <td>
                                    <?php echo ($srv['is_free'] || $srv['price'] === null) ? '<span class="badge-status badge-completed">Free</span>' : '₹' . number_format($srv['price'], 2); ?>
                                </td>
                                <td><?php echo $srv['duration_minutes']; ?> mins</td>
                                <td><?php echo $srv['sort_order']; ?></td>
                                <td>
                                    <?php echo ($srv['is_active']) ? '<span class="badge-status badge-completed">Active</span>' : '<span class="badge-status badge-unread">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <a href="services.php?action=edit&id=<?php echo $srv['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                    <a href="services.php?action=delete&id=<?php echo $srv['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">No services found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
