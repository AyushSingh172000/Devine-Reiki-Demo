<?php
// About Us Page - Divine Reiki & Energy Healing Center
require_once __DIR__ . '/config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/config/db.php';
}

// Page Metadata
$pageTitle = "About Us | Reiki Bliss";
$pageDescription = "Learn about the founding story of Reiki Bliss in Adajan, Surat, guided by Dr. Chirag Gajjar & Binal Gajjar, and meet our team of dedicated energy practitioners.";

// Helper function to resolve team image URL properly
if (!function_exists('getTeamImgUrl')) {
    function getTeamImgUrl($imgPath, $fallback = 'assets/images/team/ananya-sharma.jpg') {
        if (empty($imgPath)) {
            return BASE_URL . ltrim($fallback, '/');
        }
        if (strpos($imgPath, 'http://') === 0 || strpos($imgPath, 'https://') === 0) {
            return $imgPath;
        }
        return BASE_URL . ltrim($imgPath, '/');
    }
}

// Fetch Team Members from MySQL
try {
    $teamStmt = $pdo->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $teamMembers = $teamStmt ? $teamStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error in about.php: " . $e->getMessage());
    $teamMembers = [];
}

// Keep a master copy for the full carousel
$allTeamOriginal = $teamMembers;

// Filter main founders dynamically from database
$founder1 = null;
$founder2 = null;

// 1. Identify Founder 1 (Match specifically for Anupama / Chirag, or lead Grandmaster / Founder)
foreach ($teamMembers as $idx => $member) {
    $nameLower = strtolower($member['name']);
    if (strpos($nameLower, 'anupama') !== false || strpos($nameLower, 'chirag') !== false) {
        $founder1 = $member;
        unset($teamMembers[$idx]);
        break;
    }
}
if (!$founder1) {
    foreach ($teamMembers as $idx => $member) {
        $roleLower = strtolower($member['role'] . ' ' . ($member['title'] ?? ''));
        if (strpos($roleLower, 'grandmaster') !== false || strpos($roleLower, 'grand master') !== false || strpos($roleLower, 'founder') !== false) {
            $founder1 = $member;
            unset($teamMembers[$idx]);
            break;
        }
    }
}

// 2. Identify Founder 2 (Match for Binal or Crystal Master / Co-Founder)
foreach ($teamMembers as $idx => $member) {
    $nameLower = strtolower($member['name']);
    $roleLower = strtolower($member['role'] . ' ' . ($member['title'] ?? ''));
    if (strpos($nameLower, 'binal') !== false || strpos($roleLower, 'crystal') !== false || strpos($roleLower, 'co-founder') !== false) {
        $founder2 = $member;
        unset($teamMembers[$idx]);
        break;
    }
}

// Fallbacks from remaining active members if needed
if (!$founder1 && !empty($teamMembers)) {
    $founder1 = array_shift($teamMembers);
}
if (!$founder2 && !empty($teamMembers)) {
    $founder2 = array_shift($teamMembers);
}

// Static fallback data ONLY if database is completely empty
if (!$founder1) {
    $founder1 = [
        'name' => 'Anupama Agrawal',
        'role' => 'Founder & Reiki Grandmaster',
        'title' => 'Reiki Grandmaster & Spiritual Healer',
        'bio' => 'Anupama Agrawal is a renowned Reiki Grandmaster with extensive experience in energy medicine, aura transformation, chakra alignment, and holistic spiritual wellness. She has guided thousands of individuals worldwide to unlock their natural healing capacity.',
        'specialties' => json_encode(["Usui Reiki Grandmaster", "Aura Transformation", "Energy Medicine", "Spiritual Counseling"]),
        'image' => 'assets/images/team/ananya-sharma.jpg'
    ];
}
if (!$founder2) {
    $founder2 = [
        'name' => 'Binal Gajjar',
        'role' => 'Co-Founder & Crystal Healing Master',
        'title' => 'Crystal Healing & Numerology Expert',
        'bio' => 'Binal Gajjar is a master crystal energy therapist and numerology consultant. Her intuitive gemstone attunements and personalized energy grids help clients manifest harmony, health, and prosperity.',
        'specialties' => json_encode(["Crystal Healing Expert", "Numerology Consultant", "Chakra Alignment", "Gemstone Attunement"]),
        'image' => 'assets/images/team/binal-gajjar.jpg'
    ];
}

