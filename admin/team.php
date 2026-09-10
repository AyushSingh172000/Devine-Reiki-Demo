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

// A. DELETE TEAM MEMBER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM team_members WHERE id = ?");
            $stmt->execute([$deleteId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item && !empty($item['image'])) {
                deleteImage($item['image']);
            }

            $delStmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Team member removed successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error removing team member: " . $e->getMessage();
        }
    }
    header("Location: team.php");
    exit;
}

// B. SAVE TEAM MEMBER (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_team'])) {
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? 'healer');
    $title = trim($_POST['title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? '';

    // Process Specialties JSON
    $rawSpecJson = trim($_POST['specialties_json'] ?? '');
    $specArr = [];
    if (!empty($rawSpecJson)) {
        $decoded = json_decode($rawSpecJson, true);
        if (is_array($decoded)) {
            $specArr = array_values(array_filter(array_map('trim', $decoded)));
        }
    }
    if (empty($specArr) && !empty($_POST['specialties_raw'])) {
        $parts = explode(',', $_POST['specialties_raw']);
        $specArr = array_values(array_filter(array_map('trim', $parts)));
    }
    $specialtiesJson = !empty($specArr) ? json_encode($specArr) : null;

    // Remove current photo if requested
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($currentImage)) {
            deleteImage($currentImage);
            $currentImage = null;
        }
    }

    // Handle New Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = uploadImage($_FILES['image'], '../uploads/team/');
        if ($uploadRes['success']) {
            if (!empty($currentImage) && $currentImage !== $uploadRes['path']) {
                deleteImage($currentImage);
            }
            $currentImage = $uploadRes['path'];
        } else {
            $error = "Photo upload error: " . $uploadRes['error'];
        }
    }

    if (empty($name)) {
        $error = "Team member name is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // UPDATE
                $updateSql = "UPDATE team_members SET name = ?, role = ?, title = ?, bio = ?, specialties = ?, image = ?, sort_order = ?, is_active = ? WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([$name, $role, $title, $bio, $specialtiesJson, $currentImage, $sortOrder, $isActive, $editId]);
                $_SESSION['flash_success'] = "Team member updated successfully!";
            } else {
                // INSERT
                $insertSql = "INSERT INTO team_members (name, role, title, bio, specialties, image, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([$name, $role, $title, $bio, $specialtiesJson, $currentImage, $sortOrder, $isActive]);
                $_SESSION['flash_success'] = "New team member added successfully!";
            }
            header("Location: team.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error saving team member: " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. FETCH DATA DEPENDING ON VIEW
// =========================================================================

$editItem = null;
$existingSpecialties = [];

if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editItem) {
        if (!empty($editItem['specialties'])) {
            $existingSpecialties = json_decode($editItem['specialties'], true) ?: [];
        }
    } elseif ($action === 'edit') {
        $_SESSION['flash_error'] = "Team member not found.";
        header("Location: team.php");
        exit;
    }
}

$pageTitle = ($action === 'add') ? 'Add Team Member' : (($action === 'edit') ? 'Edit Team Member' : 'Team Members');

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- =========================================================================
     ADD / EDIT VIEW
     ========================================================================= -->
