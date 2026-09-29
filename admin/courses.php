<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
require_once 'upload-helper.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$error = '';

// Auto-ensure extended course columns exist (duration, mode, energy_exchange, language, certificate, course_material)
try {
    $existingCols = [];
    $colStmt = $pdo->query("DESCRIBE courses");
    while ($r = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCols[] = $r['Field'];
    }
    $extraCols = [
        'duration' => 'VARCHAR(100) NULL',
        'mode' => 'VARCHAR(100) NULL',
        'energy_exchange' => 'VARCHAR(100) NULL',
        'language' => 'VARCHAR(100) NULL',
        'certificate' => 'VARCHAR(100) NULL',
        'course_material' => 'VARCHAR(100) NULL'
    ];
    foreach ($extraCols as $colName => $colDef) {
        if (!in_array($colName, $existingCols)) {
            $pdo->exec("ALTER TABLE courses ADD COLUMN {$colName} {$colDef}");
        }
    }
} catch (Exception $e) {
    // Columns already created
}

// =========================================================================
// 1. HANDLE POST ACTIONS (Delete, Insert, Update)
// =========================================================================

// A. DELETE COURSE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM courses WHERE id = ?");
            $stmt->execute([$deleteId]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($course && !empty($course['image'])) {
                deleteImage($course['image']);
            }

            $delStmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Course deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting course: " . $e->getMessage();
        }
    }
    header("Location: courses.php");
    exit;
}

// B. SAVE COURSE (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = generateSlug($title);
    } else {
        $slug = generateSlug($slug);
    }

    $shortDesc = trim($_POST['short_description'] ?? '');
    $fullDesc = trim($_POST['full_description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $mode = trim($_POST['mode'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $certificate = trim($_POST['certificate'] ?? '');
    $courseMaterial = trim($_POST['course_material'] ?? '');

    $priceText = trim($_POST['price_text'] ?? '');
    if (empty($priceText)) {
        $priceText = 'Contact for price';
    }
    // Sync energy_exchange with price_text so both stay fully aligned
    $energyExchange = $priceText;

    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? '';

    // Remove image if requested or cleared
    if ((isset($_POST['remove_image']) && $_POST['remove_image'] == '1') || empty($currentImage)) {
        if (!empty($currentImage)) {
            deleteImage($currentImage);
        }
        $currentImage = null;
    }

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = uploadImage($_FILES['image'], '../uploads/courses/');
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
        $error = "Course title is required.";
    }

    // Ensure slug uniqueness
    if (empty($error)) {
        $checkSlug = $pdo->prepare("SELECT id FROM courses WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $editId]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . time();
        }
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // UPDATE
                $updateSql = "UPDATE courses SET 
                    title = ?, 
                    slug = ?, 
                    short_description = ?, 
                    full_description = ?, 
                    price_text = ?, 
                    duration = ?,
                    mode = ?,
                    energy_exchange = ?,
                    language = ?,
                    certificate = ?,
                    course_material = ?,
                    image = ?, 
                    sort_order = ?, 
                    is_active = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([
                    $title, $slug, $shortDesc, $fullDesc, $priceText,
                    $duration, $mode, $energyExchange, $language, $certificate, $courseMaterial,
                    $currentImage, $sortOrder, $isActive, $editId
                ]);
                $_SESSION['flash_success'] = "Course updated successfully!";
            } else {
                // INSERT
                $insertSql = "INSERT INTO courses 
                    (title, slug, short_description, full_description, price_text, duration, mode, energy_exchange, language, certificate, course_material, image, sort_order, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    $title, $slug, $shortDesc, $fullDesc, $priceText,
                    $duration, $mode, $energyExchange, $language, $certificate, $courseMaterial,
                    $currentImage, $sortOrder, $isActive
                ]);
                $_SESSION['flash_success'] = "New course added successfully!";
            }
            header("Location: courses.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error saving course: " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. FETCH DATA DEPENDING ON VIEW
// =========================================================================

$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editItem && $action === 'edit') {
        $_SESSION['flash_error'] = "Course not found.";
        header("Location: courses.php");
        exit;
    }
}

