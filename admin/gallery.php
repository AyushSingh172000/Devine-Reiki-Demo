<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
require_once 'upload-helper.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$categoryFilter = trim($_GET['category'] ?? 'All');
$error = '';

$defaultCategories = ['Sessions', 'Events', 'Center', 'Students', 'Other'];

// Fetch distinct categories from database for filter
$dbCategories = [];
try {
    $catStmt = $pdo->query("SELECT DISTINCT category FROM gallery_images WHERE category IS NOT NULL AND category != ''");
    $dbCategories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (PDOException $e) {
    $dbCategories = [];
}
$allCategories = array_unique(array_merge($defaultCategories, $dbCategories));

// =========================================================================
// 1. HANDLE POST ACTIONS (Delete, Bulk Upload, Edit)
// =========================================================================

// A. DELETE GALLERY IMAGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image_path FROM gallery_images WHERE id = ?");
            $stmt->execute([$deleteId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item && !empty($item['image_path'])) {
                deleteImage($item['image_path']);
            }

            $delStmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Gallery image deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting image: " . $e->getMessage();
        }
    }
    header("Location: gallery.php");
    exit;
}

// B. BATCH / BULK UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_upload'])) {
    if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name'])) {
        $fileCount = count($_FILES['gallery_files']['name']);
        $captions = $_POST['captions'] ?? [];
        $categories = $_POST['categories'] ?? [];
        $uploadedCount = 0;
        $failedCount = 0;

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                $fileItem = [
                    'name'     => $_FILES['gallery_files']['name'][$i],
                    'type'     => $_FILES['gallery_files']['type'][$i],
                    'tmp_name' => $_FILES['gallery_files']['tmp_name'][$i],
                    'error'    => $_FILES['gallery_files']['error'][$i],
                    'size'     => $_FILES['gallery_files']['size'][$i],
                ];

                $uploadRes = uploadImage($fileItem, '../uploads/gallery/');
                if ($uploadRes['success']) {
                    $caption = trim($captions[$i] ?? '');
                    $cat = trim($categories[$i] ?? 'General');
                    if (empty($cat)) {
                        $cat = 'General';
                    }

                    try {
                        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_path, caption, category, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        $insertStmt->execute([$uploadRes['path'], $caption, $cat, 0, 1]);
                        $uploadedCount++;
                    } catch (PDOException $e) {
                        $failedCount++;
                    }
                } else {
                    $failedCount++;
                }
            }
        }

        if ($uploadedCount > 0) {
            $_SESSION['flash_success'] = "Successfully uploaded {$uploadedCount} gallery image(s)!" . ($failedCount > 0 ? " ({$failedCount} failed)" : "");
        } else {
            $_SESSION['flash_error'] = "No images were uploaded. Please ensure valid JPG, PNG, or WEBP files are selected.";
        }
        header("Location: gallery.php");
        exit;
    }
}

// C. SAVE SINGLE EDIT (via Modal or Edit Form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit_image'])) {
    $editImgId = (int)($_POST['id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($editImgId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE gallery_images SET caption = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$caption, $category, $sortOrder, $isActive, $editImgId]);
            $_SESSION['flash_success'] = "Gallery image details updated!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error updating image: " . $e->getMessage();
        }
    }
    header("Location: gallery.php");
    exit;
}

$pageTitle = ($action === 'upload') ? 'Upload Gallery Images' : 'Gallery';

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'upload'): ?>
<!-- =========================================================================
     UPLOAD / BATCH ADD VIEW
     ========================================================================= -->
