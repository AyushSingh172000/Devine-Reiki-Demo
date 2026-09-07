<?php
// Blog Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Healing Insights & Spiritual Blog | Reiki Bliss";
$pageDescription = "Read authentic articles on Reiki healing, chakra unblocking, crystal energy frequencies, and holistic wellness guidance.";

// Pagination settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

// Fetch Total Posts Count
$totalPosts = 0;
try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE is_published = 1");
    $totalPosts = (int)($countStmt ? $countStmt->fetchColumn() : 0);
} catch (PDOException $e) {
    error_log("Database count error in blog.php: " . $e->getMessage());
}

$totalPages = max(1, ceil($totalPosts / $perPage));

// Fetch Published Blog Posts
$posts = [];
try {
    $sql = "SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $pdo->query($sql);
    $posts = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database query error in blog.php: " . $e->getMessage());
}

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION
     ========================================================================== -->
<section class="blog-hero" id="blog-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="blog-hero-badge">Spiritual Knowledge</span>
        <h1 class="blog-hero-title">Healing <em>Insights</em></h1>
        <p class="blog-hero-subtext">
            Explore articles on Reiki energy medicine, chakra balancing, crystal frequencies, and holistic spiritual well-being written by experienced Master Healers.
        </p>
    </div>
</section>

<!-- ==========================================================================
     2. BLOG POSTS GRID & PAGINATION
     ========================================================================== -->
<section class="blog-grid-section" id="blog-grid">
    <div class="container">
        
        <div class="blog-cards-grid">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <?php 
                    $tags = is_string($post['tags']) ? json_decode($post['tags'], true) : $post['tags'];
                    $formattedDate = date('F j, Y', strtotime($post['published_at']));
                    ?>
                    <article class="blog-post-card animate-on-scroll">
                        <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="blog-img-box">
                            <img src="<?php echo htmlspecialchars($post['image'] ?: 'assets/images/blog/reiki-guide.jpg'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy">
                        </a>

                        <div class="blog-content-body">
                            <div>
                                <div class="blog-meta-row">
                                    <span>📅 <?php echo $formattedDate; ?></span>
                                    <span>✍️ <?php echo htmlspecialchars($post['author'] ?: 'Divine Reiki'); ?></span>
                                </div>

                                <h3 class="blog-post-title">
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>

                                <p class="blog-post-excerpt">
                                    <?php echo htmlspecialchars($post['excerpt']); ?>
                                </p>
                            </div>

                            <div>
                                <?php if (!empty($tags) && is_array($tags)): ?>
                                    <div class="blog-tags-row">
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="blog-tag-badge">#<?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="blog-card-footer">
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-link">
                                        Read Full Article →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 0;">
                    <p style="font-size: 1.1rem; color: var(--muted-gray);">No blog articles found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <!-- Previous Page Link -->
                <a href="?page=<?php echo ($page - 1); ?>" 
                   class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" aria-label="Previous Page">
                    ←
                </a>

                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="pagination-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next Page Link -->
                <a href="?page=<?php echo ($page + 1); ?>" 
                   class="pagination-link <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>" aria-label="Next Page">
                    →
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ==========================================================================
     3. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
