<?php
// About Us Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "About Us | Reiki Bliss";
$pageDescription = "A journey inward. A purpose to help others heal. Learn about the founding story and philosophy of Reiki Bliss, founded by Reiki Grand Master & Spiritual Wellness Coach Anupama Agrawal in Adajan, Surat.";

// Helper function to resolve team image URL properly
if (!function_exists('getTeamImgUrl')) {
    function getTeamImgUrl($imgPath, $fallback = 'assets/images/team/anupama_mam.jpeg') {
        if (empty($imgPath)) {
            return BASE_URL . ltrim($fallback, '/');
        }
        if (strpos($imgPath, 'http://') === 0 || strpos($imgPath, 'https://') === 0) {
            return $imgPath;
        }
        return BASE_URL . ltrim($imgPath, '/');
    }
}

// Single Founder Data (Anupama Agrawal)
$founder = [
    'name' => 'Anupama Agrawal',
    'role' => 'Founder, Reiki Grand Master & Spiritual Wellness Coach',
    'image' => 'assets/images/team/anupama_mam.jpeg',
    'quote' => 'Think Positive, Be Positive.',
    'specialties' => [
        'Reiki Healing',
        'Chakra Balancing',
        'Guided Meditation',
        'Lama Fera',
        'Access Bars',
        'Angel Healing',
        'Victory Reiki',
        'Money Reiki',
        'Switch Words',
        'Tarot Card Reading'
    ]
];

// Dynamic About Page Content from $siteSettings & $siteStats
$aboutHeading = 'A Journey Inward.<br>A Purpose to <em>Help Others Heal.</em>';
$aboutDescription = 'Reiki Bliss was founded by Anupama Agrawal, a Reiki Grand Master and Spiritual Wellness Coach dedicated to authentic energy healing, self-awareness, and holistic inner transformation.';
$foundingYear = $siteSettings['founding_year'] ?? '2014';

$healedCount = !empty($siteStats['lives_healed']['stat_value']) ? $siteStats['lives_healed']['stat_value'] . '+' : '30K+';
$expYears = !empty($siteStats['years_experience']['stat_value']) ? $siteStats['years_experience']['stat_value'] . '+ Years Experience' : '10+ Years Experience';
$sessionsCount = !empty($siteStats['sessions_completed']['stat_value']) ? $siteStats['sessions_completed']['stat_value'] . '+ Sessions' : '25K+ Sessions Completed';
$coursesCount = !empty($siteStats['course_levels']['stat_value']) ? $siteStats['course_levels']['stat_value'] . ' Course Levels' : '6 Course Levels Offered';

// Include Header Component
include __DIR__ . '/includes/header.php';
?>

<!-- ==========================================================================
     1. HERO SECTION & SCROLLING MARQUEE
     ========================================================================== -->
<section class="about-hero" id="about-hero">
    <canvas class="hero-bg-canvas"></canvas>
    <div class="container animate-on-scroll">
        <span class="about-hero-badge">Est. <?php echo htmlspecialchars($foundingYear); ?> · Adajan, Surat</span>
        <h1 class="about-hero-title">
            A Journey Inward.<br>
            A Purpose to <em>Help Others Heal.</em>
        </h1>
        <p class="about-hero-text">
            <?php echo htmlspecialchars($aboutDescription); ?>
        </p>
    </div>

    <!-- Infinite Scrolling Stats Marquee -->
    <div class="marquee-container">
        <div class="marquee-track">
            <div class="marquee-item"><span><?php echo htmlspecialchars($healedCount); ?> Healed Clients</span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span><?php echo htmlspecialchars($expYears); ?></span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span><?php echo htmlspecialchars($sessionsCount); ?></span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span><?php echo htmlspecialchars($coursesCount); ?></span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span>100% Authentic Lineage</span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span><?php echo htmlspecialchars($healedCount); ?> Healed Clients</span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span><?php echo htmlspecialchars($expYears); ?></span> <span class="marquee-dot">✦</span></div>
            <div class="marquee-item"><span><?php echo htmlspecialchars($sessionsCount); ?></span> <span class="marquee-dot">✦</span></div>
    </div>
</section>

