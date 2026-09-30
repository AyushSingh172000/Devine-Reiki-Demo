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

// Helper to guarantee absolute asset URL for rewritten routes
$getAssetUrl = function($path, $fallback = '') {
    $img = !empty($path) ? $path : $fallback;
    if (empty($img)) return '';
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        return $img;
    }
    return BASE_URL . ltrim($img, '/');
};

$hasCourseImg = !empty($course['image']);
$heroImgUrl = $getAssetUrl($course['image'] ?? '');

// Page Metadata
$pageTitle = htmlspecialchars($course['title']) . " | Divine Reiki Certification";
$pageDescription = htmlspecialchars($course['short_description']);
$pageOgImage = $hasCourseImg ? $heroImgUrl : null;

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="container">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="<?php echo BASE_URL; ?>">Home</a>
        <span class="breadcrumb-separator">›</span>
        <a href="<?php echo BASE_URL; ?>courses">Courses</a>
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
                <?php if ($hasCourseImg): ?>
                <!-- Hero Image -->
                <div class="course-main-hero-img">
                    <img src="<?php echo htmlspecialchars($heroImgUrl); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" loading="lazy">
                </div>
                <?php endif; ?>

                <span class="badge badge-gold" style="margin-bottom: 12px; font-size: 0.82rem;">Certified Training</span>
                <h1 class="course-detail-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                
                <p style="font-size: 1.15rem; font-weight: 500; color: var(--primary-purple); margin-bottom: 24px; line-height: 1.6;">
                    <?php echo htmlspecialchars($course['short_description']); ?>
                </p>

                <!-- Key Course Highlights Bar -->
                <?php if (!empty($course['duration']) || !empty($course['mode']) || !empty($course['energy_exchange']) || !empty($course['language']) || !empty($course['certificate']) || !empty($course['course_material'])): ?>
                <div class="course-spec-highlights" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; background: #FAF8F5; border: 1px solid #EAE5DB; border-radius: 14px; padding: 18px 20px; margin-bottom: 28px;">
                    <?php if (!empty($course['duration'])): ?>
                    <div class="course-spec-item">
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #7B8089; display: block; margin-bottom: 4px;">Duration</span>
                        <strong style="font-size: 0.95rem; color: #0F1117; display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 1.05rem;">⏱️</span> <?php echo htmlspecialchars($course['duration']); ?>
                        </strong>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($course['mode'])): ?>
                    <div class="course-spec-item">
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #7B8089; display: block; margin-bottom: 4px;">Mode</span>
                        <strong style="font-size: 0.95rem; color: #0F1117; display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 1.05rem;">🌐</span> <?php echo htmlspecialchars($course['mode']); ?>
                        </strong>
                    </div>
                    <?php endif; ?>

                    <?php 
                    $eeDisplay = !empty($course['energy_exchange']) ? $course['energy_exchange'] : (!empty($course['price_text']) ? $course['price_text'] : '');
                    if (!empty($eeDisplay)): 
                    ?>
                    <div class="course-spec-item">
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #7B8089; display: block; margin-bottom: 4px;">Energy Exchange</span>
                        <strong style="font-size: 0.95rem; color: var(--accent-gold-dark, #8A6D3B); display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 1.05rem;">✨</span> <?php echo htmlspecialchars($eeDisplay); ?>
                        </strong>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($course['language'])): ?>
                    <div class="course-spec-item">
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #7B8089; display: block; margin-bottom: 4px;">Language</span>
                        <strong style="font-size: 0.95rem; color: #0F1117; display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 1.05rem;">🗣️</span> <?php echo htmlspecialchars($course['language']); ?>
                        </strong>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($course['certificate'])): ?>
                    <div class="course-spec-item">
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #7B8089; display: block; margin-bottom: 4px;">Certificate</span>
                        <strong style="font-size: 0.95rem; color: #0F1117; display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 1.05rem;">📜</span> <?php echo htmlspecialchars($course['certificate']); ?>
                        </strong>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($course['course_material'])): ?>
                    <div class="course-spec-item">
                        <span style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #7B8089; display: block; margin-bottom: 4px;">Course Material</span>
                        <strong style="font-size: 0.95rem; color: #0F1117; display: flex; align-items: center; gap: 6px;">
                            <span style="font-size: 1.05rem;">📚</span> <?php echo htmlspecialchars($course['course_material']); ?>
                        </strong>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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
                    <?php echo htmlspecialchars(!empty($course['energy_exchange']) ? $course['energy_exchange'] : $course['price_text']); ?>
                </div>

                <?php if (!empty($course['duration']) || !empty($course['mode']) || !empty($course['language']) || !empty($course['certificate']) || !empty($course['course_material'])): ?>
                <div style="background: #FAF8F5; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; border: 1px solid #EAE5DB;">
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; font-size: 0.9rem;">
                        <?php if (!empty($course['duration'])): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #666;">Duration:</span>
                            <strong style="color: #0F1117;"><?php echo htmlspecialchars($course['duration']); ?></strong>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($course['mode'])): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #666;">Mode:</span>
                            <strong style="color: #0F1117;"><?php echo htmlspecialchars($course['mode']); ?></strong>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($course['language'])): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #666;">Language:</span>
                            <strong style="color: #0F1117;"><?php echo htmlspecialchars($course['language']); ?></strong>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($course['certificate'])): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #666;">Certificate:</span>
                            <strong style="color: #0F1117;"><?php echo htmlspecialchars($course['certificate']); ?></strong>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($course['course_material'])): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #666;">Course Material:</span>
                            <strong style="color: #0F1117;"><?php echo htmlspecialchars($course['course_material']); ?></strong>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="<?php echo BASE_URL; ?>contact.php?course=<?php echo urlencode($course['slug']); ?>#enquire" class="btn-gold w-full text-center" style="padding: 12px; font-size: 1rem;">
                        Enquire Now →
                    </a>
                    <a href="<?php echo BASE_URL; ?>contact.php?course=<?php echo urlencode($course['slug']); ?>#enquire" class="btn-course-whatsapp w-full text-center">
                        <span style="font-size: 1.1rem;">💬</span> Chat on WhatsApp
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
                        <?php 
                        $hasRelImg = !empty($rel['image']);
                        $relImgUrl = $getAssetUrl($rel['image'] ?? ''); 
                        ?>
                        <div class="course-item-card">
                            <?php if ($hasRelImg): ?>
                            <div class="course-img-box">
                                <img src="<?php echo htmlspecialchars($relImgUrl); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" loading="lazy">
                            </div>
                            <?php endif; ?>
                            <div class="course-info-body">
                                <div>
                                    <h4 class="course-item-title" style="font-size: 1.25rem;"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                    <p class="course-item-desc" style="font-size: 0.88rem;"><?php echo htmlspecialchars($rel['short_description']); ?></p>
                                </div>
                                <div class="course-card-footer">
                                    <span class="course-price-text" style="font-size: 1.1rem;"><?php echo htmlspecialchars($rel['price_text']); ?></span>
                                    <a href="<?php echo BASE_URL; ?>course/<?php echo htmlspecialchars($rel['slug']); ?>" class="btn-gold" style="padding: 6px 14px; font-size: 0.82rem; text-decoration: none;">
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
