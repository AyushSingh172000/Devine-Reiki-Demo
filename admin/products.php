<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
require_once 'upload-helper.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? 'All');
$error = '';

$categories = ['Bracelets', 'Pendulums', 'Stones', 'Hangings', 'Other'];

// =========================================================================
// 1. HANDLE POST ACTIONS (Delete, Insert, Update)
// =========================================================================

// A. DELETE PRODUCT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare("SELECT image, additional_images FROM products WHERE id = ?");
            $stmt->execute([$deleteId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // Delete main image
                if (!empty($product['image'])) {
                    deleteImage($product['image']);
                }
                // Delete additional images
                if (!empty($product['additional_images'])) {
                    $extraImgs = json_decode($product['additional_images'], true);
                    if (is_array($extraImgs)) {
                        foreach ($extraImgs as $img) {
                            deleteImage($img);
                        }
                    }
                }
            }

            $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $delStmt->execute([$deleteId]);

            $_SESSION['flash_success'] = "Product deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting product: " . $e->getMessage();
        }
    }
    header("Location: products.php");
    exit;
}

// B. SAVE PRODUCT (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = generateSlug($title);
    } else {
        $slug = generateSlug($slug);
    }

    $category = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'Bracelets';
    $shortDesc = trim($_POST['short_description'] ?? '');
    $fullDesc = trim($_POST['full_description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    
    // Auto-calculate discount if original price is greater than selling price
    if ($originalPrice && $originalPrice > $price && $originalPrice > 0) {
        $discountPercent = (int)round((1 - ($price / $originalPrice)) * 100);
    } else {
        $discountPercent = (int)($_POST['discount_percent'] ?? 0);
    }

    $badgeText = trim($_POST['badge_text'] ?? 'Reiki Charged');
    $inStock = isset($_POST['in_stock']) ? 1 : 0;
    $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : (int)($editItem['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? '';

    // Remove main image if requested
    if (isset($_POST['remove_main_image']) && $_POST['remove_main_image'] == '1') {
        if (!empty($currentImage)) {
            deleteImage($currentImage);
            $currentImage = null;
        }
    }

    // Handle Main Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = uploadImage($_FILES['image'], '../uploads/products/');
        if ($uploadRes['success']) {
            if (!empty($currentImage) && $currentImage !== $uploadRes['path']) {
                deleteImage($currentImage);
            }
            $currentImage = $uploadRes['path'];
        } else {
            $error = "Main image upload error: " . $uploadRes['error'];
        }
    }

    // Process Existing Additional Images (keep those not marked for removal)
    $existingAddImages = [];
    if (!empty($_POST['existing_additional_images']) && is_array($_POST['existing_additional_images'])) {
        $removeAddImgs = $_POST['remove_additional_images'] ?? [];
        foreach ($_POST['existing_additional_images'] as $img) {
            if (in_array($img, $removeAddImgs)) {
                deleteImage($img);
            } else {
                $existingAddImages[] = $img;
            }
        }
    }

    // Process New Additional Images (multi-upload up to 5 total)
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        $fileCount = count($_FILES['additional_images']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if (count($existingAddImages) >= 5) {
                break; // Max 5 additional images
            }
            if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileItem = [
                    'name'     => $_FILES['additional_images']['name'][$i],
                    'type'     => $_FILES['additional_images']['type'][$i],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                    'error'    => $_FILES['additional_images']['error'][$i],
                    'size'     => $_FILES['additional_images']['size'][$i],
                ];
                $upRes = uploadImage($fileItem, '../uploads/products/');
                if ($upRes['success']) {
                    $existingAddImages[] = $upRes['path'];
                }
            }
        }
    }

    $additionalImagesJson = !empty($existingAddImages) ? json_encode(array_values($existingAddImages)) : null;

    if (empty($title)) {
        $error = "Product title is required.";
    }
    if ($price <= 0) {
        $error = "Please provide a valid product price.";
    }

    // Ensure slug uniqueness
    if (empty($error)) {
        $checkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $editId]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . time();
        }
    }

    if (empty($error)) {
        try {
            if ($editId > 0) {
                // UPDATE
                $updateSql = "UPDATE products SET 
                    title = ?, 
                    slug = ?, 
                    short_description = ?, 
                    full_description = ?, 
                    price = ?, 
                    original_price = ?, 
                    discount_percent = ?, 
                    badge_text = ?, 
                    image = ?, 
                    additional_images = ?, 
                    category = ?, 
                    in_stock = ?, 
                    sort_order = ?, 
                    is_active = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([
                    $title, $slug, $shortDesc, $fullDesc, $price, $originalPrice, 
                    $discountPercent, $badgeText, $currentImage, $additionalImagesJson, 
                    $category, $inStock, $sortOrder, $isActive, $editId
                ]);
                $_SESSION['flash_success'] = "Product updated successfully!";
            } else {
                // INSERT
                $insertSql = "INSERT INTO products 
                    (title, slug, short_description, full_description, price, original_price, discount_percent, badge_text, image, additional_images, category, in_stock, sort_order, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    $title, $slug, $shortDesc, $fullDesc, $price, $originalPrice, 
                    $discountPercent, $badgeText, $currentImage, $additionalImagesJson, 
                    $category, $inStock, $sortOrder, $isActive
                ]);
                $_SESSION['flash_success'] = "New product added successfully!";
            }
            header("Location: products.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error saving product: " . $e->getMessage();
        }
    }
}

