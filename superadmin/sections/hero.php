<?php
// Section 1: Hero Banner & Trust Stats
$currentHeroBadge = isset($getSecContent) ? $getSecContent('hero', 'badge', $heroBadge) : $heroBadge;
$currentHeroTitle = isset($getSecContent) ? $getSecContent('hero', 'title', $heroTitleHtml) : $heroTitleHtml;
$currentHeroSubtext = isset($getSecContent) ? $getSecContent('hero', 'desc', $heroSubtext) : $heroSubtext;
?>
<section class="hero-section hero-section-centered" id="hero">
    <!-- Scrolling Background Posters (Full Banner Size Scrolling Right to Left) -->
    <div class="hero-bg-scroller" aria-hidden="true">
        <div class="hero-bg-track">
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide1); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide2); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide3); ?>');"></div>
            <!-- Seamless loop duplicate -->
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide1); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide2); ?>');"></div>
            <div class="hero-bg-slide" style="background-image: url('<?php echo htmlspecialchars($heroSlide3); ?>');"></div>
        </div>
    </div>

    <!-- Dimmed Soft Aura Overlay -->
    <div class="hero-dim-overlay"></div>

    <!-- Ambient Canvas Glow Layer -->
    <canvas id="hero-bg-canvas"></canvas>

    <div class="container hero-container-center animate-on-scroll">
        <span class="hero-location-badge">
            <?php echo htmlspecialchars($currentHeroBadge); ?>
        </span>

        <h1 class="hero-title">
            <?php echo $currentHeroTitle; ?>
        </h1>

        <div class="hero-subtext">
            <?php echo $currentHeroSubtext; ?>
        </div>

        <div class="hero-ctas">
            <a href="<?php echo htmlspecialchars($cta1Url); ?>" target="_blank" rel="noopener" class="btn-gold btn-hero-primary">
                <?php echo htmlspecialchars($cta1Text); ?> <span class="btn-arrow">→</span>
            </a>
            <a href="<?php echo htmlspecialchars($cta2Url); ?>" class="btn-secondary btn-hero-secondary">
                <?php echo htmlspecialchars($cta2Text); ?>
            </a>
        </div>

        <!-- Centered Trust Stats Bar -->
        <div class="hero-trust-bar">
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat1Num; ?>"><?php echo htmlspecialchars($heroStat1Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat1Label); ?></div>
            </div>
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat2Num; ?>"><?php echo htmlspecialchars($heroStat2Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat2Label); ?></div>
            </div>
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat3Num; ?>"><?php echo htmlspecialchars($heroStat3Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat3Label); ?></div>
            </div>
            <div class="trust-stat">
                <div class="trust-stat-number stat-number" data-target="<?php echo $heroStat4Num; ?>"><?php echo htmlspecialchars($heroStat4Text); ?></div>
                <div class="trust-stat-label"><?php echo htmlspecialchars($heroStat4Label); ?></div>
            </div>
        </div>
    </div>
</section>