<!-- ==========================================================================
     2. FOUNDER SECTION (ANUPAMA AGRAWAL)
     ========================================================================== -->
<section class="founders-section" id="founder">
    <div class="container">
        <div class="text-center animate-on-scroll" style="margin-bottom: 48px;">
            <span class="section-label">Our Founder</span>
            <h2 class="section-heading">Meet The Soul Behind <em>Reiki Bliss</em></h2>
            <p style="max-width: 680px; margin: 0 auto; color: #555D6E; font-size: 1.05rem;">
                Dedicated to authentic healing, energy alignment, and empowering individuals to discover their inner harmony.
            </p>
        </div>

        <div class="founder-single-container animate-on-scroll">
            <div class="founder-card single-founder">
                <div class="founder-img-box">
                    <img src="<?php echo BASE_URL; ?>assets/images/team/anupama_mam.jpeg" alt="Anupama Agrawal - Founder &amp; Reiki Grand Master" loading="lazy">
                    <div class="founder-photo-badge">
                        <span class="badge-icon">✦</span>
                        <span class="badge-text">Reiki Grand Master</span>
                    </div>
                </div>
                <div class="founder-content-box">
                    <span class="founder-role-title"><?php echo htmlspecialchars($founder['role']); ?></span>
                    <h3 class="founder-name"><?php echo htmlspecialchars($founder['name']); ?></h3>
                    
                    <div class="founder-quote-banner">
                        <div class="quote-mark">“</div>
                        <div class="quote-content">
                            <p class="quote-text"><?php echo htmlspecialchars($founder['quote']); ?></p>
                            <span class="quote-caption">The guiding belief at the heart of her life and healing practice</span>
                        </div>
                    </div>

                    <div class="founder-story-paragraphs">
                        <p>
                            <strong>Reiki Bliss</strong> was founded by <strong>Anupama Agrawal</strong>, a Reiki Grand Master and Spiritual Wellness Coach whose journey into holistic wellness began with a simple but powerful interest in meditation and self-healing.
                        </p>
                        <p>
                            What started as a personal practice gradually became a deeper calling. For more than a decade, Anupama has studied and practiced Reiki with dedication, progressing to the level of Reiki Grand Master while continuing to explore complementary spiritual and energy practices.
                        </p>
                        <p>
                            Through Reiki Bliss, Anupama creates a warm, supportive space for people to slow down, reconnect with themselves and explore practices that can support greater balance, clarity and inner well-being. Her approach is personal and grounded—meeting each individual where they are rather than treating wellness as one-size-fits-all.
                        </p>
                        <p>
                            Her work today includes individual consultations as well as classes for those who wish to learn and deepen their own practice across a comprehensive range of sacred energy disciplines.
                        </p>
                    </div>

                    <div class="founder-specialties-box">
                        <div class="specialties-title">Core Healing Modalities &amp; Offerings</div>
                        <div class="specialty-tags-list">
                            <?php foreach ($founder['specialties'] as $modality): ?>
                                <span class="specialty-tag-badge"><?php echo htmlspecialchars($modality); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     3. THE PHILOSOPHY BEHIND REIKI BLISS
     ========================================================================== -->
