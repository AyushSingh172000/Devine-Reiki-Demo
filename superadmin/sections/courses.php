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
$coursesBadge = isset($getSecContent) ? $getSecContent('courses', 'badge', 'Certified Energy Training') : 'Certified Energy Training';
$coursesTitle = isset($getSecContent) ? $getSecContent('courses', 'title', 'Explore Reiki &amp; Healing <em>Courses</em>') : 'Explore Reiki &amp; Healing <em>Courses</em>';
$coursesDesc  = isset($getSecContent) ? $getSecContent('courses', 'desc', '') : '';
?>
        <div class="section-header-flex animate-on-scroll" <?php echo $coursesAlignStyle; ?>>
            <div>
                <span class="section-label"><?php echo htmlspecialchars($coursesBadge); ?></span>
                <h2 class="section-heading"><?php echo $coursesTitle; ?></h2>
                <?php if (!empty($coursesDesc)): ?>
                    <p class="section-subtext" style="color: var(--text-muted); font-size: 0.95rem; margin-top: 6px;"><?php echo htmlspecialchars($coursesDesc); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo BASE_URL; ?>courses" class="view-all-link">View all courses →</a>
        </div>

        <!-- Horizontal Scrollable Course Cards -->
        <div class="scroll-cards-row">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="scroll-card">
                        <div class="card-img-box">
                            <img src="<?php echo htmlspecialchars($course['image'] ?: 'assets/images/courses/reiki-level-1.jpg'); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" loading="lazy">
                        </div>
                        <div class="card-body-content">
                            <div>
                                <span class="card-repeat-title"><?php echo htmlspecialchars($course['title']); ?></span>
                                <h3 class="card-main-title"><?php echo htmlspecialchars($course['title']); ?></h3>
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
