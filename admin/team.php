<?php
// Admin Team Members CRUD Management
$pageTitle = "Manage Team Members";

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
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/team.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting team member: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Team member deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Team member saved successfully!";
}

// Handle Form Submission (Add / Edit)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? 'teacher');
    $title = trim($_POST['title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $rawSpecialties = trim($_POST['specialties'] ?? '');

    // Convert comma-separated string to JSON array
    $specArr = array_map('trim', explode(',', $rawSpecialties));
    $specArr = array_values(array_filter($specArr));
    $specJson = json_encode($specArr);

    $sortOrder = (int)($_POST['sort_order'] ?? 1);
    $isActive = (int)($_POST['is_active'] ?? 1);
    $currentImage = $_POST['current_image'] ?? '';

    // Handle Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'team');
        if ($newImage) {
            $currentImage = $newImage;
        }
    } catch (Exception $e) {
        $error = "Image upload failed: " . $e->getMessage();
    }

    if (empty($name)) {
        $error = "Practitioner name is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE team_members SET name = ?, role = ?, title = ?, bio = ?, specialties = ?, image = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $role, $title, $bio, $specJson, $currentImage, $sortOrder, $isActive, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO team_members (name, role, title, bio, specialties, image, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $role, $title, $bio, $specJson, $currentImage, $sortOrder, $isActive]);
            }
            header("Location: " . BASE_URL . "admin/team.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Team Members
$team = [];
try {
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC, created_at DESC");
    $team = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching team: " . $e->getMessage());
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
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Team Member' : 'Add Team Member'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/team.php" class="btn-admin btn-admin-secondary">← Back to Team List</a>
        </div>

        <form action="team.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Practitioner Name *</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($editItem['name'] ?? ''); ?>" required placeholder="e.g. Dr. Chirag Gajjar">
                </div>

                <div class="form-group">
                    <label class="form-label">Role Category</label>
                    <select name="role" class="form-select">
                        <option value="founder" <?php echo (($editItem['role'] ?? '') === 'founder') ? 'selected' : ''; ?>>Founder / Main Practitioner</option>
                        <option value="teacher" <?php echo (($editItem['role'] ?? '') === 'teacher') ? 'selected' : ''; ?>>Reiki Teacher &amp; Healer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Title / Designation</label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" placeholder="e.g. Reiki Grand Master &amp; Energy Alignment Specialist">
                </div>

                <div class="form-group">
                    <label class="form-label">Specialties (Comma-separated)</label>
                    <?php 
                    $existingSpecs = is_string($editItem['specialties'] ?? null) ? json_decode($editItem['specialties'], true) : ($editItem['specialties'] ?? []);
                    $specsStr = is_array($existingSpecs) ? implode(', ', $existingSpecs) : '';
                    ?>
                    <input type="text" name="specialties" class="form-input" value="<?php echo htmlspecialchars($specsStr); ?>" placeholder="Usui Reiki, Chakra Balancing, Distance Healing">
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

                <div class="form-group full-width">
                    <label class="form-label">Profile Photo File</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <?php if (!empty($editItem['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image']); ?>" width="80" height="80" style="object-fit: cover; border-radius: 50%;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Biography / Background Description</label>
                    <textarea name="bio" class="form-textarea" style="min-height: 140px;"><?php echo htmlspecialchars($editItem['bio'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Practitioner →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- LIST TABLE VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Healing Team Practitioners (<?php echo count($team); ?>)</h2>
            <a href="team.php?action=add" class="btn-admin btn-admin-primary">👥 Add Practitioner</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Role / Title</th>
                        <th>Specialties</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($team)): ?>
                        <?php foreach ($team as $mem): ?>
                            <?php 
                            $specs = is_string($mem['specialties']) ? json_decode($mem['specialties'], true) : $mem['specialties'];
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($mem['image'] ?: 'assets/images/team/dr-chirag-gajjar.jpg'); ?>" width="48" height="48" style="object-fit: cover; border-radius: 50%;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($mem['name']); ?></strong>
                                    <span style="display: block; font-size: 0.76rem; text-transform: uppercase; color: var(--admin-gold-dark); font-weight: 700;"><?php echo htmlspecialchars($mem['role']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($mem['title']); ?></td>
                                <td style="font-size: 0.82rem;">
                                    <?php echo is_array($specs) ? htmlspecialchars(implode(', ', $specs)) : '-'; ?>
                                </td>
                                <td><?php echo $mem['sort_order']; ?></td>
                                <td>
                                    <?php echo ($mem['is_active']) ? '<span class="badge-status badge-completed">Active</span>' : '<span class="badge-status badge-unread">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <a href="team.php?action=edit&id=<?php echo $mem['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                    <a href="team.php?action=delete&id=<?php echo $mem['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this practitioner?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">No team members found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