// All team members for full carousel
$allTeam = !empty($allTeamOriginal) ? $allTeamOriginal : [$founder1, $founder2];

// Dynamic About Page Content from $siteSettings & $siteStats
$aboutHeading = $siteSettings['about_heading'] ?? 'Healing with Heart & Purpose';
$aboutDescription = $siteSettings['about_description'] ?? 'Reiki Bliss was born from a single conviction — that every person deserves access to authentic energy healing. We have been guiding seekers on their healing journey since 2014.';
$foundingYear = $siteSettings['founding_year'] ?? '2014';

$healedCount = !empty($siteStats['lives_healed']['stat_value']) ? $siteStats['lives_healed']['stat_value'] . '+' : '30K+';
$expYears = !empty($siteStats['years_experience']['stat_value']) ? $siteStats['years_experience']['stat_value'] . '+ Years Experience' : '25+ Years Experience';
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
            <?php echo nl2br(htmlspecialchars($aboutHeading)); ?>
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
    </div>
</section>

<!-- ==========================================================================
     2. FOUNDERS SECTION
     ========================================================================== -->
<section class="founders-section" id="founders">
    <div class="container">
        <div class="text-center animate-on-scroll">
            <span class="section-label">The Founders</span>
            <h2 class="section-heading">The People Behind Every <em>Healing</em></h2>
            <p>Meet our visionary founders who have dedicated their lives to raising spiritual consciousness and healing hearts.</p>
        </div>

        <div class="founders-grid">
            <!-- Founder Card 1: Lead Grandmaster / Founder -->
            <div class="founder-card animate-on-scroll">
                <div class="founder-img-box">
                    <img src="<?php echo htmlspecialchars(getTeamImgUrl($founder1['image'], 'assets/images/team/ananya-sharma.jpg')); ?>" alt="<?php echo htmlspecialchars($founder1['name']); ?>" loading="lazy">
                </div>
                <div class="founder-content-box">
                    <span class="founder-role-title"><?php echo htmlspecialchars($founder1['role']); ?></span>
                    <h3 class="founder-name"><?php echo htmlspecialchars($founder1['name']); ?></h3>
                    <p class="founder-bio-text"><?php echo htmlspecialchars($founder1['bio']); ?></p>
                    <div>
                        <div class="specialties-title">Core Specialties</div>
                        <div class="specialty-tags-list">
                            <?php 
                            $specs = is_string($founder1['specialties']) ? json_decode($founder1['specialties'], true) : $founder1['specialties'];
                            if (is_array($specs)):
                                foreach ($specs as $tag):
                            ?>
                                <span class="specialty-tag-badge"><?php echo htmlspecialchars($tag); ?></span>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Founder Card 2: Co-Founder / Crystal Master (Reverse Layout) -->
            <div class="founder-card reverse animate-on-scroll">
                <div class="founder-img-box">
                    <img src="<?php echo htmlspecialchars(getTeamImgUrl($founder2['image'], 'assets/images/team/binal-gajjar.jpg')); ?>" alt="<?php echo htmlspecialchars($founder2['name']); ?>" loading="lazy">
                </div>
                <div class="founder-content-box">
                    <span class="founder-role-title"><?php echo htmlspecialchars($founder2['role']); ?></span>
                    <h3 class="founder-name"><?php echo htmlspecialchars($founder2['name']); ?></h3>
                    <p class="founder-bio-text"><?php echo htmlspecialchars($founder2['bio']); ?></p>
                    <div>
                        <div class="specialties-title">Core Specialties</div>
                        <div class="specialty-tags-list">
                            <?php 
                            $specs2 = is_string($founder2['specialties']) ? json_decode($founder2['specialties'], true) : $founder2['specialties'];
                            if (is_array($specs2)):
                                foreach ($specs2 as $tag):
                            ?>
                                <span class="specialty-tag-badge"><?php echo htmlspecialchars($tag); ?></span>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     3. TEAM SECTION
     ========================================================================== -->
