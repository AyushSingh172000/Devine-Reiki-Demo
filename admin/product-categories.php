<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';

// Helper: Ensure product_categories table exists & seeded
function ensureProductCategoriesTable(PDO $pdo): void {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS product_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                slug VARCHAR(100) NOT NULL UNIQUE,
                sort_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn();
        if ($cnt === 0) {
            $defaults = ['Bracelets', 'Pendulums', 'Stones', 'Hangings', 'Chakra Bracelets', 'Crystal Bracelets', 'Protection Bracelets', 'Other'];
            $existing = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
            $all = array_unique(array_merge($defaults, $existing));
            $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, 1)");
            $ord = 1;
            foreach ($all as $c) {
                $c = trim($c);
                if (empty($c)) continue;
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $c));
                $slug = trim($slug, '-');
                $stmt->execute([$c, $slug, $ord++]);
            }
        }
    } catch (PDOException $e) {
        error_log("Categories migration error: " . $e->getMessage());
    }
}

ensureProductCategoriesTable($pdo);

$search = trim($_GET['search'] ?? '');
$error = '';
$success = '';

// =========================================================================
// 1. POST ACTIONS (Add, Edit, Delete, Toggle Status)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. SAVE CATEGORY (Add or Edit)
    if ($action === 'save_category') {
        $catId = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $_SESSION['flash_error'] = "Category name cannot be empty.";
        } else {
            if (empty($slug)) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                $slug = trim($slug, '-');
            } else {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $slug));
                $slug = trim($slug, '-');
            }

            try {
                if ($catId > 0) {
                    // Update
                    // Check duplicate name/slug on other rows
                    $check = $pdo->prepare("SELECT id FROM product_categories WHERE (name = ? OR slug = ?) AND id != ?");
                    $check->execute([$name, $slug, $catId]);
                    if ($check->fetch()) {
                        $_SESSION['flash_error'] = "Another category already exists with that name or slug.";
                    } else {
                        // Fetch old name to update existing products if renamed
                        $oldStmt = $pdo->prepare("SELECT name FROM product_categories WHERE id = ?");
                        $oldStmt->execute([$catId]);
                        $oldName = $oldStmt->fetchColumn();

                        $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, slug = ?, sort_order = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$name, $slug, $sortOrder, $isActive, $catId]);

                        if ($oldName && $oldName !== $name) {
                            $updateProds = $pdo->prepare("UPDATE products SET category = ? WHERE category = ?");
                            $updateProds->execute([$name, $oldName]);
                        }

                        $_SESSION['flash_success'] = "Category updated successfully!";
                    }
                } else {
                    // Insert
                    $check = $pdo->prepare("SELECT id FROM product_categories WHERE name = ? OR slug = ?");
                    $check->execute([$name, $slug]);
                    if ($check->fetch()) {
                        $_SESSION['flash_error'] = "Category already exists with that name or slug.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO product_categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $slug, $sortOrder, $isActive]);
                        $_SESSION['flash_success'] = "New category '{$name}' created successfully!";
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
            }
        }
        header("Location: product-categories.php");
        exit;
    }

    // B. TOGGLE ACTIVE STATUS
    if ($action === 'toggle_status') {
        $catId = (int)($_POST['id'] ?? 0);
        if ($catId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE product_categories SET is_active = 1 - is_active WHERE id = ?");
                $stmt->execute([$catId]);
                $_SESSION['flash_success'] = "Category status updated!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error updating category status: " . $e->getMessage();
            }
        }
        header("Location: product-categories.php");
        exit;
    }

    // C. DELETE CATEGORY
    if ($action === 'delete') {
        $catId = (int)($_POST['id'] ?? 0);
        if ($catId > 0) {
            try {
                $nameStmt = $pdo->prepare("SELECT name FROM product_categories WHERE id = ?");
                $nameStmt->execute([$catId]);
                $catName = $nameStmt->fetchColumn();

                if ($catName) {
                    // Check if products use this category
                    $prodCountStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
                    $prodCountStmt->execute([$catName]);
                    $prodCount = (int)$prodCountStmt->fetchColumn();

                    if ($prodCount > 0) {
                        // Reassign products to 'Other' or general
                        $reassign = $pdo->prepare("UPDATE products SET category = 'Other' WHERE category = ?");
                        $reassign->execute([$catName]);
                    }

                    $delStmt = $pdo->prepare("DELETE FROM product_categories WHERE id = ?");
                    $delStmt->execute([$catId]);

                    $_SESSION['flash_success'] = "Category deleted successfully!" . ($prodCount > 0 ? " ({$prodCount} products reassigned to 'Other')" : "");
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error deleting category: " . $e->getMessage();
            }
        }
        header("Location: product-categories.php");
        exit;
    }
}

