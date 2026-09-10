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

// A. DELETE TESTIMONIAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM testimonials WHERE id = ?");
            $stmt->execute([$deleteId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item && !empty($item['image'])) {
                deleteImage($item['image']);
            }

            $delStmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Testimonial deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting testimonial: " . $e->getMessage();
        }
    }
    header("Location: testimonials.php");
    exit;
}

// B. SAVE TESTIMONIAL (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    $clientName = trim($_POST['client_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? '';

    // Remove photo if requested
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($currentImage)) {
            deleteImage($currentImage);
            $currentImage = null;
        }
    }

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = uploadImage($_FILES['image'], '../uploads/testimonials/');
        if ($uploadRes['success']) {
            if (!empty($currentImage) && $currentImage !== $uploadRes['path']) {
                deleteImage($currentImage);
            }
            $currentImage = $uploadRes['path'];
        } else {
            $error = "Photo upload error: " . $uploadRes['error'];
        }
    }

    if (empty($clientName)) {
        $error = "Client name is required.";
    }
    if (empty($content)) {
        $error = "Testimonial quote content is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // UPDATE
                $updateSql = "UPDATE testimonials SET client_name = ?, location = ?, content = ?, rating = ?, image = ?, is_active = ? WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([$clientName, $location, $content, $rating, $currentImage, $isActive, $editId]);
                $_SESSION['flash_success'] = "Testimonial updated successfully!";
            } else {
                // INSERT
                $insertSql = "INSERT INTO testimonials (client_name, location, content, rating, image, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([$clientName, $location, $content, $rating, $currentImage, $isActive]);
                $_SESSION['flash_success'] = "New testimonial added successfully!";
            }
            header("Location: testimonials.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error saving testimonial: " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. FETCH DATA DEPENDING ON VIEW
// =========================================================================

$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editItem && $action === 'edit') {
        $_SESSION['flash_error'] = "Testimonial not found.";
        header("Location: testimonials.php");
        exit;
    }
}

$pageTitle = ($action === 'add') ? 'Add Testimonial' : (($action === 'edit') ? 'Edit Testimonial' : 'Testimonials');

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- =========================================================================
     ADD / EDIT VIEW
     ========================================================================= -->
