<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
require_once 'upload-helper.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'All');
$error = '';

$currentAdmin = $_SESSION['admin_user'] ?? ($_SESSION['admin_username'] ?? 'Admin');

// =========================================================================
// 1. HANDLE POST ACTIONS (Delete, Insert, Update)
// =========================================================================

// A. DELETE BLOG POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM blog_posts WHERE id = ?");
            $stmt->execute([$deleteId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post && !empty($post['image'])) {
                deleteImage($post['image']);
            }

            $delStmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Blog post deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting blog post: " . $e->getMessage();
        }
    }
    header("Location: blog.php");
    exit;
}

// B. SAVE BLOG POST (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_blog'])) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = generateSlug($title);
    } else {
        $slug = generateSlug($slug);
    }

    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? $currentAdmin);
    if (empty($author)) {
        $author = $currentAdmin;
    }

    // Process Tags JSON
    $rawTagsJson = trim($_POST['tags_json'] ?? '');
    $tagsArr = [];
    if (!empty($rawTagsJson)) {
        $decoded = json_decode($rawTagsJson, true);
        if (is_array($decoded)) {
            $tagsArr = array_values(array_filter(array_map('trim', $decoded)));
        }
    }
    // Fallback if raw comma input was provided
    if (empty($tagsArr) && !empty($_POST['tags_raw'])) {
        $parts = explode(',', $_POST['tags_raw']);
        $tagsArr = array_values(array_filter(array_map('trim', $parts)));
    }
    $tagsJson = !empty($tagsArr) ? json_encode($tagsArr) : null;

    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    // Published Date handling
    $publishedDate = trim($_POST['published_at'] ?? '');
    if ($isPublished && empty($publishedDate)) {
        $publishedAt = date('Y-m-d H:i:s');
    } elseif (!empty($publishedDate)) {
        $publishedAt = date('Y-m-d H:i:s', strtotime($publishedDate));
    } else {
        $publishedAt = null;
    }

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
        $uploadRes = uploadImage($_FILES['image'], '../uploads/blog/');
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
        $error = "Blog post title is required.";
    }

    // Ensure unique slug
    if (empty($error)) {
        $checkSlug = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $editId]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . time();
        }
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // UPDATE
                $updateSql = "UPDATE blog_posts SET 
                    title = ?, 
                    slug = ?, 
                    excerpt = ?, 
                    content = ?, 
                    image = ?, 
                    author = ?, 
                    tags = ?, 
                    is_published = ?, 
                    published_at = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([$title, $slug, $excerpt, $content, $currentImage, $author, $tagsJson, $isPublished, $publishedAt, $editId]);
                $_SESSION['flash_success'] = "Blog post updated successfully!";
            } else {
                // INSERT
                $insertSql = "INSERT INTO blog_posts 
                    (title, slug, excerpt, content, image, author, tags, is_published, published_at, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([$title, $slug, $excerpt, $content, $currentImage, $author, $tagsJson, $isPublished, $publishedAt]);
                $_SESSION['flash_success'] = "New blog post published successfully!";
            }
            header("Location: blog.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error saving blog post: " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. FETCH DATA DEPENDING ON VIEW
// =========================================================================

$editItem = null;
$existingTags = [];

if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editItem) {
        if (!empty($editItem['tags'])) {
            $existingTags = json_decode($editItem['tags'], true) ?: [];
        }
    } elseif ($action === 'edit') {
        $_SESSION['flash_error'] = "Blog post not found.";
        header("Location: blog.php");
        exit;
    }
}

// Set Page Title
if ($action === 'add') {
    $pageTitle = 'Add Blog Post';
} elseif ($action === 'edit') {
    $pageTitle = 'Edit Blog Post';
} else {
    $pageTitle = 'Blog Posts';
}

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- =========================================================================
     ADD / EDIT VIEW
     ========================================================================= -->
