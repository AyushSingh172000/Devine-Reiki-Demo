<?php
// Reusable CTA Section Component
require_once __DIR__ . '/../config/constants.php';
?>

<!-- Reusable Healing Journey Call to Action Section -->
<section class="cta-section" id="cta-section">
    <div class="cta-bg-overlay"></div>
    <div class="container cta-container text-center animate-on-scroll">
        <span class="section-label cta-label">First Session is Free</span>
        <h2 class="cta-heading">Begin Your <em>Healing Journey</em> Today</h2>
        <p class="cta-subtext">
            Take the first step. Meet Ms Anupama Agrawal and discover which modality resonates with your soul.
        </p>
        <div class="cta-buttons">
            <a href="<?php echo BOOKING_URL; ?>" target="_blank" rel="noopener" class="btn-gold btn-cta-primary">
                Book Free Session <span class="btn-arrow">→</span>
            </a>
        </div>
    </div>
</section>