// =========================================================================
// 2. FETCH DATA DEPENDING ON VIEW
// =========================================================================

$editItem = null;
$existingAdditionalImages = [];

if (($action === 'edit' || $action === 'add') && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editItem) {
        if (!empty($editItem['additional_images'])) {
            $existingAdditionalImages = json_decode($editItem['additional_images'], true) ?: [];
        }
    } elseif ($action === 'edit') {
        $_SESSION['flash_error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }
}

// Set Page Title
if ($action === 'add') {
    $pageTitle = 'Add Product';
} elseif ($action === 'edit') {
    $pageTitle = 'Edit Product';
} else {
    $pageTitle = 'Products';
}

require_once 'includes/admin-header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- =========================================================================
     ADD / EDIT VIEW
     ========================================================================= -->
<div style="max-width: 900px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <a href="products.php" class="btn btn-outline btn-sm flex items-center gap-1">
            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Back to Products
        </a>
        <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--text-primary);">
            <?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?>
        </h2>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error flex items-center gap-2">
            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <form action="products.php?action=<?= $action ?>&id=<?= $editId ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_product" value="1">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>">

            <div class="form-row">
                <!-- Title -->
                <div class="form-group">
                    <label for="productTitle" class="form-label">Product Title <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="productTitle" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g. 7 Chakra Healing Crystal Bracelet" 
                        value="<?= htmlspecialchars($_POST['title'] ?? ($editItem['title'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="productSlug" class="form-label">URL Slug</label>
                    <input 
                        type="text" 
                        id="productSlug" 
                        name="slug" 
                        class="form-control" 
                        placeholder="7-chakra-healing-crystal-bracelet"
                        value="<?= htmlspecialchars($_POST['slug'] ?? ($editItem['slug'] ?? '')) ?>"
                    >
                    <div class="form-hint">Auto-generated or enter custom identifier.</div>
                </div>
            </div>

            <div class="form-row">
                <!-- Category -->
                <div class="form-group">
                    <label for="productCategory" class="form-label">Category <span class="text-danger">*</span></label>
                    <select id="productCategory" name="category" class="form-control" required>
                        <?php 
                            $currentCat = $_POST['category'] ?? ($editItem['category'] ?? 'Bracelets');
                            foreach ($categories as $cat): 
                        ?>
                            <option value="<?= $cat ?>" <?= $currentCat === $cat ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Badge Text -->
                <div class="form-group">
                    <label for="badgeText" class="form-label">Product Badge Text</label>
                    <input 
                        type="text" 
                        id="badgeText" 
                        name="badge_text" 
                        class="form-control" 
                        placeholder="e.g. Reiki Charged, Best Seller"
                        value="<?= htmlspecialchars($_POST['badge_text'] ?? ($editItem['badge_text'] ?? 'Reiki Charged')) ?>"
                    >
                </div>
            </div>

            <div class="form-row form-row-prices" style="grid-template-columns: repeat(3, 1fr);">
                <!-- Price -->
                <div class="form-group">
                    <label for="productPrice" class="form-label">Selling Price (₹) <span class="text-danger">*</span></label>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="productPrice" 
                        name="price" 
                        class="form-control" 
                        placeholder="e.g. 899.00" 
                        value="<?= htmlspecialchars($_POST['price'] ?? ($editItem['price'] ?? '')) ?>" 
                        required
                    >
                </div>

                <!-- Original Price -->
                <div class="form-group">
                    <label for="originalPrice" class="form-label">Original Price (₹)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="originalPrice" 
                        name="original_price" 
                        class="form-control" 
                        placeholder="e.g. 1299.00" 
                        value="<?= htmlspecialchars($_POST['original_price'] ?? ($editItem['original_price'] ?? '')) ?>"
                    >
                    <div class="form-hint">Shown with strikethrough.</div>
                </div>

                <!-- Discount Percent -->
                <div class="form-group">
                    <label for="discountPercent" class="form-label">Discount (%)</label>
                    <input 
                        type="number" 
                        id="discountPercent" 
                        name="discount_percent" 
                        class="form-control" 
                        placeholder="e.g. 30" 
                        value="<?= htmlspecialchars($_POST['discount_percent'] ?? ($editItem['discount_percent'] ?? '0')) ?>"
                    >
                    <div class="form-hint">Auto-calculated or custom.</div>
                </div>
            </div>

            <!-- Short Description -->
            <div class="form-group">
                <div class="flex-between">
                    <label for="productShortDesc" class="form-label">Short Description</label>
                    <span class="char-counter-text" id="prodShortCounter">0 / 200</span>
                </div>
                <textarea 
                    id="productShortDesc" 
                    name="short_description" 
                    class="form-control" 
                    rows="2" 
                    maxlength="200"
                    placeholder="Brief highlights for store card listings..."
                ><?= htmlspecialchars($_POST['short_description'] ?? ($editItem['short_description'] ?? '')) ?></textarea>
            </div>

            <!-- Full Description with Toolbar -->
            <div class="form-group">
                <label for="productFullDesc" class="form-label">Full Description (HTML Supported)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertTag('productFullDesc', '<b>', '</b>')" title="Bold"><i data-lucide="bold" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('productFullDesc', '<i>', '</i>')" title="Italic"><i data-lucide="italic" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('productFullDesc', '<h3>', '</h3>')" title="Heading 3"><i data-lucide="heading-3" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('productFullDesc', '<ul>\n  <li>', '</li>\n</ul>')" title="List"><i data-lucide="list" style="width: 14px; height: 14px;"></i></button>
                    <button type="button" onclick="insertTag('productFullDesc', '<p>', '</p>')" title="Paragraph"><i data-lucide="pilcrow" style="width: 14px; height: 14px;"></i></button>
                </div>
                <textarea 
                    id="productFullDesc" 
                    name="full_description" 
                    class="form-control has-toolbar" 
                    style="min-height: 180px;"
                    placeholder="Crystal specifications, spiritual properties, bead diameter, energetic activation guidelines..."
                ><?= htmlspecialchars($_POST['full_description'] ?? ($editItem['full_description'] ?? '')) ?></textarea>
            </div>

            <!-- Main Featured Image -->
            <div class="form-group">
                <label class="form-label">Primary Featured Image</label>
                
                <div class="upload-area" onclick="document.getElementById('productMainImg').click()">
                    <div class="upload-icon mb-1">
                        <i data-lucide="gem" style="width: 36px; height: 36px; color: var(--gold);"></i>
                    </div>
                    <p style="font-weight: 500; margin-bottom: 4px;">Upload main product cover photo</p>
                    <p class="text-muted" style="font-size: 0.8rem;">Supported: JPG, PNG, WEBP (Max 5MB)</p>
                </div>
                
                <input 
                    type="file" 
                    id="productMainImg" 
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
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Product Cover Preview">
                        <button type="button" class="remove-preview flex items-center justify-center" onclick="removePreview(this)" title="Remove image">
                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($editItem['image'])): ?>
                    <label class="switch-toggle-label mt-2" style="font-size: 0.82rem; color: var(--error);">
                        <input type="checkbox" name="remove_main_image" value="1">
                        Remove cover photo
                    </label>
                <?php endif; ?>
            </div>

            <!-- Additional Images Gallery (Multi-upload up to 5) -->
            <div class="form-group">
                <label class="form-label">Additional Product Gallery Images (Up to 5 images)</label>

                <!-- Existing Images with removal checkboxes -->
                <?php if (!empty($existingAdditionalImages)): ?>
                    <p class="text-muted" style="font-size: 0.82rem; margin-bottom: 8px;">Existing Gallery Photos (Check to remove):</p>
                    <div class="multi-images-grid mb-3">
                        <?php foreach ($existingAdditionalImages as $img): ?>
                            <?php 
                                $extraSrc = strpos($img, 'assets/') === 0 || strpos($img, 'uploads/') === 0 ? '../' . $img : $img;
                            ?>
                            <div class="multi-img-thumb">
                                <img src="<?= htmlspecialchars($extraSrc) ?>" alt="Product gallery thumbnail">
                                <input type="hidden" name="existing_additional_images[]" value="<?= htmlspecialchars($img) ?>">
                                <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.75); color: #f87171; font-size: 0.7rem; text-align: center; padding: 2px; cursor: pointer;">
                                    <input type="checkbox" name="remove_additional_images[]" value="<?= htmlspecialchars($img) ?>"> Remove
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="upload-area" onclick="document.getElementById('multiImgInput').click()" style="padding: 24px;">
                    <div class="upload-icon mb-1">
                        <i data-lucide="images" style="width: 28px; height: 28px; color: var(--gold);"></i>
                    </div>
                    <p style="font-weight: 500; font-size: 0.9rem; margin-bottom: 2px;">Add more images to product gallery</p>
                    <p class="text-muted" style="font-size: 0.78rem;">You can select multiple files</p>
                </div>
                <input 
                    type="file" 
                    id="multiImgInput" 
                    name="additional_images[]" 
                    multiple 
                    accept="image/jpeg,image/png,image/webp" 
                    style="display: none;"
                >
                <!-- Multi Preview Container for newly selected files -->
                <div id="newMultiPreview" class="multi-images-grid"></div>
            </div>

            <div class="form-row">
                <!-- In Stock Toggle -->
                <div class="form-group">
                    <label class="form-label">Stock Status</label>
                    <label class="switch-toggle-label">
                        <input 
                            type="checkbox" 
                            name="in_stock" 
                            value="1" 
                            <?= (!isset($editItem) || !empty($editItem['in_stock'])) ? 'checked' : '' ?>
                        >
                        <span>In Stock (Available for ordering)</span>
                    </label>
                </div>

                <!-- Is Active Visibility -->
                <div class="form-group">
                    <label class="form-label">Public Visibility</label>
                    <label class="switch-toggle-label">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            value="1" 
                            <?= (!isset($editItem) || !empty($editItem['is_active'])) ? 'checked' : '' ?>
                        >
                        <span>Active (Displayed on public shop)</span>
                    </label>
                </div>
            </div>

            <!-- Sort Order Field (Commented out)
            <div class="form-group" style="max-width: 260px;">
                <label for="prodSortOrder" class="form-label">Sort Order</label>
                <input 
                    type="number" 
                    id="prodSortOrder" 
                    name="sort_order" 
                    class="form-control" 
                    placeholder="0" 
                    value="<?= htmlspecialchars($_POST['sort_order'] ?? ($editItem['sort_order'] ?? '0')) ?>"
                >
                <div class="form-hint">Controls position on the website. Lower numbers (1, 2, 3...) appear first.</div>
            </div>
            -->

            <!-- Form Actions -->
            <div class="flex gap-2 mt-3" style="border-top: 1px solid var(--card-border); padding-top: 20px;">
                <button type="submit" class="btn btn-gold">
                    Save Product
                </button>
                <a href="products.php" class="btn btn-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Auto Slug Generation
