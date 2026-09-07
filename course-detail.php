<?php
// Course Detail Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

$slug = $_GET['slug'] ?? '';

// Fetch single course by slug
$course = null;
if (!empty($slug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in course-detail.php: " . $e->getMessage());
    }
}

// Fallback if course not found
if (!$course) {
    try {
        $fallbackStmt = $pdo->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1");
        $course = $fallbackStmt ? $fallbackStmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (PDOException $e) {
        $course = null;
    }
}

if (!$course) {
    header("Location: " . BASE_URL . "courses.php");
    exit;
}

// Fetch related courses
$relatedCourses = [];
try {
    $relStmt = $pdo->prepare("SELECT * FROM courses WHERE id != ? AND is_active = 1 ORDER BY sort_order ASC LIMIT 3");
    $relStmt->execute([$course['id']]);
    $relatedCourses = $relStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching related courses: " . $e->getMessage());
}

// Page Metadata
$pageTitle = htmlspecialchars($course['title']) . " | Divine Reiki Certification";
$pageDescription = htmlspecialchars($course['short_description']);

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="container">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="<?php echo BASE_URL; ?>index.php">Home</a>
        <span class="breadcrumb-separator">›</span>
        <a href="<?php echo BASE_URL; ?>courses.php">Courses</a>
        <span class="breadcrumb-separator">›</span>
        <span><?php echo htmlspecialchars($course['title']); ?></span>
    </nav>
</div>

<!-- Course Detail Section -->
<section class="course-detail-section" id="course-detail">
    <div class="container">
        <div class="course-detail-grid">
            
            <!-- Main Content Column -->
            <article class="course-detail-main animate-on-scroll">
                <!-- Hero Image -->
                <div class="course-main-hero-img">
                    <img src="<?php echo htmlspecialchars($course['image'] ?: 'assets/images/courses/reiki-level-1.jpg'); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" loading="lazy">
                </div>

                <span class="badge badge-gold" style="margin-bottom: 12px; font-size: 0.82rem;">Certified Training</span>
                <h1 class="course-detail-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                
                <p style="font-size: 1.15rem; font-weight: 500; color: var(--primary-purple); margin-bottom: 24px; line-height: 1.6;">
                    <?php echo htmlspecialchars($course['short_description']); ?>
                </p>

                <!-- Full Course Description -->
                <div class="course-body-content">
                    <?php 
                    $paragraphs = explode("\n", $course['full_description']);
                    foreach ($paragraphs as $para) {
                        $para = trim($para);
                        if (!empty($para)) {
                            echo '<p>' . htmlspecialchars($para) . '</p>';
                        }
                    }
                    ?>
                </div>
            </article>

            <!-- Sidebar Column -->
            <aside class="course-sidebar-box animate-on-scroll">
                <div class="course-price-big">
                    <?php echo htmlspecialchars($course['price_text']); ?>
                </div>

                <ul class="course-sidebar-features">
                    <li><span style="color: var(--accent-gold-dark);">✓</span> Recognized Practitioner Certificate</li>
                    <li><span style="color: var(--accent-gold-dark);">✓</span> Sacred Attunement Ceremony</li>
                    <li><span style="color: var(--accent-gold-dark);">✓</span> Lifetime Training Manual & Material</li>
                    <li><span style="color: var(--accent-gold-dark);">✓</span> Direct Mentorship from Grand Masters</li>
                    <li><span style="color: var(--accent-gold-dark);">✓</span> Post-Course Practice Support</li>
                </ul>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="<?php echo BASE_URL; ?>contact.php?course=<?php echo urlencode($course['slug']); ?>#enquire" class="btn-gold w-full text-center" style="padding: 12px; font-size: 1rem;">
                        Enquire Now →
                    </a>
                    <a href="https://wa.me/919971655705?text=Hello%20Reiki%20Bliss,%20I%20want%20to%20enquire%20about%20the%20<?php echo urlencode($course['title']); ?>%20course." target="_blank" rel="noopener" class="btn-secondary w-full text-center" style="padding: 12px; font-size: 0.92rem;">
                        💬 Chat on WhatsApp
                    </a>
                </div>
            </aside>

        </div>

        <!-- Related Courses Section -->
        <?php if (!empty($relatedCourses)): ?>
            <div class="related-courses-section animate-on-scroll">
                <h3 class="section-heading" style="font-size: 2rem; margin-bottom: 30px;">Related Certification <em>Courses</em></h3>
                <div class="courses-cards-grid">
                    <?php foreach ($relatedCourses as $rel): ?>
                        <div class="course-item-card">
                            <div class="course-img-box">
                                <img src="<?php echo htmlspecialchars($rel['image'] ?: 'assets/images/courses/reiki-level-1.jpg'); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy">
                            </div>
                            <div class="course-info-body">
                                <div>
                                    <h4 class="course-item-title" style="font-size: 1.25rem;"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                    <p class="course-item-desc" style="font-size: 0.88rem;"><?php echo htmlspecialchars($rel['short_description']); ?></p>
                                </div>
                                <div class="course-card-footer">
                                    <span class="course-price-text" style="font-size: 1.1rem;"><?php echo htmlspecialchars($rel['price_text']); ?></span>
                                    <a href="<?php echo BASE_URL; ?>course-detail.php?slug=<?php echo htmlspecialchars($rel['slug']); ?>" class="btn-secondary" style="padding: 6px 14px; font-size: 0.82rem;">
                                        View Details →
                                    </a>
                                </div>
                            </div>
                        </div>
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