<section class="philosophy-section" id="philosophy">
    <div class="container">
        <div class="text-center animate-on-scroll">
            <span class="section-label">Our Philosophy</span>
            <h2 class="section-heading">The Philosophy Behind <em>Reiki Bliss</em></h2>
            <p style="max-width: 720px; margin: 0 auto; color: #555D6E; font-size: 1.05rem;">
                Anupama believes that meaningful change often begins by turning inward—creating space to understand ourselves, release what no longer serves us and become more intentional about the energy we bring into our lives.
            </p>
        </div>

        <div class="philosophy-grid">
            <!-- Pillar 1: Turning Inward -->
            <div class="philosophy-card animate-on-scroll">
                <div class="philosophy-icon">🧘‍♀️</div>
                <h3 class="philosophy-card-title">Turning Inward</h3>
                <p class="philosophy-card-text">
                    Meaningful change begins by creating space to understand ourselves, gently releasing emotional and energetic blocks that no longer serve us, and cultivating intentional positive energy.
                </p>
            </div>

            <!-- Pillar 2: Approachable Spiritual Wellness -->
            <div class="philosophy-card animate-on-scroll">
                <div class="philosophy-icon">✨</div>
                <h3 class="philosophy-card-title">Our Mission</h3>
                <p class="philosophy-card-text">
                    To make spiritual wellness approachable and to help more people discover the transformative power of self-awareness, self-healing, and conscious positive living.
                </p>
            </div>

            <!-- Pillar 3: Begin Where You Are -->
            <div class="philosophy-card animate-on-scroll">
                <div class="philosophy-icon">🌱</div>
                <h3 class="philosophy-card-title">Begin Where You Are</h3>
                <p class="philosophy-card-text">
                    Whether you are completely new to spiritual wellness, looking for greater balance in your everyday life, or hoping to deepen an existing practice, you are welcome to begin exactly where you are.
                </p>
            </div>

            <!-- Pillar 4: Your Journey is Your Own -->
            <div class="philosophy-card animate-on-scroll">
                <div class="philosophy-icon">🌟</div>
                <h3 class="philosophy-card-title">Your Journey is Your Own</h3>
                <p class="philosophy-card-text">
                    Your journey is your own. Reiki Bliss is here to provide grounded, compassionate guidance and create a safe sanctuary to help you explore it at your own rhythm.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     4. MEET OUR HEALING TEAM (HIDDEN AS REQUESTED)
     ========================================================================== -->
<!--
<section class="team-section" id="team">
    <div class="container">
        <div class="text-center animate-on-scroll">
            <span class="section-label">Our Practitioners</span>
            <h2 class="section-heading">Meet Our <em>Healing</em> Team</h2>
            <p>Our certified masters and energy therapists bring deep wisdom, compassion, and specialized healing techniques to every session.</p>
        </div>
    </div>
</section>
-->

<!-- ==========================================================================
     4. VALUES SECTION
     ========================================================================== -->
<section class="values-section" id="values">
    <div class="container">
        <div class="text-center animate-on-scroll">
            <span class="section-label">What We Stand For</span>
            <h2 class="section-heading">Our Core <em>Values</em></h2>
            <p>Principles that guide every healing session, attunement workshop, and crystal recommendation at our center.</p>
        </div>

        <?php
        $coreValuesList = [];
        if (!empty($siteSettings['core_values'])) {
            $decodedVals = is_string($siteSettings['core_values']) ? json_decode($siteSettings['core_values'], true) : $siteSettings['core_values'];
            if (is_array($decodedVals) && !empty($decodedVals)) {
                $coreValuesList = $decodedVals;
            }
        }
        if (empty($coreValuesList)) {
            $coreValuesList = [
                [
                    'title' => $siteSettings['value_1_title'] ?? 'Compassionate Presence',
                    'description' => $siteSettings['value_1_desc'] ?? 'Every session is held with deep unconditional empathy, confidentiality, and spiritual grounding.'
                ],
                [
                    'title' => $siteSettings['value_2_title'] ?? 'Authentic Lineage',
                    'description' => $siteSettings['value_2_desc'] ?? 'Direct Usui Reiki tradition handed down through accredited grandmasters with authentic attunement.'
                ],
                [
                    'title' => $siteSettings['value_3_title'] ?? 'Holistic Transformation',
                    'description' => $siteSettings['value_3_desc'] ?? 'Addressing subtle energetic root causes rather than just superficial physical symptoms.'
                ],
                [
                    'title' => $siteSettings['value_4_title'] ?? 'Empowered Self-Healing',
                    'description' => $siteSettings['value_4_desc'] ?? 'Guiding every student and healee with knowledge to sustain their own energetic balance.'
                ]
            ];
        }
        ?>
        <div class="values-grid">
            <?php foreach ($coreValuesList as $vIndex => $valItem): ?>
                <div class="value-card animate-on-scroll">
                    <div class="value-number"><?php echo sprintf('%02d', $vIndex + 1); ?></div>
                    <h3 class="value-card-title"><?php echo htmlspecialchars($valItem['title'] ?? ''); ?></h3>
                    <p class="value-card-text"><?php echo htmlspecialchars($valItem['description'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==========================================================================
     5. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