<div style="max-width: 900px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="blog.php" class="btn btn-outline btn-sm">
            <i data-lucide="arrow-left"></i> Back to Blog Posts
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: #ffffff;">
            <?= $action === 'edit' ? 'Edit Article' : 'Write New Article' ?>
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i data-lucide="alert-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="blog.php?action=<?= $action ?>&id=<?= $editId ?>" method="POST" enctype="multipart/form-data" id="blogForm">
            <input type="hidden" name="save_blog" value="1">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>">
            <input type="hidden" name="tags_json" id="tagsJsonInput" value='<?= !empty($existingTags) ? htmlspecialchars(json_encode($existingTags)) : "[]" ?>'>

            <div class="form-row">
                <!-- Title -->
                <div class="form-group">
                    <label for="blogTitle" class="form-label">Article Title <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="blogTitle" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g. The Power of Distance Reiki Healing"
                        value="<?= htmlspecialchars($_POST['title'] ?? ($editItem['title'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="blogSlug" class="form-label">URL Slug</label>
                    <input 
                        type="text" 
                        id="blogSlug" 
                        name="slug" 
                        class="form-control" 
                        placeholder="the-power-of-distance-reiki-healing"
                        value="<?= htmlspecialchars($_POST['slug'] ?? ($editItem['slug'] ?? '')) ?>"
                    >
                    <div class="form-hint">Auto-generated from title or custom identifier.</div>
                </div>
            </div>

            <!-- Excerpt -->
            <div class="form-group">
                <div class="flex-between">
                    <label for="blogExcerpt" class="form-label">Article Excerpt</label>
                    <span class="char-counter-text" id="blogExcerptCounter">0 / 300</span>
                </div>
                <textarea 
                    id="blogExcerpt" 
                    name="excerpt" 
                    class="form-control" 
                    rows="2" 
                    maxlength="300"
                    placeholder="Brief summary appearing on blog feed cards (max 300 characters)..."
                ><?= htmlspecialchars($_POST['excerpt'] ?? ($editItem['excerpt'] ?? '')) ?></textarea>
            </div>

            <!-- Full HTML Content with Toolbar & Preview Modal Button -->
            <div class="form-group">
                <div class="flex-between" style="align-items: flex-end; margin-bottom: 6px;">
                    <label for="blogContent" class="form-label" style="margin-bottom: 0;">Full Content (HTML Supported)</label>
                    <button type="button" class="btn btn-outline btn-sm text-gold" onclick="openBlogPreviewModal()">
                        <i data-lucide="eye"></i> Preview Rendered HTML
                    </button>
                </div>

                <div class="editor-toolbar">
                    <button type="button" onclick="insertTag('blogContent', '<b>', '</b>')" title="Bold"><i data-lucide="bold"></i></button>
                    <button type="button" onclick="insertTag('blogContent', '<i>', '</i>')" title="Italic"><i data-lucide="italic"></i></button>
                    <button type="button" onclick="insertTag('blogContent', '<h3>', '</h3>')" title="Heading 3"><i data-lucide="heading-3"></i></button>
                    <button type="button" onclick="insertTag('blogContent', '<p>', '</p>')" title="Paragraph"><span style="font-weight: bold; font-family: serif;">¶</span></button>
                    <button type="button" onclick="insertTag('blogContent', '<a href=\x22#\x22>', '</a>')" title="Link"><i data-lucide="link"></i></button>
                    <button type="button" onclick="insertTag('blogContent', '<ul>\n  <li>', '</li>\n</ul>')" title="List"><i data-lucide="list"></i></button>
                    <button type="button" onclick="insertTag('blogContent', '<blockquote>', '</blockquote>')" title="Quote"><i data-lucide="quote"></i></button>
                </div>
                <textarea 
                    id="blogContent" 
                    name="content" 
                    class="form-control has-toolbar" 
                    style="min-height: 260px; font-family: 'Inter', monospace; line-height: 1.6;"
                    placeholder="Write your in-depth spiritual healing article here. You can use HTML tags or format with the toolbar..."
                ><?= htmlspecialchars($_POST['content'] ?? ($editItem['content'] ?? '')) ?></textarea>
            </div>

            <!-- Featured Image Upload Section -->
            <div class="form-group">
                <label class="form-label">Featured Header Image</label>
                
                <div class="upload-area" onclick="document.getElementById('blogImgInput').click()">
                    <i data-lucide="cloud-upload"></i>
                    <p style="font-weight: 500; margin-bottom: 4px;">Upload featured article banner</p>
                    <p class="text-muted" style="font-size: 0.8rem;">Supported formats: JPG, PNG, WEBP (Max 5MB)</p>
                </div>
                
                <input 
                    type="file" 
                    id="blogImgInput" 
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
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Blog Banner Preview">
                        <button type="button" class="remove-preview" onclick="removePreview(this)" title="Remove image">✕</button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($editItem['image'])): ?>
                    <label class="switch-toggle-label mt-2" style="font-size: 0.82rem; color: var(--error);">
                        <input type="checkbox" name="remove_image" value="1">
                        Remove featured banner
                    </label>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <!-- Author -->
                <div class="form-group">
                    <label for="blogAuthor" class="form-label">Author Name</label>
                    <input 
                        type="text" 
                        id="blogAuthor" 
                        name="author" 
                        class="form-control" 
                        value="<?= htmlspecialchars($_POST['author'] ?? ($editItem['author'] ?? $currentAdmin)) ?>"
                        required
                    >
                </div>

                <!-- Published Date -->
                <div class="form-group">
                    <label for="publishedDate" class="form-label">Published Date</label>
                    <?php 
                        $rawDate = !empty($editItem['published_at']) ? date('Y-m-d', strtotime($editItem['published_at'])) : date('Y-m-d');
                    ?>
                    <input 
                        type="date" 
                        id="publishedDate" 
                        name="published_at" 
                        class="form-control" 
                        value="<?= htmlspecialchars($_POST['published_at'] ?? $rawDate) ?>"
                    >
                </div>
            </div>

            <!-- Tags with Interactive Chips -->
            <div class="form-group">
                <label class="form-label">Tags (Type and press comma or Enter)</label>
                <div class="tags-input-wrapper" id="tagsWidget">
                    <div id="tagChipsList" style="display: contents;"></div>
                    <input 
                        type="text" 
                        id="tagInputBare" 
                        class="tag-bare-input" 
                        placeholder="Add tag and press comma..."
                    >
                </div>
                <div class="form-hint">Click ✕ to remove tag. Tags are stored as JSON for easy querying.</div>
            </div>

            <!-- Is Published Toggle -->
            <div class="form-group">
                <label class="form-label">Publishing Status</label>
                <label class="switch-toggle-label">
                    <input 
                        type="checkbox" 
                        name="is_published" 
                        value="1" 
                        <?= (!isset($editItem) || !empty($editItem['is_published'])) ? 'checked' : '' ?>
                    >
                    <span>Published (Live and accessible on the blog feed)</span>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-2 mt-3" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                <button type="submit" class="btn btn-gold">
                    Save Article
                </button>
                <a href="blog.php" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- LIVE HTML PREVIEW MODAL -->
