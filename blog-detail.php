<?php
// Blog Detail Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

$slug = $_GET['slug'] ?? '';

// Fetch single post by slug
$post = null;
if (!empty($slug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in blog-detail.php: " . $e->getMessage());
    }
}

// Fallback if post not found
if (!$post) {
    try {
        $fallbackStmt = $pdo->query("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 1");
        $post = $fallbackStmt ? $fallbackStmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (PDOException $e) {
        $post = null;
    }
}

if (!$post) {
    header("Location: " . BASE_URL . "blog.php");
    exit;
}

// Tags & Dates parsing
$tags = is_string($post['tags']) ? json_decode($post['tags'], true) : $post['tags'];
$formattedDate = date('F j, Y', strtotime($post['published_at']));
$currentArticleUrl = BASE_URL . "blog-detail.php?slug=" . urlencode($post['slug']);

// Share URLs
$waShareUrl = "https://api.whatsapp.com/send?text=" . urlencode($post['title'] . " - " . $currentArticleUrl);
$fbShareUrl = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($currentArticleUrl);
$twShareUrl = "https://twitter.com/intent/tweet?text=" . urlencode($post['title']) . "&url=" . urlencode($currentArticleUrl);

// Fetch related posts
$relatedPosts = [];
try {
    $relStmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id != ? AND is_published = 1 ORDER BY published_at DESC LIMIT 3");
    $relStmt->execute([$post['id']]);
    $relatedPosts = $relStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching related posts: " . $e->getMessage());
}

// Page Metadata
$pageTitle = htmlspecialchars($post['title']) . " | Divine Reiki Insights";
$pageDescription = htmlspecialchars($post['excerpt']);

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="container">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="<?php echo BASE_URL; ?>index.php">Home</a>
        <span class="breadcrumb-separator">›</span>
        <a href="<?php echo BASE_URL; ?>blog.php">Blog</a>
        <span class="breadcrumb-separator">›</span>
        <span><?php echo htmlspecialchars($post['title']); ?></span>
    </nav>
</div>

<!-- Blog Article Detail Section -->
<section class="blog-detail-section" id="blog-detail">
    <div class="container">
        
        <article class="blog-detail-container animate-on-scroll">
            <!-- Full-Width Hero Image -->
            <div class="blog-main-hero-img">
                <img src="<?php echo htmlspecialchars($post['image'] ?: 'assets/images/blog/reiki-guide.jpg'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
            </div>

            <!-- Meta & Title -->
            <div class="blog-detail-header-meta">
                <span>📅 <?php echo $formattedDate; ?></span>
                <span>✍️ Written by <?php echo htmlspecialchars($post['author'] ?: 'Divine Reiki Master'); ?></span>
            </div>

            <h1 class="blog-detail-title"><?php echo htmlspecialchars($post['title']); ?></h1>

            <?php if (!empty($tags) && is_array($tags)): ?>
                <div class="blog-tags-row" style="margin-bottom: 30px;">
                    <?php foreach ($tags as $tag): ?>
                        <span class="blog-tag-badge" style="font-size: 0.82rem; padding: 6px 14px;">#<?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Article Body Content -->
            <div class="blog-body-text">
                <?php 
                $paragraphs = explode("\n", $post['content']);
                foreach ($paragraphs as $para) {
                    $para = trim($para);
                    if (!empty($para)) {
                        echo '<p>' . htmlspecialchars($para) . '</p>';
                    }
                }
                ?>
            </div>

            <!-- Share Buttons Row -->
            <div class="share-buttons-box">
                <span class="share-label">Share Article:</span>
                <a href="<?php echo htmlspecialchars($waShareUrl); ?>" target="_blank" rel="noopener" class="share-btn share-btn-wa">
                    💬 WhatsApp
                </a>
                <a href="<?php echo htmlspecialchars($fbShareUrl); ?>" target="_blank" rel="noopener" class="share-btn share-btn-fb">
                    📘 Facebook
                </a>
                <a href="<?php echo htmlspecialchars($twShareUrl); ?>" target="_blank" rel="noopener" class="share-btn share-btn-tw">
                    🐦 Twitter / X
                </a>
            </div>
        </article>

        <!-- Related Posts Section -->
        <?php if (!empty($relatedPosts)): ?>
            <div class="related-posts-wrapper animate-on-scroll">
                <h3 class="section-heading" style="font-size: 2rem; margin-bottom: 30px;">Related Healing <em>Insights</em></h3>
                <div class="blog-cards-grid">
                    <?php foreach ($relatedPosts as $rel): ?>
                        <?php $relDate = date('F j, Y', strtotime($rel['published_at'])); ?>
                        <article class="blog-post-card">
                            <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>" class="blog-img-box">
                                <img src="<?php echo htmlspecialchars($rel['image'] ?: 'assets/images/blog/reiki-guide.jpg'); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy">
                            </a>
                            <div class="blog-content-body">
                                <div>
                                    <div class="blog-meta-row">
                                        <span>📅 <?php echo $relDate; ?></span>
                                    </div>
                                    <h4 class="blog-post-title" style="font-size: 1.15rem;">
                                        <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo htmlspecialchars($rel['title']); ?>
                                        </a>
                                    </h4>
                                    <p class="blog-post-excerpt" style="font-size: 0.88rem;"><?php echo htmlspecialchars($rel['excerpt']); ?></p>
                                </div>
                                <div class="blog-card-footer">
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>" class="read-more-link" style="font-size: 0.84rem;">
                                        Read Article →
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Reusable CTA Section -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
