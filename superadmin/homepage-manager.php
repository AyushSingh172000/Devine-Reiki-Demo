<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Homepage Section Architecture';

$successMsg = '';
$errorMsg = '';

// Default section metadata and fallbacks
$sectionDefinitions = [
    'hero' => [
        'name' => 'Hero Banner & Trust Numbers',
        'desc' => 'Primary video/slide banner, location badge, dual headlines, subtext description, and key stats bar.',
        'badge' => 'HERO BANNER',
        'allow_align' => false, // Hero heading is fixed centered by layout design
        'default_align' => 'center',
        'default_tag' => 'Ahmedabad, India & Global Distance Healing',
        'default_title' => 'Harmonize Your Mind, Body & Soul<br><span class="hero-gold-text">With Ancient Energy</span>',
        'default_desc' => 'Guided by <strong>Grand Master Ms Anupama Agrawal</strong>: offering Reiki, Chakra Balancing, Guided Meditations, Other Healings & more.'
    ],
    'services' => [
        'name' => 'Holistic Services & Modalities',
        'desc' => 'Core Reiki healing sessions, session duration badges, pricing, and book session buttons.',
        'badge' => 'SERVICES',
        'allow_align' => true,
        'default_align' => 'left',
        'default_tag' => 'Holistic Healing Modalities',
        'default_title' => 'Our Core <em>Services</em>',
        'default_desc' => 'Experience personalized Reiki healing, chakra alignment, and aura cleansing guided by Grandmaster Anupama Agrawal to restore physical vitality and spiritual harmony.'
    ],
    'testimonials' => [
        'name' => 'Testimonials & Client Stories',
        'desc' => 'Student reviews, 5-star ratings, author thumbnails, and interactive testimonial navigation slider.',
        'badge' => 'REVIEWS',
        'allow_align' => true,
        'default_align' => 'center',
        'default_tag' => 'STORIES OF HEALING',
        'default_title' => 'What Our Students &amp; Clients <em>Say</em>',
        'default_desc' => 'Read real life experiences from individuals who restored harmony, vitality, and peace through our Reiki sessions.'
    ],
    'courses' => [
        'name' => 'Certified Energy Courses',
        'desc' => 'Training levels, course previews, pricing notes, and curriculum syllabus detail buttons.',
        'badge' => 'COURSES',
        'allow_align' => true,
        'default_align' => 'left',
        'default_tag' => 'Certified Energy Training',
        'default_title' => 'Explore Reiki &amp; Healing <em>Courses</em>',
        'default_desc' => 'Become a certified Reiki healer yourself. Structured curriculum with authentic attunement (Diksha), physical manual, lifetime mentorship, and recognized certificates.'
    ],
    'products' => [
        'name' => 'Sacred Products & Bracelets',
        'desc' => 'Reiki charged crystals, discount badges, astrology bracelet catalog, and shop links.',
        'badge' => 'PRODUCTS',
        'allow_align' => true,
        'default_align' => 'left',
        'default_tag' => 'Sacred Crystal Energy',
        'default_title' => 'Featured Reiki Charged <em>Products</em>',
        'default_desc' => 'Energized astrological bracelets, natural healing crystals, and sacred gemstone artifacts charged with high-frequency Reiki symbols to amplify protection, prosperity, and peace.'
    ],
    'reels' => [
        'name' => 'Instagram Reels Showcase',
        'desc' => 'Directly playable Instagram video reels theater, pulse indicators, and follower link badge.',
        'badge' => 'REELS',
        'allow_align' => true,
        'default_align' => 'left',
        'default_tag' => '@reiki_bliss · 104K Spiritual Seekers',
        'default_title' => 'Watch Our Healing <em>Reels &amp; Stories</em>',
        'default_desc' => 'Daily energy resets, sacred mudras, and real healing wisdom shared by Reiki Grandmaster Anupama Agrawal. Tap any reel to play directly on this website.'
    ],
    'cta_banner' => [
        'name' => 'Healing Journey CTA Banner',
        'desc' => 'Atmospheric healing banner with "First Session is Free", headline, description, background image, and golden "Book Free Session" action button.',
        'badge' => 'CTA BANNER',
        'allow_align' => true,
        'default_align' => 'center',
        'default_tag' => 'FIRST SESSION IS FREE',
        'default_title' => 'Begin Your <em>Healing Journey</em> Today',
        'default_desc' => 'Take the first step. Meet Ms Anupama Agrawal and discover which modality resonates with your soul.',
        'default_bg_image' => 'assets/images/cta-bg.jpg',
        'default_btn_text' => 'Book Free Session →',
        'default_btn_url' => !empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : '#booking'
    ],
];