<div class="modal-overlay" id="blogPreviewModal">
    <div class="modal" style="max-width: 780px;">
        <div class="modal-header">
            <h3 class="modal-title flex items-center gap-2">
                <i data-lucide="file-text"></i> Article HTML Live Preview
            </h3>
            <button type="button" class="modal-close" onclick="closeBlogPreviewModal()"><i data-lucide="x"></i></button>
        </div>
        <div style="margin-bottom: 20px;">
            <h1 id="previewModalHeading" style="font-size: 1.6rem; color: #ffffff; margin-bottom: 12px;"></h1>
            <div class="text-muted" style="font-size: 0.85rem; margin-bottom: 18px; border-bottom: 1px solid var(--card-border); padding-bottom: 12px;">
                By <span id="previewModalAuthor" class="text-gold"></span> &bull; <span id="previewModalDate"></span>
            </div>
            <div id="previewModalBody" style="color: #e2e8f0; font-size: 0.95rem; line-height: 1.8;"></div>
        </div>
        <div style="text-align: right;">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeBlogPreviewModal()">Close Preview</button>
        </div>
    </div>
</div>

<script>
// Auto Slug Generation
const blogTitle = document.getElementById('blogTitle');
const blogSlug = document.getElementById('blogSlug');
let userTouchedSlug = <?= ($action === 'edit') ? 'true' : 'false' ?>;