<div style="max-width: 800px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="testimonials.php" class="btn btn-outline btn-sm flex items-center gap-1">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Testimonials
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: #ffffff;">
            <?= $action === 'edit' ? 'Edit Testimonial' : 'Add Client Testimonial' ?>
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error flex items-center gap-2">
            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="testimonials.php?action=<?= $action ?>&id=<?= $editId ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_testimonial" value="1">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>">
            <input type="hidden" name="rating" id="ratingValueInput" value="<?= (int)($editItem['rating'] ?? 5) ?>">

            <div class="form-row">
                <!-- Client Name -->
                <div class="form-group">
                    <label for="clientName" class="form-label">Client Name <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="clientName" 
                        name="client_name" 
                        class="form-control" 
                        placeholder="e.g. Meera Patel" 
                        value="<?= htmlspecialchars($_POST['client_name'] ?? ($editItem['client_name'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Location -->
                <div class="form-group">
                    <label for="clientLocation" class="form-label">Client Location / City</label>
                    <input 
                        type="text" 
                        id="clientLocation" 
                        name="location" 
                        class="form-control" 
                        placeholder="e.g. Surat, Gujarat" 
                        value="<?= htmlspecialchars($_POST['location'] ?? ($editItem['location'] ?? '')) ?>"
                    >
                </div>
            </div>

            <!-- Star Rating Clickable Selector -->
            <div class="form-group">
                <label class="form-label">Client Star Rating (Click to set rating)</label>
                <div class="star-rating-container" id="starRatingWidget">
                    <?php $curRating = (int)($editItem['rating'] ?? 5); ?>
                    <span class="star-item <?= $curRating >= 1 ? 'active' : '' ?>" data-val="1">★</span>
                    <span class="star-item <?= $curRating >= 2 ? 'active' : '' ?>" data-val="2">★</span>
                    <span class="star-item <?= $curRating >= 3 ? 'active' : '' ?>" data-val="3">★</span>
                    <span class="star-item <?= $curRating >= 4 ? 'active' : '' ?>" data-val="4">★</span>
                    <span class="star-item <?= $curRating >= 5 ? 'active' : '' ?>" data-val="5">★</span>
                    <span id="ratingDisplayLabel" style="font-size: 0.9rem; font-weight: 600; color: var(--gold); margin-left: 8px;">
                        <?= $curRating ?> / 5 Stars
                    </span>
                </div>
            </div>

            <!-- Testimonial Content -->
            <div class="form-group">
                <label for="testimonialContent" class="form-label">Testimonial Quote / Review <span class="text-danger">*</span></label>
                <textarea 
                    id="testimonialContent" 
                    name="content" 
                    class="form-control" 
                    rows="4" 
                    placeholder="Share client's genuine feedback and transformation experience..."
                    required
                ><?= htmlspecialchars($_POST['content'] ?? ($editItem['content'] ?? '')) ?></textarea>
            </div>

            <!-- Client Photo Upload -->
            <div class="form-group">
                <label class="form-label">Client Photo (Optional — default avatar used if blank)</label>
                
                <div class="upload-area" onclick="document.getElementById('clientPhotoInput').click()" style="padding: 24px;">
                    <div class="upload-icon mb-1">
                        <i data-lucide="user" style="width: 32px; height: 32px; color: var(--gold);"></i>
                    </div>
                    <p style="font-weight: 500; font-size: 0.9rem; margin-bottom: 2px;">Click to select client profile photo</p>
                    <p class="text-muted" style="font-size: 0.78rem;">JPG, PNG, WEBP (Max 5MB)</p>
                </div>
                
                <input 
                    type="file" 
                    id="clientPhotoInput" 
                    name="image" 
                    class="image-upload-input" 
                    accept="image/jpeg,image/png,image/webp" 
                    style="display: none;"
                >

                <div class="upload-preview" style="<?= !empty($editItem['image']) ? 'display: inline-block;' : 'display: none;' ?>">
                    <?php if (!empty($editItem['image'])): ?>
                        <?php 
                            $imgSrc = strpos($editItem['image'], 'assets/') === 0 || strpos($editItem['image'], 'uploads/') === 0
                                ? '../' . $editItem['image'] 
                                : $editItem['image'];
                        ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Client Photo Preview">
                        <button type="button" class="remove-preview flex items-center justify-center" onclick="removePreview(this)" title="Remove photo">
                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($editItem['image'])): ?>
                    <label class="switch-toggle-label mt-2" style="font-size: 0.82rem; color: var(--error);">
                        <input type="checkbox" name="remove_image" value="1">
                        Remove client photo
                    </label>
                <?php endif; ?>
            </div>

            <!-- Is Active Toggle -->
            <div class="form-group">
                <label class="form-label">Visibility Status</label>
                <label class="switch-toggle-label">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1" 
                        <?= (!isset($editItem) || !empty($editItem['is_active'])) ? 'checked' : '' ?>
                    >
                    <span>Active (Display on website testimonials slider)</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mt-3" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                <button type="submit" class="btn btn-gold">
                    Save Testimonial
                </button>
                <a href="testimonials.php" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Interactive 5-Star Rating Selector
const starWidget = document.getElementById('starRatingWidget');
const ratingInput = document.getElementById('ratingValueInput');
const ratingLabel = document.getElementById('ratingDisplayLabel');
const stars = starWidget.querySelectorAll('.star-item');