// Set Page Title
if ($action === 'add') {
    $pageTitle = 'Add Course';
} elseif ($action === 'edit') {
    $pageTitle = 'Edit Course';
} else {
    $pageTitle = 'Courses';
}

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- =========================================================================
     ADD / EDIT VIEW
     ========================================================================= -->
<div style="max-width: 860px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="courses.php" class="btn btn-outline btn-sm flex items-center gap-1">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Courses
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--text-primary);">
            <?= $action === 'edit' ? 'Edit Course' : 'Create New Course' ?>
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error flex items-center gap-2">
            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="courses.php?action=<?= $action ?>&id=<?= $editId ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_course" value="1">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>">

            <div class="form-row">
                <!-- Title -->
                <div class="form-group">
                    <label for="courseTitle" class="form-label">Course Title <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="courseTitle" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g. Usui Reiki Level 1 & 2 Certification" 
                        value="<?= htmlspecialchars($_POST['title'] ?? ($editItem['title'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="courseSlug" class="form-label">URL Slug</label>
                    <input 
                        type="text" 
                        id="courseSlug" 
                        name="slug" 
                        class="form-control" 
                        placeholder="usui-reiki-level-1-2"
                        value="<?= htmlspecialchars($_POST['slug'] ?? ($editItem['slug'] ?? '')) ?>"
                    >
                    <div class="form-hint">Auto-generated from title or customize for clean URLs.</div>
                </div>
            </div>

            <!-- Short Description -->
            <div class="form-group">
                <div class="flex-between">
                    <label for="courseShortDesc" class="form-label">Short Description</label>
                    <span class="char-counter-text" id="courseShortCounter">0 / 200</span>
                </div>
                <textarea 
                    id="courseShortDesc" 
                    name="short_description" 
                    class="form-control" 
                    rows="3" 
                    maxlength="200"
                    placeholder="Brief highlights of what students will master..."
                ><?= htmlspecialchars($_POST['short_description'] ?? ($editItem['short_description'] ?? '')) ?></textarea>
            </div>

            <!-- Full Description with Toolbar -->
            <div class="form-group">
                <label for="courseFullDesc" class="form-label">Full Description (HTML Supported)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertTag('courseFullDesc', '<b>', '</b>')" title="Bold"><i data-lucide="bold" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('courseFullDesc', '<i>', '</i>')" title="Italic"><i data-lucide="italic" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('courseFullDesc', '<h3>', '</h3>')" title="Heading 3"><i data-lucide="heading-3" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('courseFullDesc', '<ul>\n  <li>', '</li>\n</ul>')" title="List"><i data-lucide="list" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('courseFullDesc', '<p>', '</p>')" title="Paragraph"><i data-lucide="pilcrow" style="width: 14px; height: 14px;"></i></button>
                </div>
                <textarea 
                    id="courseFullDesc" 
                    name="full_description" 
                    class="form-control has-toolbar" 
                    style="min-height: 180px;"
                    placeholder="Curriculum modules, attunements, certificate details, duration, prerequisite..."
                ><?= htmlspecialchars($_POST['full_description'] ?? ($editItem['full_description'] ?? '')) ?></textarea>
            </div>

            <!-- Key Course Details & Specifications (Duration, Mode, Energy Exchange, Language, Certificate, Course Material) -->
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--card-border, #EAE5DB); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 16px 0; font-size: 0.96rem; font-weight: 600; color: var(--gold, #C9A84C); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                    Course Highlights & Specifications
                </h4>

                <div class="form-row">
                    <!-- Duration -->
                    <div class="form-group">
                        <label for="courseDuration" class="form-label">Duration</label>
                        <input 
                            type="text" 
                            id="courseDuration" 
                            name="duration" 
                            class="form-control" 
                            placeholder="e.g. 9 hours"
                            value="<?= htmlspecialchars($_POST['duration'] ?? ($editItem['duration'] ?? '')) ?>"
                        >
                        <div class="form-hint">e.g. 9 hours, 2 Days, Self-paced</div>
                    </div>

                    <!-- Mode -->
                    <div class="form-group">
                        <label for="courseMode" class="form-label">Mode</label>
                        <input 
                            type="text" 
                            id="courseMode" 
                            name="mode" 
                            class="form-control" 
                            placeholder="e.g. Online"
                            value="<?= htmlspecialchars($_POST['mode'] ?? ($editItem['mode'] ?? '')) ?>"
                        >
                        <div class="form-hint">e.g. Online, Offline, Hybrid</div>
                    </div>

                    <!-- Language -->
                    <div class="form-group">
                        <label for="courseLanguage" class="form-label">Language</label>
                        <input 
                            type="text" 
                            id="courseLanguage" 
                            name="language" 
                            class="form-control" 
                            placeholder="e.g. Hindi"
                            value="<?= htmlspecialchars($_POST['language'] ?? ($editItem['language'] ?? '')) ?>"
                        >
                        <div class="form-hint">e.g. Hindi, English</div>
                    </div>
                    <!-- Course Material -->
                    <div class="form-group">
                        <label for="courseMaterial" class="form-label">Course Material</label>
                        <input 
                            type="text" 
                            id="courseMaterial" 
                            name="course_material" 
                            class="form-control" 
                            placeholder="e.g. Included"
                            value="<?= htmlspecialchars($_POST['course_material'] ?? ($editItem['course_material'] ?? '')) ?>"
                        >
                        <div class="form-hint">e.g. Included, PDF Manual & Audio</div>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 14px;">
                    <!-- Certificate -->
                    <div class="form-group">
                        <label for="courseCertificate" class="form-label">Certificate</label>
                        <input 
                            type="text" 
                            id="courseCertificate" 
                            name="certificate" 
                            class="form-control" 
                            placeholder="e.g. Included"
                            value="<?= htmlspecialchars($_POST['certificate'] ?? ($editItem['certificate'] ?? '')) ?>"
                        >
                        <div class="form-hint">e.g. Included, ISO Certified</div>
                    </div>

                    
                </div>
            </div>

            <div class="form-row">
                <!-- Energy Exchange -->
                <div class="form-group">
                    <label for="priceText" class="form-label">Energy Exchange</label>
                    <input 
                        type="text" 
                        id="priceText" 
                        name="price_text" 
                        class="form-control" 
                        placeholder="e.g. Rs. 5,100 or ₹5,000"
                        value="<?= htmlspecialchars($_POST['price_text'] ?? ($editItem['price_text'] ?? ($editItem['energy_exchange'] ?? 'Contact for price'))) ?>"
                    >
                    <div class="form-hint">Fee / Energy exchange displayed on the course card and detail page.</div>
                </div>

                <!-- Sort Order -->
                <div class="form-group">
                    <label for="courseSortOrder" class="form-label">Sort Order</label>
                    <input 
                        type="number" 
                        id="courseSortOrder" 
                        name="sort_order" 
                        class="form-control" 
                        placeholder="0" 
                        value="<?= htmlspecialchars($_POST['sort_order'] ?? ($editItem['sort_order'] ?? '0')) ?>"
                    >
                </div>
            </div>

            <!-- Image Upload Section -->
            <div class="form-group">
                <label class="form-label">Course Featured Image</label>
                
                <div class="upload-area" onclick="document.getElementById('courseImgInput').click()">
                    <div class="upload-icon mb-1">
                        <i data-lucide="graduation-cap" style="width: 36px; height: 36px; color: var(--gold);"></i>
                    </div>
                    <p style="font-weight: 500; margin-bottom: 4px;">Click or drag an image here to upload</p>
                    <p class="text-muted" style="font-size: 0.8rem;">Supported formats: JPG, PNG, WEBP (Max 5MB)</p>
                </div>
                
                <input 
                    type="file" 
                    id="courseImgInput" 
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
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Course Image Preview">
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

            <!-- Is Active Toggle -->
            <div class="form-group">
                <label class="form-label">Status Visibility</label>
                <label class="switch-toggle-label">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1" 
                        <?= (!isset($editItem) || !empty($editItem['is_active'])) ? 'checked' : '' ?>
                    >
                    <span>Active (Visible on public website)</span>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-2 mt-3" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                <button type="submit" class="btn btn-gold">
                    Save Course
                </button>
                <a href="courses.php" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Auto Slug Generation