if (blogSlug) {
    blogSlug.addEventListener('input', () => { userTouchedSlug = true; });
}
if (blogTitle && blogSlug) {
    blogTitle.addEventListener('input', function() {
        if (!userTouchedSlug || blogSlug.value === '') {
            blogSlug.value = this.value
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
}

// Character Counter for Excerpt
const blogExcerpt = document.getElementById('blogExcerpt');
const blogExcerptCounter = document.getElementById('blogExcerptCounter');
if (blogExcerpt && blogExcerptCounter) {
    const updateExcerptCounter = () => {
        blogExcerptCounter.textContent = `${blogExcerpt.value.length} / 300`;
    };
    blogExcerpt.addEventListener('input', updateExcerptCounter);
    updateExcerptCounter();
}

// Tag Chips Input Widget
let tagsList = [];
try {
    const initialTagsJson = document.getElementById('tagsJsonInput').value;
    tagsList = JSON.parse(initialTagsJson) || [];
} catch(e) {
    tagsList = [];
}

const tagChipsList = document.getElementById('tagChipsList');
const tagInputBare = document.getElementById('tagInputBare');
const tagsJsonInput = document.getElementById('tagsJsonInput');

function renderTagChips() {
    tagChipsList.innerHTML = '';
    tagsList.forEach((tag, index) => {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `<span>#${escapeHtml(tag)}</span><span class="tag-chip-remove" onclick="removeTag(${index})">✕</span>`;
        tagChipsList.appendChild(chip);
    });
    tagsJsonInput.value = JSON.stringify(tagsList);
}

function removeTag(index) {
    tagsList.splice(index, 1);
    renderTagChips();
}

if (tagInputBare) {
    tagInputBare.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const val = this.value.trim().replace(/^#/, '').replace(/,/g, '');
            if (val && !tagsList.includes(val)) {
                tagsList.push(val);
                renderTagChips();
            }
            this.value = '';
        } else if (e.key === 'Backspace' && this.value === '' && tagsList.length > 0) {
            tagsList.pop();
            renderTagChips();
        }
    });
}

renderTagChips();

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

// Live HTML Preview Modal
function openBlogPreviewModal() {
    const title = document.getElementById('blogTitle').value || 'Untitled Article';
    const author = document.getElementById('blogAuthor').value || 'Admin';
    const date = document.getElementById('publishedDate').value || 'Today';
    const content = document.getElementById('blogContent').value || '<p class="text-muted">No content written yet.</p>';

    document.getElementById('previewModalHeading').textContent = title;
    document.getElementById('previewModalAuthor').textContent = author;
    document.getElementById('previewModalDate').textContent = date;
    document.getElementById('previewModalBody').innerHTML = content;

    document.getElementById('blogPreviewModal').classList.add('active');
    if (window.lucide) lucide.createIcons();
}

function closeBlogPreviewModal() {
    document.getElementById('blogPreviewModal').classList.remove('active');
}
</script>

<?php else: ?>
<!-- =========================================================================
     LIST VIEW (DEFAULT)
     ========================================================================= -->
<?php
try {
    $whereParts = [];
    $params = [];

    if (!empty($search)) {
        $whereParts[] = "(title LIKE ? OR excerpt LIKE ? OR author LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if ($statusFilter === 'Published') {
        $whereParts[] = "is_published = 1";
    } elseif ($statusFilter === 'Draft') {
        $whereParts[] = "is_published = 0";
    }

    $whereClause = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';
    $sql = "SELECT * FROM blog_posts {$whereClause} ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $postsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $postsList = [];
    $error = "Error fetching blog posts: " . $e->getMessage();
}
?>

<!-- Search Bar & Filters -->
<div class="search-bar flex-between mb-3">
    <div class="flex gap-2" style="flex: 1; flex-wrap: wrap;">
        <!-- Search Form -->
        <form action="blog.php" method="GET" class="search-input-wrapper" style="max-width: 380px;">
            <i data-lucide="search"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Search articles by title or keyword..." 
                value="<?= htmlspecialchars($search) ?>"
            >
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php if (!empty($search)): ?>
                <a href="blog.php?status=<?= urlencode($statusFilter) ?>" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem;">✕ Clear</a>
            <?php endif; ?>
        </form>

        <!-- Status Filter -->
        <form action="blog.php" method="GET" id="statusFilterForm" style="display: flex; align-items: center;">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Statuses</option>
                <option value="Published" <?= $statusFilter === 'Published' ? 'selected' : '' ?>>Published Only</option>
                <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Drafts Only</option>
            </select>
        </form>
    </div>

    <a href="blog.php?action=add" class="btn btn-gold">
        <i data-lucide="plus"></i> Add Post
    </a>
</div>

<div class="admin-card">
    <?php if (empty($postsList)): ?>
        <div class="empty-state" style="text-align: center; padding: 40px 20px;">
            <i data-lucide="file-text"></i>
            <h3 style="font-size: 1.1rem; color: #ffffff; margin-bottom: 6px;">No blog posts found</h3>
            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 18px;">
                <?= (!empty($search) || $statusFilter !== 'All') ? 'No articles match your search or status filter.' : 'You have not authored any healing blog articles yet.' ?>
            </p>
            <a href="blog.php?action=add" class="btn btn-gold btn-sm"><i data-lucide="plus"></i> Write First Post</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Published Date</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postsList as $post): ?>
                        <?php 
                            $thumb = !empty($post['image']) 
                                ? (strpos($post['image'], 'assets/') === 0 || strpos($post['image'], 'uploads/') === 0 ? '../' . $post['image'] : $post['image']) 
                                : '../assets/images/blog/reiki-guide.jpg';
                            $isPub = !empty($post['is_published']);
                            $pubDate = !empty($post['published_at']) 
                                ? date('M d, Y', strtotime($post['published_at'])) 
                                : date('M d, Y', strtotime($post['created_at']));
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="thumbnail-50" onerror="this.src='../assets/images/logo.png'">
                            </td>
                            <td>
                                <strong style="color: #ffffff;"><?= htmlspecialchars($post['title']) ?></strong>
                                <div class="text-muted" style="font-size: 0.78rem;"><?= htmlspecialchars($post['slug']) ?></div>
                            </td>
                            <td>
                                <span class="text-muted" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($post['author'] ?: 'Admin') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isPub): ?>
                                    <span class="badge badge-success">Published</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                                <?= $pubDate ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <a href="blog.php?action=edit&id=<?= $post['id'] ?>" class="btn btn-purple btn-sm btn-icon" title="Edit Article">
                                        <i data-lucide="pencil"></i>
                                    </a>
                                    <form action="blog.php" method="POST" class="delete-form" style="display: inline;" data-item-name="<?= htmlspecialchars($post['title']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete Article">
                                            <i data-lucide="trash-2"></i>
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