// =========================================================================
// 2. FETCH CATEGORIES & STATS
// =========================================================================
$where = "";
$params = [];
if (!empty($search)) {
    $where = "WHERE name LIKE ? OR slug LIKE ?";
    $params = ['%' . $search . '%', '%' . $search . '%'];
}

$catSql = "
    SELECT c.*, COUNT(p.id) as product_count 
    FROM product_categories c
    LEFT JOIN products p ON p.category = c.name
    {$where}
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.name ASC
";
$stmt = $pdo->prepare($catSql);
$stmt->execute($params);
$categoriesList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overall stats
$totalCats = (int)$pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn();
$activeCats = (int)$pdo->query("SELECT COUNT(*) FROM product_categories WHERE is_active = 1")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$pageTitle = 'Product Categories Master';
require_once 'includes/admin-header.php';
?>

<div class="admin-content-container">
    <!-- Header Section -->
    <div class="flex items-center justify-between" style="margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div class="flex items-center gap-2">
                <a href="products.php" class="btn btn-outline btn-sm flex items-center gap-1" title="Back to Products Catalog">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Products
                </a>
                <h1 style="font-size: 1.45rem; font-weight: 700; color: var(--text-primary); margin: 0;">
                    Product Category Master
                </h1>
            </div>
            <p class="text-muted" style="font-size: 0.88rem; margin: 4px 0 0 0;">
                Manage crystal shop categories. Categories defined here automatically appear on the shop page filter and product editor.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" class="btn btn-purple flex items-center gap-1" onclick="openCategoryModal()">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Add New Category
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="admin-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(179,139,45,0.12); display: flex; align-items: center; justify-content: center; color: var(--gold);">
                <i data-lucide="tags" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Categories</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: var(--text-primary);"><?= $totalCats ?></div>
            </div>
        </div>

        <div class="admin-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16,185,129,0.12); display: flex; align-items: center; justify-content: center; color: #10b981;">
                <i data-lucide="check-circle-2" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Active On Website</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: #10b981;"><?= $activeCats ?></div>
            </div>
        </div>

        <div class="admin-card" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(124,58,237,0.12); display: flex; align-items: center; justify-content: center; color: #7c3aed;">
                <i data-lucide="shopping-bag" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Products</div>
                <div style="font-size: 1.45rem; font-weight: 700; color: var(--text-primary);"><?= $totalProducts ?></div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="admin-card" style="padding: 16px 20px; margin-bottom: 20px;">
        <form method="GET" action="product-categories.php" class="flex items-center justify-between" style="flex-wrap: wrap; gap: 12px;">
            <div style="position: relative; flex: 1; max-width: 380px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted);"></i>
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Search categories by name or slug..." 
                    value="<?= htmlspecialchars($search) ?>" 
                    style="padding-left: 36px;"
                >
            </div>
            <div class="flex items-center gap-2">
                <?php if (!empty($search)): ?>
                    <a href="product-categories.php" class="btn btn-outline btn-sm">Clear Search</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            </div>
        </form>
    </div>

    <!-- Categories Table Card -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <?php if (empty($categoriesList)): ?>
            <div style="text-align: center; padding: 48px 20px;">
                <i data-lucide="folder-x" style="width: 44px; height: 44px; color: var(--gold); opacity: 0.6; margin-bottom: 12px;"></i>
                <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 6px;">No categories found</h3>
                <p class="text-muted" style="font-size: 0.88rem; max-width: 400px; margin: 0 auto 16px auto;">
                    <?= !empty($search) ? 'No categories matched your search query.' : 'Start organizing your shop by creating your first product category.' ?>
                </p>
                <button type="button" class="btn btn-purple btn-sm" onclick="openCategoryModal()">
                    <i data-lucide="plus-circle" style="width: 14px; height: 14px;"></i> Add Category
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="margin: 0; border: none; border-radius: 0;">
                <table class="admin-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Order</th>
                            <th style="min-width: 180px;">Category Name</th>
                            <th style="min-width: 160px;">URL Slug</th>
                            <th style="width: 130px; text-align: center;">Products</th>
                            <th style="width: 110px; text-align: center;">Status</th>
                            <th style="width: 130px; text-align: right; padding-right: 20px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoriesList as $cat): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">
                                    <?= (int)$cat['sort_order'] ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 0.92rem;">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.74rem;">
                                        Created: <?= date('M d, Y', strtotime($cat['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: rgba(179,139,45,0.08); padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; color: var(--gold-dark); border: 1px solid rgba(179,139,45,0.2);">
                                        <?= htmlspecialchars($cat['slug']) ?>
                                    </code>
                                </td>
                                <td style="text-align: center;">
                                    <a href="products.php?category=<?= urlencode($cat['name']) ?>" class="badge badge-purple" style="text-decoration: none; font-size: 0.8rem; padding: 4px 10px;" title="View products in this category">
                                        <?= (int)$cat['product_count'] ?> Products
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <form method="POST" action="product-categories.php" style="display: inline-block;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;" title="Click to toggle status">
                                            <?php if ($cat['is_active']): ?>
                                                <span class="badge badge-success flex items-center gap-1" style="font-size: 0.75rem;">
                                                    <i data-lucide="check" style="width: 12px; height: 12px;"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-danger flex items-center gap-1" style="font-size: 0.75rem;">
                                                    <i data-lucide="x" style="width: 12px; height: 12px;"></i> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td style="text-align: right; padding-right: 20px; white-space: nowrap;">
                                    <div class="flex gap-1" style="justify-content: flex-end;">
                                        <!-- Edit Category Button -->
                                        <button 
                                            type="button" 
                                            class="btn btn-outline btn-icon btn-sm" 
                                            title="Edit Category"
                                            onclick='editCategory(<?= json_encode($cat) ?>)'
                                        >
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                        </button>

                                        <!-- Delete Category Button -->
                                        <form method="POST" action="product-categories.php" class="delete-form" style="display: inline-block;" data-item-name="<?= htmlspecialchars($cat['name']) ?> Category"<?php if ($cat['product_count'] > 0): ?> data-confirm-message="Are you sure you want to delete <strong style='color: var(--gold, #b38b2d); font-weight: 700;'>&quot;<?= htmlspecialchars($cat['name']) ?>&quot;</strong>?<br><br><span style='color: #f59e0b; font-size: 0.88rem; display: inline-block; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 6px; padding: 6px 12px;'>⚠️ Warning: <?= (int)$cat['product_count'] ?> product(s) in this category will be reassigned to &quot;Other&quot;.</span>"<?php endif; ?>>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-icon btn-sm" title="Delete Category">
                                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
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
</div>

<!-- =========================================================================
     CATEGORY ADD/EDIT MODAL
     ========================================================================= -->
<div id="categoryModal" class="modal-overlay">
    <div class="modal category-modal-dialog">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(179,139,45,0.12); display: flex; align-items: center; justify-content: center; color: var(--gold); flex-shrink: 0;">
                    <i data-lucide="tag" style="width: 18px; height: 18px;"></i>
                </div>
                <h3 id="categoryModalTitle" style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin: 0;">Add New Category</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeCategoryModal()" title="Close dialog">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <form method="POST" action="product-categories.php" id="categoryForm">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" id="catModalId" value="0">

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="catModalName" class="form-label">Category Name <span class="text-danger">*</span></label>
                <input 
                    type="text" 
                    id="catModalName" 
                    name="name" 
                    class="form-control" 
                    placeholder="e.g. Rudraksha, Pyramids, Incense" 
                    required
                    oninput="autoGenerateCatSlug(this.value)"
                >
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="catModalSlug" class="form-label">URL Slug</label>
                <input 
                    type="text" 
                    id="catModalSlug" 
                    name="slug" 
                    class="form-control" 
                    placeholder="e.g. rudraksha, pyramids"
                >
                <div class="form-hint" style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                    Used in web links (e.g. <code>/products?category=rudraksha</code>). Auto-filled if empty.
                </div>
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="catModalOrder" class="form-label">Display Order</label>
                    <input 
                        type="number" 
                        id="catModalOrder" 
                        name="sort_order" 
                        class="form-control" 
                        value="0" 
                        min="0"
                    >
                </div>
                <div class="form-group" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: center;">
                    <label class="form-label" style="margin-bottom: 8px;">Visibility</label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.86rem; color: var(--text-primary);">
                        <input type="checkbox" id="catModalActive" name="is_active" value="1" checked style="width: 17px; height: 17px;">
                        <span>Show on website</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--card-border); padding-top: 16px;">
                <button type="button" class="btn btn-outline" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="btn btn-purple flex items-center gap-1">
                    <i data-lucide="check" style="width: 15px; height: 15px;"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<style>
#categoryModal.modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(5px) !important;
    -webkit-backdrop-filter: blur(5px) !important;
    z-index: 99999 !important;
    display: none;
    align-items: flex-start !important;
    justify-content: center !important;
    padding-top: calc(var(--header-height, 65px) + 42px) !important;
    padding-bottom: 40px !important;
    padding-left: 16px !important;
    padding-right: 16px !important;
    box-sizing: border-box !important;
    overflow-y: auto !important;
    overscroll-behavior: contain !important;
}
#categoryModal.modal-overlay.active {
    display: flex !important;
}
#categoryModal .category-modal-dialog {
    max-width: 480px;
    width: 100%;
    margin: 0 auto !important;
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid var(--card-border);
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.28), 0 0 0 1px rgba(212, 175, 55, 0.1);
    padding: 24px 28px !important;
    box-sizing: border-box !important;
    position: relative !important;
    transform: none !important;
    animation: catModalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