function highlightStars(val) {
    stars.forEach(s => {
        const sVal = parseInt(s.getAttribute('data-val'));
        if (sVal <= val) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
    ratingLabel.textContent = `${val} / 5 Stars`;
}

stars.forEach(star => {
    star.addEventListener('click', function() {
        const val = parseInt(this.getAttribute('data-val'));
        ratingInput.value = val;
        highlightStars(val);
    });

    star.addEventListener('mouseenter', function() {
        const val = parseInt(this.getAttribute('data-val'));
        highlightStars(val);
    });
});

starWidget.addEventListener('mouseleave', function() {
    const currentVal = parseInt(ratingInput.value) || 5;
    highlightStars(currentVal);
});
</script>

<?php else: ?>
<!-- =========================================================================
     LIST VIEW (DEFAULT)
     ========================================================================= -->
<?php
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE client_name LIKE ? OR location LIKE ? OR content LIKE ? ORDER BY created_at DESC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
    }
    $testimonialsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonialsList = [];
    $error = "Error fetching testimonials: " . $e->getMessage();
}
?>

<!-- Search & Action Bar -->
<div class="search-bar flex-between mb-3">
    <form action="testimonials.php" method="GET" class="search-input-wrapper" style="max-width: 380px;">
        <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search testimonials by client or city..." 
            value="<?= htmlspecialchars($search) ?>"
        >
        <?php if (!empty($search)): ?>
            <a href="testimonials.php" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 2px;">
                <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
            </a>
        <?php endif; ?>
    </form>

    <a href="testimonials.php?action=add" class="btn btn-gold flex items-center gap-1">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add Testimonial
    </a>
</div>

<div class="admin-card">
    <?php if (empty($testimonialsList)): ?>
        <div style="text-align: center; padding: 40px 20px;">
            <div style="margin-bottom: 12px;">
                <i data-lucide="message-square-quote" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
            </div>
            <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 6px;">No testimonials found</h3>
            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 18px;">
                <?= !empty($search) ? 'No reviews matched your search.' : 'You have not added any client testimonials yet.' ?>
            </p>
            <a href="testimonials.php?action=add" class="btn btn-gold btn-sm flex items-center gap-1" style="display: inline-flex;">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add First Testimonial
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Client</th>
                        <th>Name & Location</th>
                        <th style="width: 130px;">Rating</th>
                        <th>Review Quote</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testimonialsList as $t): ?>
                        <?php 
                            $hasImg = !empty($t['image']);
                            $thumb = $hasImg 
                                ? (strpos($t['image'], 'assets/') === 0 || strpos($t['image'], 'uploads/') === 0 ? '../' . $t['image'] : $t['image']) 
                                : null;
                            $ratingNum = (int)($t['rating'] ?? 5);
                            $starsHtml = str_repeat('★', $ratingNum) . str_repeat('☆', 5 - $ratingNum);
                            $contentExcerpt = mb_strlen($t['content']) > 75 
                                ? mb_substr($t['content'], 0, 72) . '...' 
                                : $t['content'];
                            $isActive = !empty($t['is_active']);
                        ?>
                        <tr>
                            <td>
                                <?php if ($hasImg): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($t['client_name']) ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--gold);">
                                <?php else: ?>
                                    <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--purple-accent), var(--card-bg)); border: 1.5px solid var(--gold); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--gold); font-size: 1rem;">
                                        <?= strtoupper(substr($t['client_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #ffffff;"><?= htmlspecialchars($t['client_name']) ?></strong>
                                <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($t['location'] ?: 'Verified Healee') ?></div>
                            </td>
                            <td>
                                <span class="text-gold" style="font-size: 1.05rem; letter-spacing: 2px;">
                                    <?= $starsHtml ?>
                                </span>
                            </td>
                            <td style="font-size: 0.88rem; color: #e2e8f0;">
                                &ldquo;<?= htmlspecialchars($contentExcerpt) ?>&rdquo;
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <a href="testimonials.php?action=edit&id=<?= $t['id'] ?>" class="btn btn-purple btn-sm flex items-center gap-1" title="Edit Testimonial">
                                        <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit
                                    </a>
                                    <form action="testimonials.php" method="POST" class="delete-form" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm flex items-center gap-1" title="Delete Testimonial">
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
