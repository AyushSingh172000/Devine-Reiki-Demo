<?php
// Admin Products CRUD Management
$pageTitle = "Manage Products";

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
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$editId]);
        header("Location: " . BASE_URL . "admin/products.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Product deleted successfully!";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = "Product saved successfully!";
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
    $price = (float)($_POST['price'] ?? 0);
    $origPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    
    // Auto-calculate discount percentage
    $discountPercent = 0;
    if ($origPrice && $origPrice > $price) {
        $discountPercent = (int)round((($origPrice - $price) / $origPrice) * 100);
    }

    $badgeText = trim($_POST['badge_text'] ?? 'Reiki Charged');
    $category = trim($_POST['category'] ?? 'Bracelets');
    $inStock = (int)($_POST['in_stock'] ?? 1);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 1);
    $currentImage = $_POST['current_image'] ?? '';
    $currentAddl = $_POST['current_additional'] ?? '[]';

    // Handle Primary Image Upload
    try {
        $newImage = handleAdminImageUpload('image_file', 'products');
        if ($newImage) {
            $currentImage = $newImage;
        }
    } catch (Exception $e) {
        $error = "Primary image upload failed: " . $e->getMessage();
    }

    // Handle Additional Images Multi-Upload
    $additionalImagesArr = is_string($currentAddl) ? json_decode($currentAddl, true) : [];
    if (!is_array($additionalImagesArr)) $additionalImagesArr = [];

    if (isset($_FILES['additional_files']) && !empty($_FILES['additional_files']['name'][0])) {
        $filesCount = count($_FILES['additional_files']['name']);
        for ($i = 0; $i < $filesCount; $i++) {
            if ($_FILES['additional_files']['error'][$i] === UPLOAD_ERR_OK) {
                $_FILES['single_addl_file'] = [
                    'name' => $_FILES['additional_files']['name'][$i],
                    'type' => $_FILES['additional_files']['type'][$i],
                    'tmp_name' => $_FILES['additional_files']['tmp_name'][$i],
                    'error' => $_FILES['additional_files']['error'][$i],
                    'size' => $_FILES['additional_files']['size'][$i]
                ];
                try {
                    $uploadedAddl = handleAdminImageUpload('single_addl_file', 'products');
                    if ($uploadedAddl) {
                        $additionalImagesArr[] = $uploadedAddl;
                    }
                } catch (Exception $e) {
                    error_log("Additional image upload error: " . $e->getMessage());
                }
            }
        }
    }

    $additionalImagesJson = json_encode(array_values(array_unique($additionalImagesArr)));

    if (empty($title)) {
        $error = "Product title is required.";
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE products SET title = ?, slug = ?, short_description = ?, full_description = ?, price = ?, original_price = ?, discount_percent = ?, badge_text = ?, image = ?, additional_images = ?, category = ?, in_stock = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $price, $origPrice, $discountPercent, $badgeText, $currentImage, $additionalImagesJson, $category, $inStock, $sortOrder, $isActive, $editId]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO products (title, slug, short_description, full_description, price, original_price, discount_percent, badge_text, image, additional_images, category, in_stock, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $slug, $shortDesc, $fullDesc, $price, $origPrice, $discountPercent, $badgeText, $currentImage, $additionalImagesJson, $category, $inStock, $sortOrder, $isActive]);
            }
            header("Location: " . BASE_URL . "admin/products.php?msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Item for Edit Form
$editItem = null;
if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Products for Table View
$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY sort_order ASC, created_at DESC");
    $products = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
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
            <h2 class="admin-card-title"><?php echo ($editId > 0) ? 'Edit Product' : 'Add New Product'; ?></h2>
            <a href="<?php echo BASE_URL; ?>admin/products.php" class="btn-admin btn-admin-secondary">← Back to Products List</a>
        </div>

        <form action="products.php?action=<?php echo $action; ?>&id=<?php echo $editId; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">
            <input type="hidden" name="current_additional" value="<?php echo htmlspecialchars($editItem['additional_images'] ?? '[]'); ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label class="form-label">Product Title *</label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required placeholder="e.g. 7 Chakra Healing Crystal Bracelet">
                </div>

                <div class="form-group">
                    <label class="form-label">URL Slug (Leave blank to auto-generate)</label>
                    <input type="text" name="slug" class="form-input" value="<?php echo htmlspecialchars($editItem['slug'] ?? ''); ?>" placeholder="7-chakra-bracelet">
                </div>

                <div class="form-group">
                    <label class="form-label">Sale Price (INR) *</label>
                    <input type="number" step="0.01" name="price" class="form-input" value="<?php echo htmlspecialchars($editItem['price'] ?? ''); ?>" required placeholder="1299.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Original Price (INR) — For Strikethrough</label>
                    <input type="number" step="0.01" name="original_price" class="form-input" value="<?php echo htmlspecialchars($editItem['original_price'] ?? ''); ?>" placeholder="1999.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="Bracelets" <?php echo (($editItem['category'] ?? '') === 'Bracelets') ? 'selected' : ''; ?>>Bracelets</option>
                        <option value="Pendulums" <?php echo (($editItem['category'] ?? '') === 'Pendulums') ? 'selected' : ''; ?>>Pendulums</option>
                        <option value="Stones" <?php echo (($editItem['category'] ?? '') === 'Stones') ? 'selected' : ''; ?>>Stones & Pyramids</option>
                        <option value="Hangings" <?php echo (($editItem['category'] ?? '') === 'Hangings') ? 'selected' : ''; ?>>Space Hangings</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Badge Text</label>
                    <input type="text" name="badge_text" class="form-input" value="<?php echo htmlspecialchars($editItem['badge_text'] ?? 'Reiki Charged'); ?>" placeholder="Reiki Charged">
                </div>

                <div class="form-group">
                    <label class="form-label">Primary Image File</label>
                    <input type="file" name="image_file" class="form-input" accept="image/*">
                    <?php if (!empty($editItem['image'])): ?>
                        <div style="margin-top: 8px;">
                            <img src="<?php echo BASE_URL . htmlspecialchars($editItem['image']); ?>" width="80" height="60" style="object-fit: cover; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Additional Gallery Images (Multi-select)</label>
                    <input type="file" name="additional_files[]" class="form-input" accept="image/*" multiple>
                </div>

                <div class="form-group">
                    <label class="form-label">In Stock?</label>
                    <select name="in_stock" class="form-select">
                        <option value="1" <?php echo (($editItem['in_stock'] ?? 1) == 1) ? 'selected' : ''; ?>>In Stock</option>
                        <option value="0" <?php echo (($editItem['in_stock'] ?? 1) == 0) ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?php echo (($editItem['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (($editItem['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Short Summary *</label>
                    <textarea name="short_description" class="form-textarea" style="min-height: 80px;" required><?php echo htmlspecialchars($editItem['short_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Full Product Description & Metaphysical Properties</label>
                    <textarea name="full_description" class="form-textarea" style="min-height: 160px;"><?php echo htmlspecialchars($editItem['full_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Product →</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <!-- LIST TABLE VIEW -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">All Crystal Products (<?php echo count($products); ?>)</h2>
            <a href="products.php?action=add" class="btn-admin btn-admin-primary">➕ Add New Product</a>
        </div>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Sale Price</th>
                        <th>Orig. Price</th>
                        <th>Discount</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $prd): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($prd['image'] ?: 'assets/images/products/amethyst-bracelet.jpg'); ?>" width="54" height="42" style="object-fit: cover; border-radius: 8px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prd['title']); ?></strong>
                                    <span style="display: block; font-size: 0.78rem; color: var(--admin-muted);"><?php echo htmlspecialchars($prd['badge_text']); ?></span>
                                </td>
                                <td><span style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($prd['category']); ?></span></td>
                                <td><strong>₹<?php echo number_format($prd['price'], 2); ?></strong></td>
                                <td style="color: var(--admin-muted);">
                                    <?php echo ($prd['original_price']) ? '₹' . number_format($prd['original_price'], 2) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo ($prd['discount_percent'] > 0) ? '<span class="badge-status badge-unread">' . $prd['discount_percent'] . '% OFF</span>' : '-'; ?>
                                </td>
                                <td>
                                    <?php echo ($prd['in_stock']) ? '<span class="badge-status badge-completed">In Stock</span>' : '<span class="badge-status badge-unread">Out of Stock</span>'; ?>
                                </td>
                                <td>
                                    <?php echo ($prd['is_active']) ? '<span class="badge-status badge-completed">Active</span>' : '<span class="badge-status badge-unread">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <a href="products.php?action=edit&id=<?php echo $prd['id']; ?>" class="btn-admin btn-admin-secondary btn-admin-sm">Edit</a>
                                    <a href="products.php?action=delete&id=<?php echo $prd['id']; ?>" class="btn-admin btn-admin-sm" style="background-color: #fee2e2; color: #dc2626;" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--admin-muted); padding: 30px;">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