const titleInput = document.getElementById('productTitle');
const slugInput = document.getElementById('productSlug');
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

// Character Counter
const prodShortDesc = document.getElementById('productShortDesc');
const prodShortCounter = document.getElementById('prodShortCounter');
if (prodShortDesc && prodShortCounter) {
    const updateProdCounter = () => {
        prodShortCounter.textContent = `${prodShortDesc.value.length} / 200`;
    };
    prodShortDesc.addEventListener('input', updateProdCounter);
    updateProdCounter();
}

// Auto-Calculate Discount Percent
const priceInput = document.getElementById('productPrice');
const origPriceInput = document.getElementById('originalPrice');
const discountInput = document.getElementById('discountPercent');

function calculateDiscount() {
    const price = parseFloat(priceInput.value);
    const origPrice = parseFloat(origPriceInput.value);

    if (!isNaN(price) && !isNaN(origPrice) && origPrice > price && origPrice > 0) {
        const discount = Math.round((1 - (price / origPrice)) * 100);
        discountInput.value = discount;
    }
}

if (priceInput && origPriceInput && discountInput) {
    priceInput.addEventListener('input', calculateDiscount);
    origPriceInput.addEventListener('input', calculateDiscount);
}

// Multi-image selection preview
const multiImgInput = document.getElementById('multiImgInput');
const newMultiPreview = document.getElementById('newMultiPreview');

