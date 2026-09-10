<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
require_once 'upload-helper.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$error = '';

// =========================================================================
// 1. HANDLE POST ACTIONS (Delete, Insert, Update)
// =========================================================================

// A. DELETE SERVICE (POST method for safety)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
            $stmt->execute([$deleteId]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($service && !empty($service['image'])) {
                deleteImage($service['image']);
            }

            $delStmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Service deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting service: " . $e->getMessage();
        }
    }
    header("Location: services.php");
    exit;
}

// B. SAVE SERVICE (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = generateSlug($title);
    } else {
        $slug = generateSlug($slug);
    }

    $shortDesc = trim($_POST['short_description'] ?? '');
    $fullDesc = trim($_POST['full_description'] ?? '');
    $isFree = isset($_POST['is_free']) ? 1 : 0;
    $price = $isFree ? null : (is_numeric($_POST['price'] ?? null) ? (float)$_POST['price'] : null);
    $duration = (int)($_POST['duration_minutes'] ?? 60);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? '';

    // Remove current image if requested
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($currentImage)) {
            deleteImage($currentImage);
            $currentImage = null;
        }
    }

    // Handle New Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = uploadImage($_FILES['image'], '../uploads/services/');
        if ($uploadRes['success']) {
            if (!empty($currentImage) && $currentImage !== $uploadRes['path']) {
                deleteImage($currentImage);
            }
            $currentImage = $uploadRes['path'];
        } else {
            $error = "Image upload error: " . $uploadRes['error'];
        }
    }

    if (empty($title)) {
        $error = "Please provide a valid service title.";
    }

    // Verify slug uniqueness
    if (empty($error)) {
        $checkSlug = $pdo->prepare("SELECT id FROM services WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $editId]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . time();
        }
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // UPDATE
                $updateSql = "UPDATE services SET 
                    title = ?, 
                    slug = ?, 
                    short_description = ?, 
                    full_description = ?, 
                    price = ?, 
                    duration_minutes = ?, 
                    image = ?, 
                    sort_order = ?, 
                    is_free = ?, 
                    is_active = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $price, $duration, $currentImage, $sortOrder, $isFree, $isActive, $editId]);
                $_SESSION['flash_success'] = "Service updated successfully!";
            } else {
                // INSERT
                $insertSql = "INSERT INTO services 
                    (title, slug, short_description, full_description, price, duration_minutes, image, sort_order, is_free, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $price, $duration, $currentImage, $sortOrder, $isFree, $isActive]);
                $_SESSION['flash_success'] = "New service created successfully!";
            }
            header("Location: services.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error saving service: " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. FETCH DATA DEPENDING ON VIEW
// =========================================================================

$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editItem && $action === 'edit') {
        $_SESSION['flash_error'] = "Service not found.";
        header("Location: services.php");
        exit;
    }
}

// Set Page Title
if ($action === 'add') {
    $pageTitle = 'Add Service';
} elseif ($action === 'edit') {
    $pageTitle = 'Edit Service';
} else {
    $pageTitle = 'Services';
}

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- =========================================================================
     ADD / EDIT VIEW
     ========================================================================= -->