const titleInput = document.getElementById('courseTitle');
const slugInput = document.getElementById('courseSlug');
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
const courseShortDesc = document.getElementById('courseShortDesc');
const courseShortCounter = document.getElementById('courseShortCounter');
if (courseShortDesc && courseShortCounter) {
    const updateCourseCounter = () => {
        courseShortCounter.textContent = `${courseShortDesc.value.length} / 200`;
    };
    courseShortDesc.addEventListener('input', updateCourseCounter);
    updateCourseCounter();
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
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE title LIKE ? OR short_description LIKE ? ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY sort_order ASC, created_at DESC");
    }
    $coursesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $coursesList = [];
    $error = "Error fetching courses: " . $e->getMessage();
}
?>

<!-- Search Bar & Action Header -->
<div class="search-bar flex-between mb-3">
    <form action="courses.php" method="GET" class="search-input-wrapper" style="max-width: 400px;">
        <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search courses by title..." 
            value="<?= htmlspecialchars($search) ?>"
        >
        <?php if (!empty($search)): ?>
            <a href="courses.php" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: gap; gap: 2px;">
                <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
            </a>
        <?php endif; ?>
    </form>

    <a href="courses.php?action=add" class="btn btn-gold flex items-center gap-1">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add New Course
    </a>
</div>