<div style="max-width: 860px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="team.php" class="btn btn-outline btn-sm flex items-center gap-1">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Team Members
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: #ffffff;">
            <?= $action === 'edit' ? 'Edit Team Member' : 'Add Team Member' ?>
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error flex items-center gap-2">
            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="team.php?action=<?= $action ?>&id=<?= $editId ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_team" value="1">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>">
            <input type="hidden" name="specialties_json" id="specialtiesJsonInput" value='<?= !empty($existingSpecialties) ? htmlspecialchars(json_encode($existingSpecialties)) : "[]" ?>'>

            <div class="form-row">
                <!-- Name -->
                <div class="form-group">
                    <label for="memberName" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="memberName" 
                        name="name" 
                        class="form-control" 
                        placeholder="e.g. Binal Gajjar" 
                        value="<?= htmlspecialchars($_POST['name'] ?? ($editItem['name'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Role -->
                <div class="form-group">
                    <label for="memberRole" class="form-label">Role (Category Identifier)</label>
                    <input 
                        type="text" 
                        id="memberRole" 
                        name="role" 
                        class="form-control" 
                        placeholder="e.g. founder, master, healer, teacher" 
                        value="<?= htmlspecialchars($_POST['role'] ?? ($editItem['role'] ?? 'healer')) ?>"
                    >
                    <div class="form-hint">Internal categorization keyword (e.g. founder, teacher).</div>
                </div>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label for="memberTitle" class="form-label">Professional Public Title</label>
                <input 
                    type="text" 
                    id="memberTitle" 
                    name="title" 
                    class="form-control" 
                    placeholder="e.g. Reiki Grand Master & Holistic Energy Healer" 
                    value="<?= htmlspecialchars($_POST['title'] ?? ($editItem['title'] ?? '')) ?>"
                >
            </div>

            <!-- Bio with HTML Toolbar -->
            <div class="form-group">
                <label for="memberBio" class="form-label">Biography (HTML Supported)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertTag('memberBio', '<b>', '</b>')" title="Bold"><i data-lucide="bold" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('memberBio', '<i>', '</i>')" title="Italic"><i data-lucide="italic" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('memberBio', '<p>', '</p>')" title="Paragraph"><i data-lucide="pilcrow" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('memberBio', '<ul>\n  <li>', '</li>\n</ul>')" title="List"><i data-lucide="list" style="width: 14px; height: 14px;"></i></button>
                </div>
                <textarea 
                    id="memberBio" 
                    name="bio" 
                    class="form-control has-toolbar" 
                    style="min-height: 150px;" 
                    placeholder="Describe their healing lineage, credentials, experience, and spiritual philosophy..."
                ><?= htmlspecialchars($_POST['bio'] ?? ($editItem['bio'] ?? '')) ?></textarea>
            </div>

            <!-- Specialties Chips Input -->
            <div class="form-group">
                <label class="form-label">Specialties (Type and press comma or Enter)</label>
                <div class="tags-input-wrapper" id="specialtiesWidget">
                    <div id="specChipsList" style="display: contents;"></div>
                    <input 
                        type="text" 
                        id="specInputBare" 
                        class="tag-bare-input" 
                        placeholder="e.g. Usui Reiki, Chakra Healing..."
                    >
                </div>
                <div class="form-hint">Press comma or Enter to create a specialty chip. Click ✕ to remove.</div>
            </div>

            <!-- Photo Upload with CIRCULAR PREVIEW -->
            <div class="form-group">
                <label class="form-label">Member Profile Photo (Circular Preview)</label>
                
                <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                    <!-- Circular Preview Container -->
                    <div style="position: relative; width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 2.5px solid var(--gold); background: #151230; flex-shrink: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.4);">
                        <?php 
                            $imgSrc = !empty($editItem['image']) 
                                ? (strpos($editItem['image'], 'assets/') === 0 || strpos($editItem['image'], 'uploads/') === 0 ? '../' . $editItem['image'] : $editItem['image']) 
                                : '../assets/images/team/member-1.jpg';
                        ?>
                        <img 
                            id="circularPreviewImg" 
                            src="<?= htmlspecialchars($imgSrc) ?>" 
                            alt="Profile Photo" 
                            style="width: 100%; height: 100%; object-fit: cover;"
                            onerror="this.src='../assets/images/logo.png'"
                        >
                    </div>

                    <div style="flex: 1; min-width: 200px;">
                        <button type="button" class="btn btn-outline btn-sm flex items-center gap-1" onclick="document.getElementById('teamImgInput').click()">
                            <i data-lucide="upload" style="width: 15px; height: 15px;"></i> Choose Photo File
                        </button>
                        <p class="text-muted mt-1" style="font-size: 0.78rem;">Upload square or portrait image (JPG, PNG, WEBP, max 5MB). Photo is automatically presented in a circular frame.</p>

                        <?php if (!empty($editItem['image'])): ?>
                            <label class="switch-toggle-label mt-1" style="font-size: 0.82rem; color: var(--error);">
                                <input type="checkbox" name="remove_image" value="1">
                                Remove current photo
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <input 
                    type="file" 
                    id="teamImgInput" 
                    name="image" 
                    accept="image/jpeg,image/png,image/webp" 
                    style="display: none;"
                >
            </div>

            <div class="form-row">
                <!-- Sort Order -->
                <div class="form-group">
                    <label for="memberSortOrder" class="form-label">Sort Order</label>
                    <input 
                        type="number" 
                        id="memberSortOrder" 
                        name="sort_order" 
                        class="form-control" 
                        placeholder="0" 
                        value="<?= htmlspecialchars($_POST['sort_order'] ?? ($editItem['sort_order'] ?? '0')) ?>"
                    >
                </div>

                <!-- Is Active -->
                <div class="form-group" style="display: flex; flex-direction: column; justify-content: center;">
                    <label class="form-label">Visibility</label>
                    <label class="switch-toggle-label" style="margin-top: 6px;">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            value="1" 
                            <?= (!isset($editItem) || !empty($editItem['is_active'])) ? 'checked' : '' ?>
                        >
                        <span>Active (Publicly displayed in team section)</span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-2 mt-3" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                <button type="submit" class="btn btn-gold">
                    Save Team Member
                </button>
                <a href="team.php" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Live Circular Image Preview
