<?php
// Courses Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "Reiki Certification Courses | Reiki Bliss";
$pageDescription = "Enroll in certified Usui Reiki training courses from Level 1 First Degree to Master Teacher Level 3B with Reiki Grandmaster Anupama Agrawal.";

// Fetch Courses from MySQL
try {
    $stmt = $pdo->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY sort_order ASC");
    $courses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error in courses.php: " . $e->getMessage());
    $courses = [];
}

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION
     ========================================================================== -->
<section class="courses-hero" id="courses-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="courses-hero-badge">Certified Energy Training</span>
        <h1 class="courses-hero-title">Certification <em>Courses</em></h1>
        <p class="courses-hero-subtext">
            Embark on a transformative spiritual learning path. Master Usui Reiki attunements, chakra balancing techniques, and earn professional practitioner credentials guided by Grandmaster Healers.
        </p>
    </div>
</section>

<!-- ==========================================================================
     2. COURSES GRID SECTION
     ========================================================================== -->
<section class="courses-grid-section" id="courses-grid">
    <div class="container">
        <div class="courses-cards-grid">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $index => $course): ?>
                    <?php $hasCourseImg = !empty($course['image']); ?>
                    <div class="course-item-card animate-on-scroll">
                        <?php if ($hasCourseImg): ?>
                        <div class="course-img-box">
                            <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" loading="lazy">
                            <span class="badge badge-gold course-level-badge">Level <?php echo ($index + 1); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="course-info-body">
                            <div>
                                <?php if (!$hasCourseImg): ?>
                                    <span class="badge badge-gold" style="margin-bottom: 10px; display: inline-block;">Level <?php echo ($index + 1); ?></span>
                                <?php endif; ?>
                                <h3 class="course-item-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <?php if (!empty($course['short_description'])): ?>
                                    <p class="course-item-desc"><?php echo htmlspecialchars($course['short_description']); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($course['duration']) || !empty($course['mode']) || !empty($course['language'])): ?>
                                    <div class="course-meta-tags" style="display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 20px 0; font-size: 0.8rem; color: #555D6E;">
                                        <?php if (!empty($course['duration'])): ?>
                                            <span style="background: rgba(200, 155, 60, 0.08); border: 1px solid rgba(200, 155, 60, 0.2); padding: 4px 9px; border-radius: 6px; font-weight: 500;">
                                                ⏱️ <?php echo htmlspecialchars($course['duration']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($course['mode'])): ?>
                                            <span style="background: rgba(200, 155, 60, 0.08); border: 1px solid rgba(200, 155, 60, 0.2); padding: 4px 9px; border-radius: 6px; font-weight: 500;">
                                                🌐 <?php echo htmlspecialchars($course['mode']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($course['language'])): ?>
                                            <span style="background: rgba(200, 155, 60, 0.08); border: 1px solid rgba(200, 155, 60, 0.2); padding: 4px 9px; border-radius: 6px; font-weight: 500;">
                                                🗣️ <?php echo htmlspecialchars($course['language']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="course-card-footer" style="margin-top: 18px; padding-top: 16px; border-top: 1px solid #EAE5DB;">
                                <span class="course-price-text"><?php echo htmlspecialchars(!empty($course['energy_exchange']) ? $course['energy_exchange'] : $course['price_text']); ?></span>
                                <a href="<?php echo BASE_URL; ?>course/<?php echo htmlspecialchars($course['slug']); ?>" class="btn-primary" style="padding: 8px 18px; font-size: 0.88rem;">
                                    View Details →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses currently available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     3. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