if (multiImgInput && newMultiPreview) {
    multiImgInput.addEventListener('change', function() {
        newMultiPreview.innerHTML = '';
        if (this.files) {
            Array.from(this.files).slice(0, 5).forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const thumb = document.createElement('div');
                    thumb.className = 'multi-img-thumb';
                    thumb.innerHTML = `<img src="${e.target.result}" alt="New Preview"><span style="position:absolute; bottom:2px; left:2px; font-size:0.65rem; background:rgba(0,0,0,0.6); padding:1px 4px; border-radius:3px; color:#4ade80;">New</span>`;
                    newMultiPreview.appendChild(thumb);
                };
                reader.readAsDataURL(file);
            });
        }
    });
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
    $whereParts = [];
    $params = [];

    if (!empty($search)) {
        $whereParts[] = "(title LIKE ? OR short_description LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if (!empty($categoryFilter) && $categoryFilter !== 'All') {
        $whereParts[] = "category = ?";
        $params[] = $categoryFilter;
    }

    $whereClause = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';
    $sql = "SELECT * FROM products {$whereClause} ORDER BY sort_order ASC, created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productsList = [];
    $error = "Error fetching products: " . $e->getMessage();
}
?>

<!-- Scoped Products Styling -->
<style>
.products-table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
}
.products-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
}
.products-table thead th {
    font-size: 0.74rem;
    padding: 12px 14px;
    letter-spacing: 0.5px;
    background: #f8fafc;
    border-bottom: 1px solid var(--card-border);
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}
.products-table tbody td {
    padding: 12px 14px;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f5f9;
}
.products-table tbody tr {
    transition: background-color 0.15s ease;
}
.products-table tbody tr:hover {
    background: #fafbfc;
}
.products-table tbody tr:last-child td {
    border-bottom: none;
}