// Handle Reset Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_default') {
    $defaultLayout = [];
    foreach ($sectionDefinitions as $id => $meta) {
        $secItem = [
            'id' => $id,
            'name' => $meta['name'],
            'visible' => true,
            'align' => $meta['default_align'],
            'badge' => $meta['default_tag'],
            'title' => $meta['default_title'],
            'desc' => $meta['default_desc']
        ];
        if (!empty($meta['default_bg_image'])) {
            $secItem['bg_image'] = $meta['default_bg_image'];
        }
        if (!empty($meta['default_btn_text'])) {
            $secItem['btn_text'] = $meta['default_btn_text'];
        }
        if (!empty($meta['default_btn_url'])) {
            $secItem['btn_url'] = $meta['default_btn_url'];
        }
        $defaultLayout[] = $secItem;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_homepage_layout', json_encode($defaultLayout)]);
        $siteSettings['superadmin_homepage_layout'] = json_encode($defaultLayout);
        $successMsg = "Homepage layout architecture, headings, descriptions, and CTA settings successfully reset to factory defaults!";
    } catch (\PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

// Handle Save Layout Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_layout') {
    $sectionIds = $_POST['section_ids'] ?? [];
    $visibilities = $_POST['visible'] ?? [];
    $alignments = $_POST['align'] ?? [];
    $badges = $_POST['badge'] ?? [];
    $titles = $_POST['title'] ?? [];
    $descs = $_POST['desc'] ?? [];
    $bgImages = $_POST['bg_image'] ?? [];
    $btnTexts = $_POST['btn_text'] ?? [];
    $btnUrls = $_POST['btn_url'] ?? [];

    $newLayout = [];
    foreach ($sectionIds as $idx => $id) {
        if (!isset($sectionDefinitions[$id])) continue;

        $isVisible = isset($visibilities[$id]) && ($visibilities[$id] === '1' || $visibilities[$id] === 'on');
        $align = $alignments[$id] ?? ($sectionDefinitions[$id]['default_align'] ?? 'left');
        if (!in_array($align, ['left', 'center', 'right'])) {
            $align = 'left';
        }

        $badge = isset($badges[$id]) ? trim(strip_tags($badges[$id])) : '';
        $title = isset($titles[$id]) ? trim(strip_tags($titles[$id], '<em><strong><span><br><b><i>')) : '';
        $desc = isset($descs[$id]) ? trim(strip_tags($descs[$id], '<em><strong><span><br><b><i>')) : '';

        $secItem = [
            'id' => $id,
            'name' => $sectionDefinitions[$id]['name'],
            'visible' => $isVisible,
            'align' => $align,
            'badge' => $badge,
            'title' => $title,
            'desc' => $desc
        ];

        if ($id === 'cta_banner') {
            $secItem['bg_image'] = isset($bgImages[$id]) && trim($bgImages[$id]) !== '' ? trim(strip_tags($bgImages[$id])) : ($sectionDefinitions[$id]['default_bg_image'] ?? 'assets/images/cta-bg.jpg');
            $secItem['btn_text'] = isset($btnTexts[$id]) && trim($btnTexts[$id]) !== '' ? trim(strip_tags($btnTexts[$id])) : ($sectionDefinitions[$id]['default_btn_text'] ?? 'Book Free Session →');
            $secItem['btn_url'] = isset($btnUrls[$id]) ? trim(strip_tags($btnUrls[$id])) : '';
        }

        $newLayout[] = $secItem;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_homepage_layout', json_encode($newLayout)]);
        $siteSettings['superadmin_homepage_layout'] = json_encode($newLayout);

        // Synchronize section heading keys to site_settings
        $settingSyncStmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($newLayout as $sec) {
            $sId = $sec['id'] ?? '';
            if (in_array($sId, ['services', 'courses', 'products', 'testimonials'])) {
                $settingSyncStmt->execute(["home_{$sId}_badge", $sec['badge'] ?? '']);
                $settingSyncStmt->execute(["home_{$sId}_title", $sec['title'] ?? '']);
                $settingSyncStmt->execute(["home_{$sId}_desc", $sec['desc'] ?? '']);
                $siteSettings["home_{$sId}_badge"] = $sec['badge'] ?? '';
                $siteSettings["home_{$sId}_title"] = $sec['title'] ?? '';
                $siteSettings["home_{$sId}_desc"] = $sec['desc'] ?? '';
            }
        }

        $successMsg = "Homepage section architecture, headings, and descriptions updated successfully! Live immediately on the homepage.";
    } catch (\PDOException $e) {
        $errorMsg = "Failed to save homepage layout: " . $e->getMessage();
    }
}