<div style="max-width: 960px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="gallery.php" class="btn btn-outline btn-sm flex items-center gap-1">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Gallery
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: #ffffff;">
            Batch Upload Gallery Images
        </h2>
    </div>

    <div class="admin-card">
        <form action="gallery.php?action=upload" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
            <input type="hidden" name="batch_upload" value="1">

            <!-- Drag & Drop Zone -->
            <div class="upload-area" id="dropZone" onclick="document.getElementById('galleryFileInput').click()">
                <div class="upload-icon mb-2">
                    <i data-lucide="cloud-upload" style="width: 44px; height: 44px; color: var(--gold);"></i>
                </div>
                <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 6px;">Drop images here or click to browse</h3>
                <p class="text-muted" style="font-size: 0.85rem;">Select multiple photos at once. Supported formats: JPG, PNG, WEBP (Max 5MB each)</p>
            </div>

            <input 
                type="file" 
                id="galleryFileInput" 
                name="gallery_files[]" 
                multiple 
                accept="image/jpeg,image/png,image/webp" 
                style="display: none;"
            >

            <!-- Previews Container with caption and category inputs -->
            <div id="selectedFilesContainer" style="display: none; margin-top: 28px;">
                <div class="flex-between mb-2">
                    <h4 style="font-size: 1rem; color: var(--gold); font-weight: 600;">
                        Selected Images (<span id="selectedCount">0</span>)
                    </h4>
                    <span class="text-muted" style="font-size: 0.8rem;">Set individual caption & category for each image</span>
                </div>

                <div id="batchPreviewList" style="display: flex; flex-direction: column; gap: 14px;"></div>

                <div class="flex gap-2 mt-4" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                    <button type="submit" class="btn btn-gold" id="uploadAllBtn">
                        Upload All Images
                    </button>
                    <a href="gallery.php" class="btn btn-outline">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const fileInput = document.getElementById('galleryFileInput');
const dropZone = document.getElementById('dropZone');
const container = document.getElementById('selectedFilesContainer');
const previewList = document.getElementById('batchPreviewList');
const countSpan = document.getElementById('selectedCount');

const categories = <?= json_encode($allCategories) ?>;

// Drag & drop highlight
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        handleFilesSelected(fileInput.files);
    }
});

fileInput.addEventListener('change', function() {
    handleFilesSelected(this.files);
});