const teamImgInput = document.getElementById('teamImgInput');
const circularPreviewImg = document.getElementById('circularPreviewImg');

if (teamImgInput && circularPreviewImg) {
    teamImgInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                circularPreviewImg.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Specialties Tag Chips Widget
let specList = [];
try {
    const initialSpecJson = document.getElementById('specialtiesJsonInput').value;
    specList = JSON.parse(initialSpecJson) || [];
} catch(e) {
    specList = [];
}

const specChipsList = document.getElementById('specChipsList');
const specInputBare = document.getElementById('specInputBare');
const specialtiesJsonInput = document.getElementById('specialtiesJsonInput');

function renderSpecChips() {
    specChipsList.innerHTML = '';
    specList.forEach((spec, index) => {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `<span>${escapeHtml(spec)}</span><span class="tag-chip-remove" onclick="removeSpec(${index})">✕</span>`;
        specChipsList.appendChild(chip);
    });
    specialtiesJsonInput.value = JSON.stringify(specList);
}

function addSpec(rawSpec) {
    const cleanSpec = rawSpec.trim();
    if (cleanSpec && !specList.includes(cleanSpec)) {
        specList.push(cleanSpec);
        renderSpecChips();
    }
}

function removeSpec(index) {
    specList.splice(index, 1);
    renderSpecChips();
}

if (specInputBare) {
    specInputBare.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addSpec(this.value);
            this.value = '';
        } else if (e.key === 'Backspace' && this.value === '' && specList.length > 0) {
            removeSpec(specList.length - 1);
        }
    });
    specInputBare.addEventListener('blur', function() {
        if (this.value.trim() !== '') {
            addSpec(this.value);
            this.value = '';
        }
    });
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

renderSpecChips();

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
     LIST VIEW (CARDS GRID LAYOUT)
     ========================================================================= -->
<?php
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM team_members WHERE name LIKE ? OR role LIKE ? OR title LIKE ? ORDER BY sort_order ASC, created_at DESC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC, created_at DESC");
    }
    $teamList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $teamList = [];
    $error = "Error fetching team members: " . $e->getMessage();
}
?>

