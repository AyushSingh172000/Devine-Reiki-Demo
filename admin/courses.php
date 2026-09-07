<?php
// Admin Courses CRUD Management
$pageTitle = "Manage Courses";

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
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/courses.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting course: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Course deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Course saved successfully!";
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
    $priceText = trim($_POST['price_text'] ?? 'Contact for price');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 1);
    $currentImage = $_POST['current_image'] ?? '';

    // Handle Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'courses');
        if ($newImage) {
            $currentImage = $newImage;
        }
    } catch (Exception $e) {
        $error = "Image upload failed: " . $e->getMessage();
    }

    if (empty($title)) {
        $error = "Course title is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE courses SET title = ?, slug = ?, short_description = ?, full_description = ?, price_text = ?, image = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $priceText, $currentImage, $sortOrder, $isActive, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO courses (title, slug, short_description, full_description, price_text, image, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $priceText, $currentImage, $sortOrder, $isActive]);
            }
            header("Location: " . BASE_URL . "admin/courses.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Courses for Table View
$courses = [];
try {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY sort_order ASC, created_at DESC");
    $courses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
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
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Course' : 'Add New Course'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/courses.php" class="btn-admin btn-admin-secondary">← Back to Courses List</a>
        </div>

        <form action="courses.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Course Title *</label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required placeholder="e.g. Reiki Level 1 First Degree">
                </div>

                <div class="form-group">
                    <label class="form-label">URL Slug (Leave blank to auto-generate)</label>
                    <input type="text" name="slug" class="form-input" value="<?php echo htmlspecialchars($editItem['slug'] ?? ''); ?>" placeholder="reiki-level-1">
                </div>

                <div class="form-group">
                    <label class="form-label">Price Text (e.g. "₹4,999" or "Contact for price")</label>
                    <input type="text" name="price_text" class="form-input" value="<?php echo htmlspecialchars($editItem['price_text'] ?? 'Contact for price'); ?>" placeholder="₹4,999">
                </div>

                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-input" value="<?php echo htmlspecialchars($editItem['sort_order'] ?? 1); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Course Image File</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <?php if (!empty($editItem['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image']); ?>" width="80" height="60" style="object-fit: cover; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
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
                    <label class="form-label">Full Course Curriculum / Description</label>
                    <textarea name="full_description" class="form-textarea" style="min-height: 160px;"><?php echo htmlspecialchars($editItem['full_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Course →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- LIST TABLE VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">All Certification Courses (<?php echo count($courses); ?>)</h2>
            <a href="courses.php?action=add" class="btn-admin btn-admin-primary">➕ Add New Course</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price Text</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $crs): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($crs['image'] ?: 'assets/images/courses/reiki-level-1.jpg'); ?>" width="54" height="42" style="object-fit: cover; border-radius: 8px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($crs['title']); ?></strong>
                                    <span style="display: block; font-size: 0.78rem; color: var(--admin-muted);"><?php echo htmlspecialchars($crs['slug']); ?></span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($crs['price_text']); ?></strong></td>
                                <td><?php echo $crs['sort_order']; ?></td>
                                <td>
                                    <?php echo ($crs['is_active']) ? '<span class="badge-status badge-completed">Active</span>' : '<span class="badge-status badge-unread">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <a href="courses.php?action=edit&id=<?php echo $crs['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                    <a href="courses.php?action=delete&id=<?php echo $crs['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--admin-muted); padding: 30px;">No courses found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
