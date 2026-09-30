<?php
// Section 4: Courses Section
$coursesAlignStyle = '';
if (($secAlign ?? 'left') === 'center') {
    $coursesAlignStyle = 'style="text-align: center; justify-content: center; flex-direction: column; align-items: center; gap: 14px;"';
} elseif (($secAlign ?? 'left') === 'right') {
    $coursesAlignStyle = 'style="text-align: right; justify-content: flex-end; flex-direction: row-reverse;"';
}

$hidePrices = !empty($saToggles['hide_prices']);
?>
<section class="courses-section" id="courses">
    <div class="container">
<?php
$defaultCoursesBadge = array_key_exists('home_courses_badge', $siteSettings) ? trim((string)$siteSettings['home_courses_badge']) : 'Certified Energy Training';
$defaultCoursesTitle = array_key_exists('home_courses_title', $siteSettings) ? trim((string)$siteSettings['home_courses_title']) : 'Explore Reiki &amp; Healing <em>Courses</em>';
$defaultCoursesDesc  = array_key_exists('home_courses_desc', $siteSettings) ? trim((string)$siteSettings['home_courses_desc']) : 'Become a certified Reiki healer yourself. Structured curriculum with authentic attunement (Diksha), physical manual, lifetime mentorship, and recognized certificates.';

$coursesBadge = isset($getSecContent) ? $getSecContent('courses', 'badge', $defaultCoursesBadge) : $defaultCoursesBadge;
$coursesTitle = isset($getSecContent) ? $getSecContent('courses', 'title', $defaultCoursesTitle) : $defaultCoursesTitle;
$coursesDesc  = isset($getSecContent) ? $getSecContent('courses', 'desc', $defaultCoursesDesc) : $defaultCoursesDesc;

$coursesSubtextMargin = (($secAlign ?? 'left') === 'center') ? 'margin: 8px auto 0;' : ((($secAlign ?? 'left') === 'right') ? 'margin: 8px 0 0 auto;' : 'margin-top: 8px;');
?>
        <div class="section-header-flex animate-on-scroll" <?php echo $coursesAlignStyle; ?>>
            <div>
                <?php if (!empty(trim((string)$coursesBadge))): ?>
                <span class="section-label"><?php echo htmlspecialchars($coursesBadge); ?></span>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$coursesTitle)))): ?>
                <h2 class="section-heading"><?php echo $coursesTitle; ?></h2>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$coursesDesc)))): ?>
                    <p class="section-subtext" style="color: var(--text-muted, #555D6E); font-size: 0.98rem; line-height: 1.6; max-width: 680px; <?php echo $coursesSubtextMargin; ?> margin-bottom: 0;"><?php echo htmlspecialchars($coursesDesc); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>courses" class="view-all-link">View all courses →</a>
        </div>

        <!-- Horizontal Scrollable Course Cards -->
        <div class="scroll-cards-row">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <?php $hasCourseImg = !empty($course['image']); ?>
                    <div class="scroll-card">
                        <?php if ($hasCourseImg): ?>
                        <div class="card-img-box">
                            <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" loading="lazy">
                        </div>
                        <?php endif; ?>
                        <div class="card-body-content" style="<?php echo !$hasCourseImg ? 'padding-top: 24px;' : ''; ?>">
                            <div>
                                <h3 class="card-main-title">
                                    <a href="<?php echo BASE_URL; ?><?php echo !empty($course['slug']) ? 'course/' . urlencode($course['slug']) : 'courses'; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </a>
                                </h3>
                                <p class="card-desc-text"><?php echo htmlspecialchars($course['short_description']); ?></p>
                            </div>
                            <div class="card-footer-meta">
                                <span class="card-price-value">
                                    <?php echo $hidePrices ? 'Contact for Price' : htmlspecialchars($course['price_text']); ?>
                                </span>
                                <a href="<?php echo BASE_URL; ?><?php echo !empty($course['slug']) ? 'course/' . urlencode($course['slug']) : 'courses'; ?>" class="btn-primary" style="padding: 7px 18px; font-size: 0.86rem;">View details →</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses currently listed.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
