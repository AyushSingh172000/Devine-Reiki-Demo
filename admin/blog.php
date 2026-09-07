<?php
// Admin Blog Posts CRUD Management
$pageTitle = "Manage Spiritual Blog";

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
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/blog.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting blog post: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Blog post deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Blog post saved successfully!";
}

// Handle Form Submission (Add / Edit)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = generateSlug($title);
    }
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? 'Anupama Agrawal');
    $rawTags = trim($_POST['tags'] ?? '');
    
    // Convert comma-separated tags to JSON array
    $tagsArr = array_map('trim', explode(',', $rawTags));
    $tagsArr = array_values(array_filter($tagsArr));
    $tagsJson = json_encode($tagsArr);

    $isPublished = (int)($_POST['is_published'] ?? 1);
    $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s');
    $currentImage = $_POST['current_image'] ?? '';

    // Handle Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'blog');
        if ($newImage) {
            $currentImage = $newImage;
        }
    } catch (Exception $e) {
        $error = "Image upload failed: " . $e->getMessage();
    }

    if (empty($title)) {
        $error = "Article title is required.";
    }

    if (empty($content)) {
        $error = "Article content is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, image = ?, author = ?, tags = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $slug, $excerpt, $content, $currentImage, $author, $tagsJson, $isPublished, $publishedAt, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, image, author, tags, is_published, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $slug, $excerpt, $content, $currentImage, $author, $tagsJson, $isPublished, $publishedAt]);
            }
            header("Location: " . BASE_URL . "admin/blog.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Posts for Table View
$posts = [];
try {
    $stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY published_at DESC");
    $posts = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching blog posts: " . $e->getMessage());
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
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Article' : 'Write New Article'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/blog.php" class="btn-admin btn-admin-secondary">← Back to Blog List</a>
        </div>

        <form action="blog.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Article Title *</label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required placeholder="e.g. Understanding the 7 Major Chakras">
                </div>

                <div class="form-group">
                    <label class="form-label">URL Slug (Leave blank to auto-generate)</label>
                    <input type="text" name="slug" class="form-input" value="<?php echo htmlspecialchars($editItem['slug'] ?? ''); ?>" placeholder="understanding-7-major-chakras">
                </div>

                <div class="form-group">
                    <label class="form-label">Author Name</label>
                    <input type="text" name="author" class="form-input" value="<?php echo htmlspecialchars($editItem['author'] ?? 'Anupama Agrawal'); ?>" placeholder="Anupama Agrawal">
                </div>

                <div class="form-group">
                    <label class="form-label">Tags (Comma-separated)</label>
                    <?php 
                    $existingTags = is_string($editItem['tags'] ?? null) ? json_decode($editItem['tags'], true) : ($editItem['tags'] ?? []);
                    $tagsStr = is_array($existingTags) ? implode(', ', $existingTags) : '';
                    ?>
                    <input type="text" name="tags" class="form-input" value="<?php echo htmlspecialchars($tagsStr); ?>" placeholder="Chakras, Reiki, Healing">
                </div>

                <div class="form-group">
                    <label class="form-label">Publication Date & Time</label>
                    <input type="datetime-local" name="published_at" class="form-input" value="<?php echo !empty($editItem['published_at']) ? date('Y-m-d\TH:i', strtotime($editItem['published_at'])) : date('Y-m-d\TH:i'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_published" class="form-select">
                        <option value="1" <?php echo (($editItem['is_published'] ?? 1) == 1) ? 'selected' : ''; ?>>Published</option>
                        <option value="0" <?php echo (($editItem['is_published'] ?? 1) == 0) ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Featured Header Image File</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <?php if (!empty($editItem['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image']); ?>" width="100" height="60" style="object-fit: cover; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Short Excerpt / Summary *</label>
                    <textarea name="excerpt" class="form-textarea" style="min-height: 80px;" required><?php echo htmlspecialchars($editItem['excerpt'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Full Article Content (HTML or Paragraphs) *</label>
                    <textarea name="content" class="form-textarea" style="min-height: 220px;" required><?php echo htmlspecialchars($editItem['content'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Article →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- LIST TABLE VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">All Spiritual Articles (<?php echo count($posts); ?>)</h2>
            <a href="blog.php?action=add" class="btn-admin btn-admin-primary">✍️ Write New Article</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Published Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $pst): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($pst['image'] ?: 'assets/images/blog/reiki-guide.jpg'); ?>" width="54" height="42" style="object-fit: cover; border-radius: 8px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pst['title']); ?></strong>
                                    <span style="display: block; font-size: 0.78rem; color: var(--admin-muted);"><?php echo htmlspecialchars($pst['slug']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($pst['author']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($pst['published_at'])); ?></td>
                                <td>
                                    <?php echo ($pst['is_published']) ? '<span class="badge-status badge-completed">Published</span>' : '<span class="badge-status badge-pending">Draft</span>'; ?>
                                </td>
                                <td>
                                    <a href="blog.php?action=edit&id=<?php echo $pst['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                    <a href="blog.php?action=delete&id=<?php echo $pst['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this article?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--admin-muted); padding: 30px;">No articles found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
