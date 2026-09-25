<?php
// Gallery Page - Disabled / Commented Out
require_once __DIR__ . '/config/constants.php';
header('Location: ' . BASE_URL, true, 302);
exit;

/* =========================================================================
   GALLERY PAGE (COMMENTED OUT)
   =========================================================================

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Spiritual Sanctuary Gallery | Reiki Bliss";
$pageDescription = "View photo gallery of our Reiki attunement workshops, healing sanctuary, group sound meditations, and crystal energy layouts.";

// Fetch Gallery Images from MySQL
try {
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC");
    $galleryImages = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error in gallery.php: " . $e->getMessage());
    $galleryImages = [];
}

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION
     ========================================================================== -->
<section class="gallery-hero" id="gallery-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="gallery-hero-badge">Spiritual Sanctuary</span>
        <h1 class="gallery-hero-title">Our <em>Gallery</em></h1>
        <p class="gallery-hero-subtext">
            Explore moments of spiritual transformation, attunement workshops, crystal energy layouts, and peaceful sanctuary spaces at Reiki Bliss.
        </p>
    </div>
</section>

<!-- ==========================================================================
     2. GALLERY MASONRY SECTION & FILTER TABS
     ========================================================================== -->
<section class="gallery-section" id="gallery-grid">
    <div class="container">

        <!-- Category Filter Tabs -->
        <div class="gallery-filter-tabs" id="gallery-filter-tabs">
            <button class="gallery-filter-btn active" data-filter="all">All Photos</button>
            <button class="gallery-filter-btn" data-filter="sessions">Sessions</button>
            <button class="gallery-filter-btn" data-filter="events">Events</button>
            <button class="gallery-filter-btn" data-filter="center">Center</button>
            <button class="gallery-filter-btn" data-filter="students">Students</button>
        </div>

        <!-- Pinterest-style Masonry Grid -->
        <div class="gallery-masonry" id="masonry-container">
            <?php if (!empty($galleryImages)): ?>
                <?php foreach ($galleryImages as $img): ?>
                    <div class="gallery-item animate-on-scroll" 
                         data-category="<?php echo htmlspecialchars($img['category']); ?>" 
                         data-caption="<?php echo htmlspecialchars($img['caption']); ?>"
                         data-full-img="<?php echo htmlspecialchars($img['image_path']); ?>">
                        
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['caption']); ?>" loading="lazy">
                        
                        <div class="gallery-overlay-caption">
                            <h3 class="gallery-caption-title"><?php echo htmlspecialchars($img['caption']); ?></h3>
                            <span class="gallery-caption-cat"><?php echo htmlspecialchars($img['category']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--muted-gray);">No gallery photos found.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ==========================================================================
     3. LIGHTBOX MODAL OVERLAY
     ========================================================================== -->
<div class="gallery-lightbox" id="gallery-lightbox" role="dialog" aria-modal="true" aria-label="Image Lightbox">
    <div class="lightbox-content-box">
        <button class="lightbox-close" id="lightbox-close" aria-label="Close Lightbox">✕</button>
        <button class="lightbox-nav-btn lightbox-prev" id="lightbox-prev" aria-label="Previous Image">‹</button>
        <button class="lightbox-nav-btn lightbox-next" id="lightbox-next" aria-label="Next Image">›</button>

        <img id="lightbox-img" src="" alt="Enlarged Gallery Image" class="lightbox-img">

        <div class="lightbox-info">
            <div id="lightbox-caption" class="lightbox-caption"></div>
            <span id="lightbox-cat" class="lightbox-cat-badge"></span>
        </div>
    </div>
</div>

<!-- ==========================================================================
     4. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
*/
?>