<div class="admin-card">
    <?php if (empty($coursesList)): ?>
        <div style="text-align: center; padding: 40px 20px;">
            <div style="margin-bottom: 12px;">
                <i data-lucide="graduation-cap" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
            </div>
            <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 6px;">No courses found</h3>
            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 18px;">
                <?= !empty($search) ? 'No courses matched your search query.' : 'You have not added any healing courses yet.' ?>
            </p>
            <a href="courses.php?action=add" class="btn btn-gold btn-sm flex items-center gap-1" style="display: inline-flex;">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add First Course
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 65px; text-align: center;">Image</th>
                        <th style="min-width: 220px;">Title</th>
                        <th style="width: 150px;">Energy Exchange</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 80px; text-align: center;">Order</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coursesList as $crs): ?>
                        <?php 
                            $hasImage = !empty($crs['image']);
                            $thumb = $hasImage 
                                ? (strpos($crs['image'], 'assets/') === 0 || strpos($crs['image'], 'uploads/') === 0 ? '../' . $crs['image'] : $crs['image']) 
                                : '';
                            $isActive = !empty($crs['is_active']);
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if ($hasImage): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($crs['title']) ?>" class="thumbnail-50" onerror="this.src='../assets/images/logo.png'">
                                <?php else: ?>
                                    <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(255,255,255,0.04); border: 1px dashed var(--card-border); display: inline-flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.72rem;" title="No image">
                                        <i data-lucide="image-off" style="width: 16px; height: 16px; opacity: 0.6;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary);"><?= htmlspecialchars($crs['title']) ?></strong>
                                <div class="text-muted" style="font-size: 0.78rem;"><?= htmlspecialchars($crs['slug']) ?></div>
                                <?php 
                                    $specs = array_filter([
                                        !empty($crs['duration']) ? '⏱️ ' . $crs['duration'] : '',
                                        !empty($crs['mode']) ? '🌐 ' . $crs['mode'] : '',
                                        !empty($crs['language']) ? '🗣️ ' . $crs['language'] : ''
                                    ]);
                                    if (!empty($specs)):
                                ?>
                                    <div style="font-size: 0.75rem; color: var(--gold, #C9A84C); margin-top: 4px;">
                                        <?= htmlspecialchars(implode(' • ', $specs)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-gold">
                                    <?= htmlspecialchars(!empty($crs['energy_exchange']) ? $crs['energy_exchange'] : ($crs['price_text'] ?: 'Contact for price')) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                <?= (int)$crs['sort_order'] ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <a href="courses.php?action=edit&id=<?= $crs['id'] ?>" class="btn btn-purple btn-sm flex items-center gap-1" title="Edit Course">
                                        <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit
                                    </a>
                                    <form action="courses.php" method="POST" class="delete-form" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $crs['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm flex items-center gap-1" title="Delete Course">
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
