<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Services Management & Rates Governance';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$successMsg = $_SESSION['sa_flash_success'] ?? '';
$errorMsg = $_SESSION['sa_flash_error'] ?? '';
unset($_SESSION['sa_flash_success'], $_SESSION['sa_flash_error']);

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$deleteId]);
            $_SESSION['sa_flash_success'] = "Service #{$deleteId} successfully removed!";
        } catch (\PDOException $e) {
            $_SESSION['sa_flash_error'] = "Error deleting service: " . $e->getMessage();
        }
    }
    header("Location: services.php");
    exit;
}

// Handle Save Action (Insert or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    $shortDesc = trim($_POST['short_description'] ?? '');
    $fullDesc = trim($_POST['full_description'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 21);
    $mode = trim($_POST['mode'] ?? 'Online');
    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $price = $isFree ? 0.00 : (is_numeric($_POST['price'] ?? null) ? (float)$_POST['price'] : 0.00);
    $imagePath = trim($_POST['image'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Handle File Upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/services/';
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFileName = 'service-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newFileName)) {
                $imagePath = 'assets/images/services/' . $newFileName;
            }
        }
    }

    if (empty($title)) {
        $errorMsg = "Service title cannot be empty.";
    } else {
        try {
            if ($editId > 0) {
                // UPDATE
                $upd = $pdo->prepare("UPDATE services SET 
                    title = ?, slug = ?, short_description = ?, full_description = ?, 
                    duration_minutes = ?, mode = ?, price = ?, is_free = ?, 
                    image = ?, sort_order = ?, is_active = ?, updated_at = NOW() 
                    WHERE id = ?");
                $upd->execute([
                    $title, $slug, $shortDesc, $fullDesc,
                    $duration, $mode, $price, $isFree,
                    $imagePath, $sortOrder, $isActive, $editId
                ]);
                $_SESSION['sa_flash_success'] = "Service '{$title}' updated successfully!";
            } else {
                // INSERT
                $ins = $pdo->prepare("INSERT INTO services 
                    (title, slug, short_description, full_description, duration_minutes, mode, price, is_free, image, sort_order, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $ins->execute([
                    $title, $slug, $shortDesc, $fullDesc,
                    $duration, $mode, $price, $isFree,
                    $imagePath, $sortOrder, $isActive
                ]);
                $_SESSION['sa_flash_success'] = "New service '{$title}' added successfully!";
            }
            header("Location: services.php");
            exit;
        } catch (\PDOException $e) {
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch edit item if editing
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all services for list view
$servicesList = [];
if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
    $servicesList = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

include __DIR__ . '/includes/superadmin-header.php';
?>

<?php if (!empty($successMsg)): ?>
    <div class="sa-alert sa-alert-success">
        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div><?= htmlspecialchars($successMsg) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="sa-alert sa-alert-error">
        <i data-lucide="alert-octagon" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div><?= htmlspecialchars($errorMsg) ?></div>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- ADD / EDIT FORM VIEW -->
    <div class="sa-card" style="max-width: 900px; margin: 0 auto;">
        <div class="sa-card-header">
            <div>
                <h2 class="sa-card-title">
                    <i data-lucide="<?= $action === 'edit' ? 'edit-3' : 'plus-circle' ?>" style="color: var(--sa-gold);"></i>
                    <?= $action === 'edit' ? 'Edit Healing Service #' . $editId : 'Add New Healing Service' ?>
                </h2>
                <p class="sa-card-desc">Configure title, description, rate (energy exchange), duration, mode, and image preview.</p>
            </div>
            <a href="services.php" class="sa-btn sa-btn-outline" style="font-size: 0.85rem; padding: 8px 14px;">
                <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i> Back to Services
            </a>
        </div>

        <form method="POST" action="services.php?action=<?= $action ?>&id=<?= $editId ?>" enctype="multipart/form-data">
            <input type="hidden" name="save_service" value="1">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 6px;">
                        Service Title *
                    </label>
                    <input type="text" name="title" class="sa-input" value="<?= htmlspecialchars($_POST['title'] ?? ($editItem['title'] ?? '')) ?>" placeholder="e.g. Reiki Session for Evil Eye Removal" required>
                </div>

                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; margin-bottom: 6px;">
                        URL Slug
                    </label>
                    <input type="text" name="slug" class="sa-input" value="<?= htmlspecialchars($_POST['slug'] ?? ($editItem['slug'] ?? '')) ?>" placeholder="reiki-session-evil-eye-removal">
                </div>
            </div>

            <!-- Short Description -->
            <div style="margin-bottom: 16px;">
                <label class="form-label" style="display: flex; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 6px;">
                    <span>Short Description (Shown on Cards)</span>
                    <span style="font-size: 0.7rem; color: var(--sa-text-dim);">Max 400 characters</span>
                </label>
                <textarea name="short_description" class="sa-input" rows="3" maxlength="400" placeholder="Cleanse and protect your energy field from the effects of negative intentions and evil eye (Nazar)."><?= htmlspecialchars($_POST['short_description'] ?? ($editItem['short_description'] ?? '')) ?></textarea>
            </div>

            <!-- Full Description -->
            <div style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; margin-bottom: 6px;">
                    Full Detail Description (HTML / Paragraphs)
                </label>
                <textarea name="full_description" class="sa-input" rows="4" placeholder="Detailed description for service details page..."><?= htmlspecialchars($_POST['full_description'] ?? ($editItem['full_description'] ?? '')) ?></textarea>
            </div>

            <!-- Rate, Duration, Mode Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 18px; padding: 14px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--sa-border);">
                <!-- Energy Exchange Rate -->
                <div>
                    <label class="form-label" style="display: flex; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 6px;">
                        <span>Rate (₹)</span>
                        <label style="font-size: 0.72rem; color: var(--sa-gold); cursor: pointer;">
                            <input type="checkbox" name="is_free" value="1" <?= (isset($_POST['is_free']) || (!empty($editItem['is_free']))) ? 'checked' : '' ?>> Free
                        </label>
                    </label>
                    <input type="number" step="0.01" name="price" class="sa-input" value="<?= htmlspecialchars($_POST['price'] ?? ($editItem['price'] ?? '500.00')) ?>" placeholder="500.00">
                </div>

                <!-- Duration -->
                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 6px;">
                        Duration (Minutes)
                    </label>
                    <input type="number" name="duration_minutes" class="sa-input" value="<?= htmlspecialchars($_POST['duration_minutes'] ?? ($editItem['duration_minutes'] ?? '21')) ?>" placeholder="21">
                </div>

                <!-- Mode -->
                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 6px;">
                        Mode
                    </label>
                    <?php $currentMode = $_POST['mode'] ?? ($editItem['mode'] ?? 'Online'); ?>
                    <select name="mode" class="sa-input">
                        <option value="Online" <?= ($currentMode === 'Online') ? 'selected' : '' ?>>Online</option>
                        <option value="In-Person" <?= ($currentMode === 'In-Person') ? 'selected' : '' ?>>In-Person</option>
                        <option value="Online & In-Person" <?= ($currentMode === 'Online & In-Person') ? 'selected' : '' ?>>Online &amp; In-Person</option>
                        <option value="Distance Healing" <?= ($currentMode === 'Distance Healing') ? 'selected' : '' ?>>Distance Healing</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; margin-bottom: 6px;">
                        Sort Order
                    </label>
                    <input type="number" name="sort_order" class="sa-input" value="<?= htmlspecialchars($_POST['sort_order'] ?? ($editItem['sort_order'] ?? '0')) ?>" placeholder="0">
                </div>
            </div>

            <!-- Image Selection / Upload -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; margin-bottom: 6px;">
                        Image Path / Preset
                    </label>
                    <?php $activeImg = $_POST['image'] ?? ($editItem['image'] ?? 'assets/images/services/reiki-healing.jpg'); ?>
                    <input type="text" name="image" class="sa-input" value="<?= htmlspecialchars($activeImg) ?>" placeholder="assets/images/services/evil-eye-removal.jpg">
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Live Preview:</span>
                        <img src="../<?= htmlspecialchars($activeImg) ?>" alt="Service Preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid var(--sa-border-gold);" onerror="this.src='../assets/images/services/reiki-healing.jpg'">
                    </div>
                </div>

                <div>
                    <label class="form-label" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; margin-bottom: 6px;">
                        Or Upload New Image
                    </label>
                    <input type="file" name="image_file" class="sa-input" accept="image/*">
                </div>
            </div>

            <!-- Active Status -->
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding: 12px; background: rgba(0,0,0,0.15); border-radius: 6px;">
                <label class="sa-switch">
                    <input type="checkbox" name="is_active" value="1" <?= (!isset($editItem) || !empty($editItem['is_active'])) ? 'checked' : '' ?>>
                    <span class="sa-slider"></span>
                </label>
                <div>
                    <strong style="font-size: 0.86rem; color: #fff;">Active &amp; Published on Website</strong>
                    <div style="font-size: 0.74rem; color: var(--sa-text-dim);">Toggle off to temporarily hide this service from the live website without deleting it.</div>
                </div>
            </div>

            <!-- Submit -->
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <a href="services.php" class="sa-btn sa-btn-outline">Cancel</a>
                <button type="submit" class="sa-btn sa-btn-gold">
                    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                    <?= $action === 'edit' ? 'Save Service Changes' : 'Create Healing Service' ?>
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- LIST VIEW -->
    <div class="sa-card">
        <div class="sa-card-header">
            <div>
                <h2 class="sa-card-title">
                    <i data-lucide="sparkles" style="color: var(--sa-gold);"></i>
                    Healing Services Management (10 Active Modalities)
                </h2>
                <p class="sa-card-desc">
                    Control services descriptions, energy exchange rates (₹), session duration, and delivery mode (Online/In-Person) across the website.
                </p>
            </div>

            <div style="display: flex; gap: 10px;">
                <a href="../services.php" target="_blank" class="sa-btn sa-btn-outline" style="font-size: 0.85rem; padding: 8px 14px;">
                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> View Services Page
                </a>
                <a href="services.php?action=add" class="sa-btn sa-btn-gold" style="font-size: 0.85rem; padding: 8px 14px;">
                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Add New Service
                </a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="sa-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--sa-border); text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--sa-text-dim);">
                        <th style="padding: 12px 14px; width: 60px;">Image</th>
                        <th style="padding: 12px 14px; min-width: 220px;">Title &amp; Modality</th>
                        <th style="padding: 12px 14px; min-width: 260px;">Description</th>
                        <th style="padding: 12px 14px; width: 110px;">Rate (₹)</th>
                        <th style="padding: 12px 14px; width: 95px;">Duration</th>
                        <th style="padding: 12px 14px; width: 100px;">Mode</th>
                        <th style="padding: 12px 14px; width: 90px;">Status</th>
                        <th style="padding: 12px 14px; width: 110px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicesList as $srv): 
                        $hasImage = !empty($srv['image']);
                        $thumb = $hasImage ? '../' . ltrim($srv['image'], '/') : '';
                        $isFree = !empty($srv['is_free']);
                    ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.86rem;">
                            <td style="padding: 12px 14px;">
                                <?php if ($hasImage): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($srv['title']) ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid var(--sa-border-gold);" onerror="this.src='../assets/images/logo.png'">
                                <?php else: ?>
                                    <div style="width: 44px; height: 44px; border-radius: 6px; background: rgba(255,255,255,0.04); border: 1px dashed var(--sa-border-gold); display: inline-flex; align-items: center; justify-content: center; color: var(--sa-text-muted);" title="No image">
                                        <i data-lucide="image-off" style="width: 16px; height: 16px; opacity: 0.6;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <strong style="color: #ffffff;"><?= htmlspecialchars($srv['title']) ?></strong>
                                <div style="font-size: 0.72rem; color: var(--sa-cyan); font-family: monospace;"><?= htmlspecialchars($srv['slug']) ?></div>
                            </td>
                            <td style="padding: 12px 14px; color: var(--sa-text-muted); font-size: 0.82rem; line-height: 1.5;">
                                <?= htmlspecialchars($srv['short_description']) ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <?php if ($isFree || $srv['price'] == 0): ?>
                                    <span style="color: #4ade80; font-weight: 700;">Free</span>
                                <?php else: ?>
                                    <strong style="color: var(--sa-gold); font-size: 0.95rem;">₹<?= number_format((float)$srv['price'], 0) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; color: #fff;">
                                <?= (int)$srv['duration_minutes'] ?> mins
                            </td>
                            <td style="padding: 12px 14px;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.74rem; background: rgba(200, 155, 60, 0.14); color: var(--sa-gold); border: 1px solid var(--sa-border-gold);">
                                    <?= htmlspecialchars($srv['mode'] ?? 'Online') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px;">
                                <?php if (!empty($srv['is_active'])): ?>
                                    <span style="color: #4ade80; font-size: 0.75rem; font-weight: 600;">● Active</span>
                                <?php else: ?>
                                    <span style="color: var(--sa-text-dim); font-size: 0.75rem;">○ Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="services.php?action=edit&id=<?= $srv['id'] ?>" class="sa-btn sa-btn-outline" style="padding: 5px 8px; font-size: 0.75rem;" title="Edit Service">
                                        <i data-lucide="edit-2" style="width: 13px; height: 13px;"></i>
                                    </a>
                                    <form method="POST" action="services.php" onsubmit="return confirm('Are you sure you want to delete this service?');" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                        <button type="submit" class="sa-btn" style="padding: 5px 8px; font-size: 0.75rem; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);" title="Delete Service">
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
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