// Load current layout
$currentLayout = [];
if (!empty($siteSettings['superadmin_homepage_layout'])) {
    $currentLayout = json_decode($siteSettings['superadmin_homepage_layout'], true) ?: [];
}

// Ensure all sections are present in current layout
$existingIds = array_column($currentLayout, 'id');
foreach ($sectionDefinitions as $secKey => $secDef) {
    if (!in_array($secKey, $existingIds)) {
        $currentLayout[] = [
            'id' => $secKey,
            'name' => $secDef['name'],
            'visible' => true,
            'align' => $secDef['default_align'],
            'badge' => $secDef['default_tag'],
            'title' => $secDef['default_title'],
            'desc' => $secDef['default_desc']
        ];
    }
}

include __DIR__ . '/includes/superadmin-header.php';
?>

<?php if (!empty($successMsg)): ?>
    <div class="sa-alert sa-alert-success">
        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div><?= htmlspecialchars($successMsg) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="sa-alert sa-alert-error">
        <i data-lucide="alert-octagon" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div><?= htmlspecialchars($errorMsg) ?></div>
    </div>
<?php endif; ?>

<div class="sa-card">
    <div class="sa-card-header">
        <div>
            <h2 class="sa-card-title">
                <i data-lucide="layers" style="color: var(--sa-gold);"></i>
                Section Ordering, Headings, Descriptions &amp; Alignments
            </h2>
            <p class="sa-card-desc">
                Drag cards or use <strong>▲ Move Up / ▼ Move Down</strong> to reorder sections. Edit each section's <strong>Heading, Badge &amp; Description</strong> directly below. Use switches to show/hide sections, and click <strong>Left / Center / Right</strong> to control heading alignments.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <form method="POST" onsubmit="return confirm('Reset all homepage sections, headings, and descriptions to original factory defaults?');">
                <input type="hidden" name="action" value="reset_default">
                <button type="submit" class="sa-btn sa-btn-outline" style="font-size: 0.82rem; padding: 8px 14px;">
                    <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i> Reset to Default
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="homepage-manager.php" id="homepageLayoutForm">
        <input type="hidden" name="action" value="save_layout">

        <div class="section-reorder-list" id="sectionReorderList">
            <?php foreach ($currentLayout as $pos => $sec): 
                $id = $sec['id'];
                $meta = $sectionDefinitions[$id] ?? [
                    'name' => ucfirst($id),
                    'desc' => '',
                    'badge' => strtoupper($id),
                    'allow_align' => true,
                    'default_align' => 'left',
                    'default_tag' => '',
                    'default_title' => '',
                    'default_desc' => ''
                ];
                $isVisible = !empty($sec['visible']);
                $align = $sec['align'] ?? $meta['default_align'];
                $activeBadge = $sec['badge'] ?? $meta['default_tag'];
                $activeTitle = $sec['title'] ?? $meta['default_title'];
                $activeDesc = $sec['desc'] ?? $meta['default_desc'];
            ?>
                <div class="section-reorder-card <?= !$isVisible ? 'disabled-section' : '' ?>" data-section-id="<?= htmlspecialchars($id) ?>" style="flex-direction: column; align-items: stretch; gap: 0;">
                    <input type="hidden" name="section_ids[]" value="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" class="section-order-input" name="order[<?= htmlspecialchars($id) ?>]" value="<?= $pos + 1 ?>">

                    <!-- Top Controls Bar -->
                    <div style="display: flex; align-items: center; gap: 16px; width: 100%; flex-wrap: wrap;">
                        <!-- Drag Handle -->
                        <div class="section-handle" title="Drag to reorder">
                            <i data-lucide="grip-vertical" style="width: 20px; height: 20px;"></i>
                        </div>

                        <!-- Up / Down Reorder Buttons -->
                        <div class="reorder-btns-col">
                            <button type="button" class="reorder-btn btn-move-up" title="Move Up" <?= $pos === 0 ? 'disabled' : '' ?>>
                                <i data-lucide="chevron-up" style="width: 16px; height: 16px;"></i>
                            </button>
                            <button type="button" class="reorder-btn btn-move-down" title="Move Down" <?= $pos === count($currentLayout) - 1 ? 'disabled' : '' ?>>
                                <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>

                        <!-- Order Number Badge -->
                        <div class="section-order-badge"><?= $pos + 1 ?></div>

                        <!-- Section Details -->
                        <div class="section-meta-info" style="flex: 1; min-width: 200px;">
                            <div class="section-meta-title">
                                <?= htmlspecialchars($meta['name']) ?>
                                <span class="section-meta-tag"><?= htmlspecialchars($meta['badge']) ?></span>
                                <?php if ($meta['allow_align']): ?>
                                    <span class="section-meta-tag section-align-indicator" style="color: var(--sa-gold); border: 1px solid var(--sa-border-gold); background: var(--sa-gold-glow);">
                                        <?= strtoupper($align) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="section-meta-desc"><?= htmlspecialchars($meta['desc']) ?></div>
                        </div>

                        <!-- Heading Alignment Selector Chips -->
                        <?php if ($meta['allow_align']): ?>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <span style="font-size: 0.7rem; color: var(--sa-text-dim); font-weight: 600; text-transform: uppercase;">Heading Alignment</span>
                                <div class="align-selector-group">
                                    <input type="hidden" class="section-align-input" name="align[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($align) ?>">
                                    <button type="button" class="align-btn <?= $align === 'left' ? 'active' : '' ?>" data-align="left" title="Align Left">
                                        <i data-lucide="align-left" style="width: 14px; height: 14px;"></i> Left
                                    </button>
                                    <button type="button" class="align-btn <?= $align === 'center' ? 'active' : '' ?>" data-align="center" title="Align Center">
                                        <i data-lucide="align-center" style="width: 14px; height: 14px;"></i> Center
                                    </button>
                                    <button type="button" class="align-btn <?= $align === 'right' ? 'active' : '' ?>" data-align="right" title="Align Right">
                                        <i data-lucide="align-right" style="width: 14px; height: 14px;"></i> Right
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 0.75rem; color: var(--sa-text-dim); font-style: italic;">
                                Centered by layout
                            </div>
                        <?php endif; ?>

                        <!-- Visibility Toggle -->
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding-left: 10px; border-left: 1px solid var(--sa-border);">
                            <span style="font-size: 0.7rem; color: var(--sa-text-dim); font-weight: 600; text-transform: uppercase;">Show on Site</span>
                            <label class="sa-switch">
                                <input type="checkbox" class="section-visibility-toggle" name="visible[<?= htmlspecialchars($id) ?>]" value="1" <?= $isVisible ? 'checked' : '' ?>>
                                <span class="sa-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Section Content Editor (Badge, Heading & Description) -->
                    <div style="margin-top: 14px; padding: 12px 14px; border-top: 1px dashed var(--sa-border); width: 100%; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; background: rgba(0,0,0,0.22); border-radius: 8px;">
                        <div>
                            <label style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; margin-bottom: 5px;">
                                <span>Section Badge / Tag</span>
                                <span style="font-size: 0.68rem; color: var(--sa-cyan); font-weight: 500;">Top small label</span>
                            </label>
                            <input type="text" name="badge[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($activeBadge) ?>" class="sa-input" style="font-size: 0.82rem; padding: 6px 10px;" placeholder="<?= htmlspecialchars($meta['default_tag'] ?? '') ?>">
                        </div>

                        <div>
                            <label style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 5px;">
                                <span>Section Heading</span>
                                <span style="font-size: 0.68rem; color: var(--sa-gold); font-weight: 500;">Allows &lt;em&gt; &lt;span&gt;</span>
                            </label>
                            <input type="text" name="title[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($activeTitle) ?>" class="sa-input" style="font-size: 0.82rem; padding: 6px 10px;" placeholder="<?= htmlspecialchars($meta['default_title'] ?? '') ?>">
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 700; color: var(--sa-text-muted); text-transform: uppercase; margin-bottom: 5px;">
                                <span>Section Subtext / Description</span>
                                <span style="font-size: 0.68rem; color: var(--sa-text-dim); font-weight: 500;">Subtitle paragraph</span>
                            </label>
                            <textarea name="desc[<?= htmlspecialchars($id) ?>]" class="sa-input" rows="2" style="font-size: 0.82rem; padding: 6px 10px; resize: vertical;" placeholder="<?= htmlspecialchars($meta['default_desc'] ?? '') ?>"><?= htmlspecialchars($activeDesc) ?></textarea>
                        </div>

                        <?php if ($id === 'cta_banner'): 
                            $activeBgImage = $sec['bg_image'] ?? ($meta['default_bg_image'] ?? 'assets/images/cta-bg.jpg');
                            $activeBtnText = $sec['btn_text'] ?? ($meta['default_btn_text'] ?? 'Book Free Session →');
                            $activeBtnUrl  = $sec['btn_url'] ?? ($meta['default_btn_url'] ?? (!empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : '#'));
                        ?>
                            <div style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; padding-top: 12px; margin-top: 4px; border-top: 1px dashed rgba(200, 155, 60, 0.25);">
                                <div>
                                    <label style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 5px;">
                                        <span>Background Image URL / Path</span>
                                        <span style="font-size: 0.68rem; color: var(--sa-text-dim); font-weight: 500;">Spiritual Hands Aura BG</span>
                                    </label>
                                    <input type="text" name="bg_image[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($activeBgImage) ?>" class="sa-input" style="font-size: 0.82rem; padding: 6px 10px;" placeholder="assets/images/cta-bg.jpg">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 6px;">
                                        <span style="font-size: 0.7rem; color: var(--sa-text-dim);">Live BG Preview:</span>
                                        <img src="../<?= htmlspecialchars($activeBgImage) ?>" alt="CTA BG Preview" style="height: 34px; width: 72px; object-fit: cover; border-radius: 4px; border: 1px solid var(--sa-border-gold);" onerror="this.style.display='none'">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 5px;">
                                            <span>Golden Button Text</span>
                                        </label>
                                        <input type="text" name="btn_text[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($activeBtnText) ?>" class="sa-input" style="font-size: 0.82rem; padding: 6px 10px;" placeholder="Book Free Session →">
                                    </div>
                                    <div>
                                        <label style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 700; color: var(--sa-gold); text-transform: uppercase; margin-bottom: 5px;">
                                            <span>Button Destination URL</span>
                                        </label>
                                        <input type="text" name="btn_url[<?= htmlspecialchars($id) ?>]" value="<?= htmlspecialchars($activeBtnUrl) ?>" class="sa-input" style="font-size: 0.82rem; padding: 6px 10px;" placeholder="<?= htmlspecialchars(!empty($siteSettings['booking_url']) ? $siteSettings['booking_url'] : '#') ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sticky Save Action Bar -->
        <div class="sa-sticky-actions">
            <div>
                <span style="font-size: 0.88rem; font-weight: 600; color: #ffffff;">Save Section Order &amp; Alignments</span>
                <p style="font-size: 0.75rem; color: var(--sa-text-muted); margin: 0;">Applies instant reordering and heading alignment updates directly to the live homepage.</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="../index.php" target="_blank" class="sa-btn sa-btn-outline">
                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i> Preview Live Homepage
                </a>
                <button type="submit" class="sa-btn sa-btn-gold">
                    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Publish Section Layout
                </button>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
