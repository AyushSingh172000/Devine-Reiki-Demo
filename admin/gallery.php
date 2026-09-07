<?php
// Admin Gallery CRUD Management
$pageTitle = "Manage Gallery Images";

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
        $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/gallery.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting image: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Gallery image deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Gallery image saved successfully!";
}

// Handle Form Submission (Add / Edit)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $caption = trim($_POST['caption'] ?? '');
    $category = trim($_POST['category'] ?? 'Sessions');
    $sortOrder = (int)($_POST['sort_order'] ?? 1);
    $isActive = (int)($_POST['is_active'] ?? 1);
    $currentImagePath = $_POST['current_image_path'] ?? '';

    // Handle Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'gallery');
        if ($newImage) {
            $currentImagePath = $newImage;
        }
    } catch (Exception $e) {
        $error = "Image upload failed: " . $e->getMessage();
    }

    if (empty($currentImagePath)) {
        $error = "Please upload an image file.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE gallery_images SET image_path = ?, caption = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$currentImagePath, $caption, $category, $sortOrder, $isActive, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO gallery_images (image_path, caption, category, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$currentImagePath, $caption, $category, $sortOrder, $isActive]);
            }
            header("Location: " . BASE_URL . "admin/gallery.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Gallery Images
$gallery = [];
try {
    $stmt = $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, created_at DESC");
    $gallery = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching gallery: " . $e->getMessage());
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
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Gallery Photo' : 'Upload Gallery Photo'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/gallery.php" class="btn-admin btn-admin-secondary">← Back to Gallery</a>
        </div>

        <form action="gallery.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($editItem['image_path'] ?? ''); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Caption / Title</label>
                    <input type="text" name="caption" class="form-input" value="<?php echo htmlspecialchars($editItem['caption'] ?? ''); ?>" placeholder="e.g. Reiki Master Attunement Workshop">
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="Sessions" <?php echo (($editItem['category'] ?? '') === 'Sessions') ? 'selected' : ''; ?>>Sessions</option>
                        <option value="Events" <?php echo (($editItem['category'] ?? '') === 'Events') ? 'selected' : ''; ?>>Events</option>
                        <option value="Center" <?php echo (($editItem['category'] ?? '') === 'Center') ? 'selected' : ''; ?>>Center</option>
                        <option value="Students" <?php echo (($editItem['category'] ?? '') === 'Students') ? 'selected' : ''; ?>>Students</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Image File *</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*" <?php echo ($editId === 0) ? 'required' : ''; ?>>
                    <?php if (!empty($editItem['image_path'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image_path']); ?>" width="100" height="70" style="object-fit: cover; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-input" value="<?php echo htmlspecialchars($editItem['sort_order'] ?? 1); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?php echo (($editItem['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (($editItem['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Image →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- GALLERY GRID VIEW WITH DELETION -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Sanctuary Gallery Images (<?php echo count($gallery); ?>)</h2>
            <a href="gallery.php?action=add" class="btn-admin btn-admin-primary">📷 Upload Photo</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">
            <?php if (!empty($gallery)): ?>
                <?php foreach ($gallery as $img): ?>
                    <div style="background: var(--admin-bg); border-radius: 14px; overflow: hidden; border: 1px solid var(--admin-border); position: relative;">
                        <img src="<?php echo BASE_URL . htmlspecialchars($img['image_path']); ?>" style="width: 100%; height: 160px; object-fit: cover; display: block;">
                        <div style="padding: 14px;">
                            <span style="font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--admin-gold-dark); font-weight: 700; display: block; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($img['category']); ?> (Order: <?php echo $img['sort_order']; ?>)
                            </span>
                            <strong style="font-size: 0.9rem; display: block; color: var(--admin-text); margin-bottom: 12px; height: 38px; overflow: hidden;">
                                <?php echo htmlspecialchars($img['caption']); ?>
                            </strong>
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="gallery.php?action=edit&id=<?php echo $img['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                <a href="gallery.php?action=delete&id=<?php echo $img['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this photo?');">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--admin-muted); padding: 30px;">No gallery images found.</p>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