<section class="team-section" id="team">
    <div class="container">
        <div class="text-center animate-on-scroll">
            <span class="section-label">Our Practitioners</span>
            <h2 class="section-heading">Meet Our <em>Healing</em> Team</h2>
            <p>Our certified masters and energy therapists bring deep wisdom, compassion, and specialized healing techniques to every session.</p>
        </div>

        <!-- Dynamic Team Carousel with Manual Drag and Nav Controls (Exact Database Members Only) -->
        <div class="team-carousel-outer">
            <button type="button" class="team-carousel-nav-btn prev-btn" id="teamCarouselPrev" aria-label="Scroll Team Cards Left">‹</button>
            <div class="team-carousel-wrapper" id="teamCarouselWrapper">
                <div class="team-carousel-track" id="teamCarouselTrack">
                    <?php foreach ($allTeam as $member): ?>
                        <div class="team-member-card">
                            <div class="team-img-box">
                                <img src="<?php echo htmlspecialchars(getTeamImgUrl($member['image'], 'assets/images/team/ananya-sharma.jpg')); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" loading="lazy">
                                <span class="badge badge-gold team-badge-tag">Reiki Master</span>
                            </div>
                            <div class="team-info-body">
                                <h3 class="team-name-title"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <span class="team-role-subtitle"><?php echo htmlspecialchars($member['role']); ?></span>
                                <p class="team-bio-short"><?php echo htmlspecialchars(substr($member['bio'], 0, 110)) . '...'; ?></p>
                                <div class="specialty-tags-list">
                                    <?php 
                                    $mSpecs = is_string($member['specialties']) ? json_decode($member['specialties'], true) : $member['specialties'];
                                    if (is_array($mSpecs)):
                                        foreach (array_slice($mSpecs, 0, 2) as $t):
                                    ?>
                                        <span class="specialty-tag-badge" style="font-size: 0.75rem; padding: 4px 10px;"><?php echo htmlspecialchars($t); ?></span>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="team-carousel-nav-btn next-btn" id="teamCarouselNext" aria-label="Scroll Team Cards Right">›</button>
        </div>
    </div>
</section>

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

        <div class="values-grid">
            <!-- Value 01 -->
            <div class="value-card animate-on-scroll">
                <div class="value-number">01</div>
                <h3 class="value-card-title"><?php echo htmlspecialchars($siteSettings['value_1_title'] ?? 'Authenticity'); ?></h3>
                <p class="value-card-text"><?php echo htmlspecialchars($siteSettings['value_1_desc'] ?? 'Rooted in traditional Usui Reiki lineage and pure spiritual energy practices without compromise or diluted shortcuts.'); ?></p>
            </div>

            <!-- Value 02 -->
            <div class="value-card animate-on-scroll">
                <div class="value-number">02</div>
                <h3 class="value-card-title"><?php echo htmlspecialchars($siteSettings['value_2_title'] ?? 'Compassion'); ?></h3>
                <p class="value-card-text"><?php echo htmlspecialchars($siteSettings['value_2_desc'] ?? 'Meeting every student and client without judgment, holding a safe and supportive space for deep emotional recovery and spiritual growth.'); ?></p>
            </div>

            <!-- Value 03 -->
            <div class="value-card animate-on-scroll">
                <div class="value-number">03</div>
                <h3 class="value-card-title"><?php echo htmlspecialchars($siteSettings['value_3_title'] ?? 'Empowerment'); ?></h3>
                <p class="value-card-text"><?php echo htmlspecialchars($siteSettings['value_3_desc'] ?? 'Equipping individuals with practical self-healing tools and knowledge to take control of their lifelong energy balance and well-being.'); ?></p>
            </div>

            <!-- Value 04 -->
            <div class="value-card animate-on-scroll">
                <div class="value-number">04</div>
                <h3 class="value-card-title"><?php echo htmlspecialchars($siteSettings['value_4_title'] ?? 'Community'); ?></h3>
                <p class="value-card-text"><?php echo htmlspecialchars($siteSettings['value_4_desc'] ?? 'Building an inclusive global family of practitioners and clients who uplift, support, and inspire each other\'s spiritual evolution.'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     5. CTA SECTION
     ========================================================================== -->
<?php include __DIR__ . '/includes/cta-section.php'; ?>

<!-- Include Footer Component -->
<?php include __DIR__ . '/includes/footer.php'; ?>