<div style="max-width: 860px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="services.php" class="btn btn-outline btn-sm flex items-center gap-1">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Services
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: #ffffff;">
            <?= $action === 'edit' ? 'Edit Service' : 'Create New Service' ?>
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error flex items-center gap-2">
            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="services.php?action=<?= $action ?>&id=<?= $editId ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_service" value="1">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>">

            <div class="form-row">
                <!-- Title -->
                <div class="form-group">
                    <label for="serviceTitle" class="form-label">Service Title <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="serviceTitle" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g., Reiki Healing Session"
                        value="<?= htmlspecialchars($_POST['title'] ?? ($editItem['title'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="serviceSlug" class="form-label">URL Slug</label>
                    <input 
                        type="text" 
                        id="serviceSlug" 
                        name="slug" 
                        class="form-control" 
                        placeholder="reiki-healing-session"
                        value="<?= htmlspecialchars($_POST['slug'] ?? ($editItem['slug'] ?? '')) ?>"
                    >
                    <div class="form-hint">Auto-generated from title, or enter custom URL identifier.</div>
                </div>
            </div>

            <!-- Short Description -->
            <div class="form-group">
                <div class="flex-between">
                    <label for="shortDesc" class="form-label">Short Description</label>
                    <span class="char-counter-text" id="shortDescCounter">0 / 200</span>
                </div>
                <textarea 
                    id="shortDesc" 
                    name="short_description" 
                    class="form-control" 
                    rows="3" 
                    maxlength="200"
                    placeholder="Brief overview summarizing the healing benefits (max 200 chars)..."
                ><?= htmlspecialchars($_POST['short_description'] ?? ($editItem['short_description'] ?? '')) ?></textarea>
            </div>

            <!-- Full Description with Toolbar -->
            <div class="form-group">
                <label for="fullDesc" class="form-label">Full Description (HTML Supported)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertTag('fullDesc', '<b>', '</b>')" title="Bold"><i data-lucide="bold" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('fullDesc', '<i>', '</i>')" title="Italic"><i data-lucide="italic" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('fullDesc', '<h3>', '</h3>')" title="Heading 3"><i data-lucide="heading-3" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('fullDesc', '<ul>\n  <li>', '</li>\n</ul>')" title="List"><i data-lucide="list" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('fullDesc', '<p>', '</p>')" title="Paragraph"><i data-lucide="pilcrow" style="width: 14px; height: 14px;"></i></button>
                </div>
                <textarea 
                    id="fullDesc" 
                    name="full_description" 
                    class="form-control has-toolbar" 
                    style="min-height: 180px;"
                    placeholder="Comprehensive session details, what to expect, chakras addressed, etc..."
                ><?= htmlspecialchars($_POST['full_description'] ?? ($editItem['full_description'] ?? '')) ?></textarea>
            </div>

            <div class="form-row">
                <!-- Price & Is Free -->
                <div class="form-group">
                    <div class="flex-between mb-1">
                        <label for="servicePrice" class="form-label" style="margin-bottom: 0;">Price (₹)</label>
                        <label class="switch-toggle-label" style="font-size: 0.82rem;">
                            <input 
                                type="checkbox" 
                                id="isFreeCheckbox" 
                                name="is_free" 
                                value="1" 
                                <?= (isset($_POST['is_free']) || (!empty($editItem['is_free']))) ? 'checked' : '' ?>
                            >
                            <span class="text-gold">Mark as Free Session</span>
                        </label>
                    </div>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="servicePrice" 
                        name="price" 
                        class="form-control" 
                        placeholder="e.g. 1500.00"
                        value="<?= htmlspecialchars($_POST['price'] ?? ($editItem['price'] ?? '')) ?>"
                    >
                </div>

                <!-- Duration -->
                <div class="form-group">
                    <label for="serviceDuration" class="form-label">Duration (Minutes)</label>
                    <input 
                        type="number" 
                        id="serviceDuration" 
                        name="duration_minutes" 
                        class="form-control" 
                        placeholder="60"
                        value="<?= htmlspecialchars($_POST['duration_minutes'] ?? ($editItem['duration_minutes'] ?? '60')) ?>"
                    >
                </div>
            </div>

            <!-- Image Upload Section -->
            <div class="form-group">
                <label class="form-label">Service Featured Image</label>
                
                <div class="upload-area" onclick="document.getElementById('serviceImgInput').click()">
                    <div class="upload-icon mb-1">
                        <i data-lucide="sparkles" style="width: 36px; height: 36px; color: var(--gold);"></i>
                    </div>
                    <p style="font-weight: 500; margin-bottom: 4px;">Click or drag an image here to upload</p>
                    <p class="text-muted" style="font-size: 0.8rem;">Supported formats: JPG, PNG, WEBP (Max 5MB)</p>
                </div>
                
                <input 
                    type="file" 
                    id="serviceImgInput" 
                    name="image" 
                    class="image-upload-input" 
                    accept="image/jpeg,image/png,image/webp" 
                    style="display: none;"
                >

                <!-- Upload Preview / Existing Image -->
                <div class="upload-preview" style="<?= !empty($editItem['image']) ? 'display: inline-block;' : 'display: none;' ?>">
                    <?php if (!empty($editItem['image'])): ?>
                        <?php 
                            $imgSrc = strpos($editItem['image'], 'assets/') === 0 || strpos($editItem['image'], 'uploads/') === 0
                                ? '../' . $editItem['image'] 
                                : $editItem['image'];
                        ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Service Image Preview">
                        <button type="button" class="remove-preview flex items-center justify-center" onclick="removePreview(this)" title="Remove image">
                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($editItem['image'])): ?>
                    <label class="switch-toggle-label mt-2" style="font-size: 0.82rem; color: var(--error);">
                        <input type="checkbox" name="remove_image" value="1">
                        Remove current image completely
                    </label>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <!-- Sort Order -->
                <div class="form-group">
                    <label for="sortOrder" class="form-label">Sort Order</label>
                    <input 
                        type="number" 
                        id="sortOrder" 
                        name="sort_order" 
                        class="form-control" 
                        placeholder="0"
                        value="<?= htmlspecialchars($_POST['sort_order'] ?? ($editItem['sort_order'] ?? '0')) ?>"
                    >
                    <div class="form-hint">Lower numbers appear first on the website.</div>
                </div>

                <!-- Is Active -->
                <div class="form-group" style="display: flex; flex-direction: column; justify-content: center;">
                    <label class="form-label">Status Visibility</label>
                    <label class="switch-toggle-label" style="margin-top: 6px;">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            value="1" 
                            <?= (!isset($editItem) || !empty($editItem['is_active'])) ? 'checked' : '' ?>
                        >
                        <span>Active (Publicly visible on website)</span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-2 mt-3" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                <button type="submit" class="btn btn-gold">
                    Save Service
                </button>
                <a href="services.php" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Auto Slug Generation