function handleFilesSelected(files) {
    if (!files || files.length === 0) {
        container.style.display = 'none';
        return;
    }

    previewList.innerHTML = '';
    countSpan.textContent = files.length;
    container.style.display = 'block';

    Array.from(files).forEach((file, index) => {
        const itemCard = document.createElement('div');
        itemCard.style.cssText = 'display: flex; align-items: center; gap: 16px; background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 12px;';

        const reader = new FileReader();
        reader.onload = (e) => {
            const catOptions = categories.map(c => `<option value="${c}">${c}</option>`).join('');

            itemCard.innerHTML = `
                <img src="${e.target.result}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px; flex-shrink: 0; border: 1px solid var(--card-border);">
                <div style="flex: 1; display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" style="margin-bottom: 4px; font-size: 0.78rem;">Caption / Description</label>
                        <input type="text" name="captions[]" class="form-control" placeholder="e.g. Healer performing chakra alignment..." style="padding: 8px 12px; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label class="form-label" style="margin-bottom: 4px; font-size: 0.78rem;">Category</label>
                        <select name="categories[]" class="form-control" style="padding: 8px 12px; font-size: 0.85rem;">
                            ${catOptions}
                        </select>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
        previewList.appendChild(itemCard);
    });
}
</script>

<?php else: ?>
<!-- =========================================================================
     LIST VIEW (4-COLUMN CARD GRID)
     ========================================================================= -->
<?php
try {
    $whereParts = [];
    $params = [];

    if (!empty($categoryFilter) && $categoryFilter !== 'All') {
        $whereParts[] = "category = ?";
        $params[] = $categoryFilter;
    }

    $whereClause = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';
    $sql = "SELECT * FROM gallery_images {$whereClause} ORDER BY sort_order ASC, created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $galleryList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $galleryList = [];
    $error = "Error fetching gallery images: " . $e->getMessage();
}
?>

<!-- Top Filter Bar -->
<div class="search-bar flex-between mb-4">
    <form action="gallery.php" method="GET" class="flex gap-2" style="align-items: center;">
        <label class="text-muted" style="font-size: 0.88rem; font-weight: 500;">Filter by Category:</label>
        <select name="category" class="filter-select" onchange="this.form.submit()">
            <option value="All" <?= $categoryFilter === 'All' ? 'selected' : '' ?>>All Categories (<?= count($galleryList) ?>)</option>
            <?php foreach ($allCategories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <a href="gallery.php?action=upload" class="btn btn-gold flex items-center gap-1">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Upload Images
    </a>
</div>

<?php if (empty($galleryList)): ?>
    <div class="admin-card" style="text-align: center; padding: 50px 20px;">
        <div style="margin-bottom: 14px;">
            <i data-lucide="image" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
        </div>
        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 6px;">No gallery photos found</h3>
        <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;">
            <?= $categoryFilter !== 'All' ? 'No photos match the selected category.' : 'Your gallery is empty. Upload high-resolution photos of healing sessions, crystal layouts, and student workshops.' ?>
        </p>
        <a href="gallery.php?action=upload" class="btn btn-gold btn-sm flex items-center gap-1" style="display: inline-flex;">
            <i data-lucide="cloud-upload" style="width: 16px; height: 16px;"></i> Upload Photos Now
        </a>
    </div>
<?php else: ?>
    <!-- 4-Column Responsive Grid -->
    <div class="gallery-admin-grid mb-4">
        <?php foreach ($galleryList as $item): ?>
            <?php 
                $imgSrc = !empty($item['image_path']) 
                    ? (strpos($item['image_path'], 'assets/') === 0 || strpos($item['image_path'], 'uploads/') === 0 ? '../' . $item['image_path'] : $item['image_path']) 
                    : '../assets/images/gallery/session-1.jpg';
                $caption = !empty($item['caption']) ? $item['caption'] : 'No caption';
                $category = !empty($item['category']) ? $item['category'] : 'General';
                $isActive = !empty($item['is_active']);
            ?>
            <div class="gallery-card">
                <!-- Category Badge -->
                <span class="gallery-card-badge">
                    <?= htmlspecialchars($category) ?>
                </span>

                <!-- Image -->
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($caption) ?>" loading="lazy" onerror="this.src='../assets/images/logo.png'">

                <!-- Caption text overlay at bottom -->
                <div class="gallery-card-caption">
                    <?= htmlspecialchars($caption) ?>
                </div>

                <!-- Hover Overlay with Edit & Delete Actions -->
                <div class="gallery-card-overlay">
                    <!-- Edit Button (Opens Modal) -->
                    <button 
                        type="button" 
                        class="btn btn-purple btn-icon" 
                        title="Edit Image Details"
                        onclick='openEditGalleryModal(<?= json_encode($item) ?>, "<?= htmlspecialchars($imgSrc) ?>")'
                    >
                        <i data-lucide="pencil" style="width: 16px; height: 16px;"></i>
                    </button>

                    <!-- Delete Button Form -->
                    <form action="gallery.php" method="POST" class="delete-form" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-icon" title="Delete Image">
                            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- EDIT GALLERY MODAL -->
<div class="modal-overlay" id="galleryEditModal">
    <div class="modal" style="max-width: 540px;">
        <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2">
                <i data-lucide="pencil" style="width: 18px; height: 18px; color: var(--gold);"></i> Edit Gallery Image
            </h3>
            <button type="button" class="modal-close flex items-center justify-center" onclick="closeEditGalleryModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <form action="gallery.php" method="POST" id="galleryEditForm">
            <input type="hidden" name="save_edit_image" value="1">
            <input type="hidden" name="id" id="modalImgId" value="0">

            <div style="text-align: center; margin-bottom: 20px;">
                <img id="modalImgPreview" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 10px; border: 1px solid var(--card-border); object-fit: contain;">
            </div>

            <div class="form-group">
                <label for="modalCaption" class="form-label">Image Caption</label>
                <input type="text" id="modalCaption" name="caption" class="form-control" placeholder="Describe the photo...">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="modalCategory" class="form-label">Category</label>
                    <select id="modalCategory" name="category" class="form-control">
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modalSortOrder" class="form-label">Sort Order</label>
                    <input type="number" id="modalSortOrder" name="sort_order" class="form-control" placeholder="0">
                </div>
            </div>

            <div class="form-group">
                <label class="switch-toggle-label">
                    <input type="checkbox" id="modalIsActive" name="is_active" value="1">
                    <span>Active (Visible in public website gallery)</span>
                </label>
            </div>

            <div class="flex-between mt-3" style="border-top: 1px solid var(--card-border); padding-top: 16px;">
                <button type="submit" class="btn btn-gold">
                    Save Changes
                </button>
                <button type="button" class="btn btn-outline" onclick="closeEditGalleryModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditGalleryModal(item, imgSrc) {
    document.getElementById('modalImgId').value = item.id;
    document.getElementById('modalImgPreview').src = imgSrc;
    document.getElementById('modalCaption').value = item.caption || '';
    document.getElementById('modalCategory').value = item.category || 'General';
    document.getElementById('modalSortOrder').value = item.sort_order || 0;
    document.getElementById('modalIsActive').checked = (item.is_active == 1);

    document.getElementById('galleryEditModal').classList.add('active');
    if (window.lucide) lucide.createIcons();
}

function closeEditGalleryModal() {
    document.getElementById('galleryEditModal').classList.remove('active');
}
</script>

<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