<!-- Search & Action Bar -->
<div class="search-bar flex-between mb-4">
    <form action="team.php" method="GET" class="search-input-wrapper" style="max-width: 380px;">
        <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search team by name or specialty..." 
            value="<?= htmlspecialchars($search) ?>"
        >
        <?php if (!empty($search)): ?>
            <a href="team.php" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 2px;">
                <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
            </a>
        <?php endif; ?>
    </form>

    <a href="team.php?action=add" class="btn btn-gold flex items-center gap-1">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add Team Member
    </a>
</div>

<?php if (empty($teamList)): ?>
    <div class="admin-card" style="text-align: center; padding: 50px 20px;">
        <div style="margin-bottom: 14px;">
            <i data-lucide="users" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
        </div>
        <h3 style="font-size: 1.2rem; color: #ffffff; margin-bottom: 6px;">No team members found</h3>
        <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;">
            <?= !empty($search) ? 'No team members matched your search.' : 'You have not added any practitioners or healing masters yet.' ?>
        </p>
        <a href="team.php?action=add" class="btn btn-gold btn-sm flex items-center gap-1" style="display: inline-flex;">
            <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add First Team Member
        </a>
    </div>
<?php else: ?>
    <!-- Responsive Team Cards Grid -->
    <div class="team-admin-grid mb-4">
        <?php foreach ($teamList as $member): ?>
            <?php 
                $hasImg = !empty($member['image']);
                $imgSrc = $hasImg 
                    ? (strpos($member['image'], 'assets/') === 0 || strpos($member['image'], 'uploads/') === 0 ? '../' . $member['image'] : $member['image']) 
                    : null;
                $specialties = !empty($member['specialties']) ? json_decode($member['specialties'], true) : [];
                $isActive = !empty($member['is_active']);
            ?>
            <div class="team-card">
                <!-- Circular Avatar -->
                <?php if ($hasImg): ?>
                    <img 
                        src="<?= htmlspecialchars($imgSrc) ?>" 
                        alt="<?= htmlspecialchars($member['name']) ?>" 
                        class="team-avatar-circular"
                        onerror="this.src='../assets/images/logo.png'"
                    >
                <?php else: ?>
                    <div class="team-avatar-placeholder">
                        <?= strtoupper(substr($member['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <!-- Name & Title -->
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #ffffff; margin-bottom: 4px;">
                    <?= htmlspecialchars($member['name']) ?>
                </h3>

                <p class="text-gold" style="font-size: 0.85rem; font-weight: 500; margin-bottom: 8px;">
                    <?= htmlspecialchars($member['title'] ?: 'Energy Healer') ?>
                </p>

                <!-- Role Badge -->
                <div class="mb-2">
                    <span class="badge" style="background: rgba(124, 107, 196, 0.15); border: 1px solid rgba(124, 107, 196, 0.3); color: #c4b8ff; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.8px;">
                        <?= htmlspecialchars($member['role'] ?: 'healer') ?>
                    </span>
                    <?php if ($isActive): ?>
                        <span class="badge badge-success" style="margin-left: 4px;">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger" style="margin-left: 4px;">Inactive</span>
                    <?php endif; ?>
                </div>

                <!-- Specialties tags -->
                <?php if (!empty($specialties) && is_array($specialties)): ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; margin-bottom: 16px;">
                        <?php foreach (array_slice($specialties, 0, 4) as $spec): ?>
                            <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 12px; background: var(--input-bg); color: var(--text-muted); border: 1px solid var(--card-border);">
                                <?= htmlspecialchars($spec) ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($specialties) > 4): ?>
                            <span style="font-size: 0.72rem; padding: 2px 6px; color: var(--gold);">+<?= count($specialties) - 4 ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex gap-2" style="justify-content: center; border-top: 1px solid var(--card-border); padding-top: 14px; margin-top: 12px;">
                    <a href="team.php?action=edit&id=<?= $member['id'] ?>" class="btn btn-purple btn-sm flex items-center gap-1">
                        <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit
                    </a>
                    <form action="team.php" method="POST" class="delete-form" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $member['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm flex items-center gap-1">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