const titleInput = document.getElementById('serviceTitle');
const slugInput = document.getElementById('serviceSlug');
let userTouchedSlug = <?= ($action === 'edit') ? 'true' : 'false' ?>;

if (slugInput) {
    slugInput.addEventListener('input', () => { userTouchedSlug = true; });
}
if (titleInput && slugInput) {
    titleInput.addEventListener('input', function() {
        if (!userTouchedSlug || slugInput.value === '') {
            slugInput.value = this.value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
}

// Character Counter for Short Description
const shortDesc = document.getElementById('shortDesc');
const shortDescCounter = document.getElementById('shortDescCounter');
if (shortDesc && shortDescCounter) {
    const updateCounter = () => {
        shortDescCounter.textContent = `${shortDesc.value.length} / 200`;
    };
    shortDesc.addEventListener('input', updateCounter);
    updateCounter();
}

// Is Free toggle handling
const isFreeCheckbox = document.getElementById('isFreeCheckbox');
const servicePrice = document.getElementById('servicePrice');
if (isFreeCheckbox && servicePrice) {
    const togglePrice = () => {
        if (isFreeCheckbox.checked) {
            servicePrice.value = '';
            servicePrice.setAttribute('disabled', 'disabled');
            servicePrice.placeholder = 'Free (No charge)';
        } else {
            servicePrice.removeAttribute('disabled');
            servicePrice.placeholder = 'e.g. 1500.00';
        }
    };
    isFreeCheckbox.addEventListener('change', togglePrice);
    togglePrice();
}

// Toolbar tag insertion helper
function insertTag(textareaId, openTag, closeTag) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selected = text.substring(start, end);
    const replacement = openTag + selected + closeTag;
    textarea.value = text.substring(0, start) + replacement + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + openTag.length, end + openTag.length);
}
</script>

<?php else: ?>
<!-- =========================================================================
     LIST VIEW (DEFAULT)
     ========================================================================= -->
<?php
// Query Services with optional Search filter
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE title LIKE ? OR short_description LIKE ? ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, created_at DESC");
    }
    $servicesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $servicesList = [];
    $error = "Error fetching services: " . $e->getMessage();
}
?>

<!-- Search Bar & Action Header -->
<div class="search-bar flex-between mb-3">
    <form action="services.php" method="GET" class="search-input-wrapper" style="max-width: 400px;">
        <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search services by title..." 
            value="<?= htmlspecialchars($search) ?>"
        >
        <?php if (!empty($search)): ?>
            <a href="services.php" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 2px;">
                <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
            </a>
        <?php endif; ?>
    </form>

    <a href="services.php?action=add" class="btn btn-gold flex items-center gap-1">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add New Service
    </a>
</div>

<div class="admin-card">
    <?php if (empty($servicesList)): ?>
        <div style="text-align: center; padding: 40px 20px;">
            <div style="margin-bottom: 12px;">
                <i data-lucide="sparkles" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
            </div>
            <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 6px;">No services found</h3>
            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 18px;">
                <?= !empty($search) ? 'No results matched your search query.' : 'You have not added any healing services yet.' ?>
            </p>
            <a href="services.php?action=add" class="btn btn-gold btn-sm flex items-center gap-1" style="display: inline-flex;">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add First Service
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Free?</th>
                        <th>Status</th>
                        <th style="width: 80px; text-align: center;">Order</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicesList as $srv): ?>
                        <?php 
                            $thumb = !empty($srv['image']) 
                                ? (strpos($srv['image'], 'assets/') === 0 || strpos($srv['image'], 'uploads/') === 0 ? '../' . $srv['image'] : $srv['image']) 
                                : '../assets/images/services/reiki-healing.jpg';
                            $isFree = !empty($srv['is_free']);
                            $isActive = !empty($srv['is_active']);
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($srv['title']) ?>" class="thumbnail-50" onerror="this.src='../assets/images/logo.png'">
                            </td>
                            <td>
                                <strong style="color: #ffffff;"><?= htmlspecialchars($srv['title']) ?></strong>
                                <div class="text-muted" style="font-size: 0.78rem;"><?= htmlspecialchars($srv['slug']) ?></div>
                            </td>
                            <td>
                                <?php if ($isFree): ?>
                                    <span class="badge badge-success">Free</span>
                                <?php else: ?>
                                    <strong class="text-gold">₹<?= number_format((float)($srv['price'] ?? 0), 2) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted">
                                <?= (int)$srv['duration_minutes'] ?> mins
                            </td>
                            <td>
                                <?php if ($isFree): ?>
                                    <span class="badge badge-info">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-outline text-muted" style="border: 1px solid var(--card-border);">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                <?= (int)$srv['sort_order'] ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <a href="services.php?action=edit&id=<?= $srv['id'] ?>" class="btn btn-purple btn-sm flex items-center gap-1" title="Edit Service">
                                        <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit
                                    </a>
                                    <form action="services.php" method="POST" class="delete-form" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm flex items-center gap-1" title="Delete Service">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