#categoryModal .modal-header {
    display: flex !important;
    flex-direction: row !important;
    justify-content: space-between !important;
    align-items: center !important;
    width: 100% !important;
    margin-bottom: 20px !important;
    border-bottom: 1px solid var(--card-border) !important;
    padding-bottom: 14px !important;
}
#categoryModal .modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
}
#categoryModal .modal-close:hover {
    background: rgba(15, 23, 42, 0.06);
    color: var(--text-primary);
}
@keyframes catModalScaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<script>
function autoGenerateCatSlug(text) {
    const slugInput = document.getElementById('catModalSlug');
    if (!slugInput.dataset.manualEdited) {
        slugInput.value = text.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}

document.getElementById('catModalSlug').addEventListener('input', function() {
    this.dataset.manualEdited = "true";
});

function openCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    document.getElementById('catModalId').value = '0';
    document.getElementById('catModalName').value = '';
    const slugEl = document.getElementById('catModalSlug');
    slugEl.value = '';
    delete slugEl.dataset.manualEdited;
    document.getElementById('catModalOrder').value = '0';
    document.getElementById('catModalActive').checked = true;

    const modal = document.getElementById('categoryModal');
    modal.classList.add('active');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        document.getElementById('catModalName').focus();
    }, 50);
    if (window.lucide) lucide.createIcons();
}

function editCategory(cat) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category: ' + cat.name;
    document.getElementById('catModalId').value = cat.id;
    document.getElementById('catModalName').value = cat.name;
    const slugEl = document.getElementById('catModalSlug');
    slugEl.value = cat.slug;
    slugEl.dataset.manualEdited = "true";
    document.getElementById('catModalOrder').value = cat.sort_order || 0;
    document.getElementById('catModalActive').checked = (parseInt(cat.is_active, 10) === 1);

    const modal = document.getElementById('categoryModal');
    modal.classList.add('active');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        document.getElementById('catModalName').focus();
    }, 50);
    if (window.lucide) lucide.createIcons();
}

function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCategoryModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCategoryModal();
    }
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>
