<?php
// Section 3: Testimonials Text & Slider
$testimHeaderStyle = '';
if (($secAlign ?? 'center') === 'left') {
    $testimHeaderStyle = 'style="text-align: left; margin: 0; align-items: flex-start;"';
} elseif (($secAlign ?? 'center') === 'right') {
    $testimHeaderStyle = 'style="text-align: right; margin-left: auto; align-items: flex-end;"';
}
?>
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="testimonials-header-wrap animate-on-scroll">
<?php
$defaultTestimBadge = array_key_exists('home_testimonials_badge', $siteSettings) ? trim((string)$siteSettings['home_testimonials_badge']) : 'STORIES OF HEALING';
$defaultTestimTitle = array_key_exists('home_testimonials_title', $siteSettings) ? trim((string)$siteSettings['home_testimonials_title']) : 'What Our Students &amp; Clients <em>Say</em>';
$defaultTestimDesc  = array_key_exists('home_testimonials_desc', $siteSettings) ? trim((string)$siteSettings['home_testimonials_desc']) : 'Read real life experiences from individuals who restored harmony, vitality, and peace through our Reiki sessions.';

$testimBadge = isset($getSecContent) ? $getSecContent('testimonials', 'badge', $defaultTestimBadge) : $defaultTestimBadge;
$testimTitle = isset($getSecContent) ? $getSecContent('testimonials', 'title', $defaultTestimTitle) : $defaultTestimTitle;
$testimDesc  = isset($getSecContent) ? $getSecContent('testimonials', 'desc', $defaultTestimDesc) : $defaultTestimDesc;
?>
            <div class="testimonials-header-center" <?php echo $testimHeaderStyle; ?>>
                <?php if (!empty(trim((string)$testimBadge))): ?>
                <span class="section-label"><?php echo htmlspecialchars($testimBadge); ?></span>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$testimTitle)))): ?>
                <h2 class="section-heading"><?php echo $testimTitle; ?></h2>
                <?php endif; ?>
                <?php if (!empty(trim(strip_tags((string)$testimDesc)))): ?>
                <p><?php echo htmlspecialchars($testimDesc); ?></p>
                <?php endif; ?>
            </div>
            <div class="testimonials-nav-btns">
                <button type="button" class="testimonial-nav-btn prev-btn" id="testimonialPrevBtn" onclick="scrollTestimonials('prev')" aria-label="Previous Testimonials">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button type="button" class="testimonial-nav-btn next-btn" id="testimonialNextBtn" onclick="scrollTestimonials('next')" aria-label="Next Testimonials">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>

        <div class="testimonials-single-row" id="testimonialsSlider">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $t): ?>
                    <?php 
                        $ratingNum = max(1, min(5, (int)($t['rating'] ?? 5)));
                        $starsHtml = str_repeat('★', $ratingNum) . str_repeat('☆', 5 - $ratingNum);
                    ?>
                    <div class="testimonial-card-box">
                        <div class="quote-mark">“</div>
                        <p class="testimonial-text-body"><?php echo htmlspecialchars($t['content']); ?></p>
                        <div class="testimonial-author-row">
                            <?php if (!empty($t['image'])): ?>
                                <div class="author-initial-circle" style="padding: 0; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($t['image']); ?>" alt="<?php echo htmlspecialchars($t['client_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php else: ?>
                                <div class="author-initial-circle">
                                    <?php echo strtoupper(substr($t['client_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="author-info-text">
                                <strong><?php echo htmlspecialchars($t['client_name']); ?></strong>
                                <span>
                                    <?php if (!empty($t['location'])): ?>
                                        <?php echo htmlspecialchars($t['location']); ?> · 
                                    <?php endif; ?>
                                    <span style="color: #d97706; letter-spacing: 1.5px; font-size: 0.95rem;"><?php echo $starsHtml; ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No testimonials available.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
if (typeof scrollTestimonials === 'undefined') {
    function scrollTestimonials(direction) {
        var slider = document.getElementById('testimonialsSlider');
        if (!slider) return;
        var firstCard = slider.querySelector('.testimonial-card-box');
        var step = firstCard ? (firstCard.getBoundingClientRect().width + 28) : 360;
        var maxScroll = slider.scrollWidth - slider.clientWidth;

        if (direction === 'next') {
            if (slider.scrollLeft >= maxScroll - 20) {
                slider.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: step, behavior: 'smooth' });
            }
        } else {
            if (slider.scrollLeft <= 20) {
                slider.scrollTo({ left: maxScroll, behavior: 'smooth' });
            } else {
                slider.scrollBy({ left: -step, behavior: 'smooth' });
            }
        }
    }
}
</script>