/* Product Cell (Thumbnail + Title + Details) */
.prod-cell-main {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 220px;
}
.prod-thumb-box {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--card-border);
    flex-shrink: 0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.prod-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.prod-info-box {
    flex: 1;
    min-width: 0;
}
.prod-title-text {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.92rem;
    line-height: 1.35;
    margin-bottom: 4px;
    word-break: break-word;
}
.prod-meta-tags {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

/* Category Badge */
.badge-category {
    background: rgba(99, 102, 241, 0.08);
    color: var(--purple-accent);
    border: 1px solid rgba(99, 102, 241, 0.2);
    font-weight: 600;
    font-size: 0.74rem;
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-block;
    white-space: nowrap;
}

/* Consolidated Pricing Cell */
.prod-pricing-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.prod-current-price {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--gold-dark);
    line-height: 1.2;
}
.prod-sub-price {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}
.prod-orig-price {
    font-size: 0.76rem;
    color: var(--text-muted);
    text-decoration: line-through;
}
.prod-discount-badge {
    font-size: 0.67rem;
    font-weight: 700;
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    padding: 1px 5px;
    border-radius: 4px;
    line-height: 1.2;
}

/* Consolidated Status Stack */
.prod-status-stack {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-start;
}
.badge-subtle-pill {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    line-height: 1.3;
}
.badge-active-subtle {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.badge-inactive-subtle {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.badge-instock-subtle {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-weight: 500;
}
.badge-outstock-subtle {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
    font-weight: 600;
}
.dot-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.dot-green {
    background: #16a34a;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
}
.dot-red {
    background: #dc2626;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2);
}

/* Responsive Rules */
@media (max-width: 680px) {
    .search-bar.flex-between {
        flex-direction: column;
        align-items: stretch !important;
        gap: 12px;
    }
    .search-input-wrapper {
        max-width: 100% !important;
        width: 100% !important;
    }
    .filter-select {
        width: 100% !important;
    }
    .btn-gold {
        width: 100% !important;
        justify-content: center;
    }
    .form-row-prices {
        grid-template-columns: 1fr !important;
    }
}
</style>

<!-- Search Bar & Filters -->
<div class="search-bar flex-between mb-3" style="align-items: center; gap: 14px; flex-wrap: wrap;">
    <div class="flex gap-2" style="flex: 1; flex-wrap: wrap; align-items: center;">
        <!-- Search Input Form -->
        <form action="products.php" method="GET" class="search-input-wrapper" style="min-width: 250px; max-width: 360px;">
            <i data-lucide="search" class="search-icon" style="width: 16px; height: 16px;"></i>
            <input 
                type="text" 
                name="search" 
                class="search-input" 
                placeholder="Search products by title..." 
                value="<?= htmlspecialchars($search) ?>"
            >
            <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
            <?php if (!empty($search)): ?>
                <a href="products.php?category=<?= urlencode($categoryFilter) ?>" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 2px;">
                    <i data-lucide="x" style="width: 12px; height: 12px;"></i> Clear
                </a>
            <?php endif; ?>
        </form>

        <!-- Category Dropdown Filter -->
        <form action="products.php" method="GET" id="catFilterForm" style="display: flex; align-items: center;">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="All" <?= $categoryFilter === 'All' ? 'selected' : '' ?>>All Categories (<?= count($productsList) ?>)</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                        <?= $cat ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <a href="products.php?action=add" class="btn btn-gold flex items-center gap-1">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add New Product
    </a>
</div>

<div class="admin-card" style="padding: 16px 20px;">
    <?php if (empty($productsList)): ?>
        <div style="text-align: center; padding: 40px 20px;">
            <div style="margin-bottom: 12px;">
                <i data-lucide="shopping-bag" style="width: 48px; height: 48px; color: var(--gold); opacity: 0.8;"></i>
            </div>
            <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 6px;">No products found</h3>
            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 18px;">
                <?= (!empty($search) || $categoryFilter !== 'All') ? 'No products match your current filters.' : 'You have not added any products to the shop catalog yet.' ?>
            </p>
            <a href="products.php?action=add" class="btn btn-gold btn-sm flex items-center gap-1" style="display: inline-flex;">
                <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add First Product
            </a>
        </div>
    <?php else: ?>
        <div class="products-table-wrapper">
            <table class="admin-table products-table">
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Product</th>
                        <th style="width: 110px; white-space: nowrap;">Category</th>
                        <th style="width: 125px; white-space: nowrap;">Price</th>
                        <th style="width: 115px; white-space: nowrap;">Status & Stock</th>
                        <th style="text-align: right; width: 115px; padding-right: 14px; white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productsList as $prod): ?>
                        <?php 
                            $thumb = !empty($prod['image']) 
                                ? (strpos($prod['image'], 'assets/') === 0 || strpos($prod['image'], 'uploads/') === 0 ? '../' . $prod['image'] : $prod['image']) 
                                : '../assets/images/products/pyrite-bracelet.jpg';
                            $inStock = !empty($prod['in_stock']);
                            $isActive = !empty($prod['is_active']);
                            $origPrice = (float)($prod['original_price'] ?? 0);
                            $price = (float)($prod['price'] ?? 0);
                            $discount = (int)($prod['discount_percent'] ?? 0);
                        ?>
                        <tr>
                            <!-- 1. Product (Thumbnail + Title + Details) -->
                            <td>
                                <div class="prod-cell-main">
                                    <div class="prod-thumb-box">
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($prod['title']) ?>" class="prod-thumb-img" onerror="this.src='../assets/images/logo.png'">
                                    </div>
                                    <div class="prod-info-box">
                                        <div class="prod-title-text">
                                            <?= htmlspecialchars($prod['title']) ?>
                                        </div>
                                        <div class="prod-meta-tags">
                                            <?php if (!empty($prod['badge_text'])): ?>
                                                <span class="badge badge-gold" style="font-size: 0.68rem; padding: 1px 6px;">
                                                    <?= htmlspecialchars($prod['badge_text']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="text-muted" style="font-size: 0.74rem; font-family: monospace;"><?= htmlspecialchars($prod['slug']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Category -->
                            <td style="white-space: nowrap;">
                                <span class="badge-category">
                                    <?= htmlspecialchars($prod['category'] ?? 'Bracelets') ?>
                                </span>
                            </td>

                            <!-- 3. Consolidated Price (Selling + Original + Discount) -->
                            <td style="white-space: nowrap;">
                                <div class="prod-pricing-cell">
                                    <span class="prod-current-price">₹<?= number_format($price, 2) ?></span>
                                    <?php if ($origPrice > 0): ?>
                                        <div class="prod-sub-price">
                                            <span class="prod-orig-price">₹<?= number_format($origPrice, 2) ?></span>
                                            <?php if ($discount > 0): ?>
                                                <span class="prod-discount-badge"><?= $discount ?>% OFF</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($discount > 0): ?>
                                        <div class="prod-sub-price">
                                            <span class="prod-discount-badge"><?= $discount ?>% OFF</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- 4. Consolidated Status & Stock -->
                            <td style="white-space: nowrap;">
                                <div class="prod-status-stack">
                                    <?php if ($isActive): ?>
                                        <span class="badge-subtle-pill badge-active-subtle">
                                            <span class="dot-indicator dot-green"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-subtle-pill badge-inactive-subtle">
                                            <span class="dot-indicator dot-red"></span> Inactive
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($inStock): ?>
                                        <span class="badge-subtle-pill badge-instock-subtle">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge-subtle-pill badge-outstock-subtle">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- 5. Actions -->
                            <td style="text-align: right; padding-right: 14px; white-space: nowrap;">
                                <div class="flex gap-1" style="justify-content: flex-end;">
                                    <a href="products.php?action=edit&id=<?= $prod['id'] ?>" class="btn btn-purple btn-sm flex items-center gap-1" title="Edit Product">
                                        <i data-lucide="pencil" style="width: 13px; height: 13px;"></i> Edit
                                    </a>
                                    <form action="products.php" method="POST" class="delete-form" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm flex items-center gap-1" title="Delete Product">
                                            <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i> Delete
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
