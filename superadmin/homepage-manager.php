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
        'default_align' => 'center'
    ],
    'services' => [
        'name' => 'Holistic Services & Modalities',
        'desc' => 'Core Reiki healing sessions, session duration badges, pricing, and book session buttons.',
        'badge' => 'SERVICES',
        'allow_align' => true,
        'default_align' => 'left'
    ],
    'testimonials' => [
        'name' => 'Testimonials & Client Stories',
        'desc' => 'Student reviews, 5-star ratings, author thumbnails, and interactive testimonial navigation slider.',
        'badge' => 'REVIEWS',
        'allow_align' => true,
        'default_align' => 'center'
    ],
    'courses' => [
        'name' => 'Certified Energy Courses',
        'desc' => 'Training levels, course previews, pricing notes, and curriculum syllabus detail buttons.',
        'badge' => 'COURSES',
        'allow_align' => true,
        'default_align' => 'left'
    ],
    'products' => [
        'name' => 'Sacred Products & Bracelets',
        'desc' => 'Reiki charged crystals, discount badges, astrology bracelet catalog, and shop links.',
        'badge' => 'PRODUCTS',
        'allow_align' => true,
        'default_align' => 'left'
    ],
    'reels' => [
        'name' => 'Instagram Reels Showcase',
        'desc' => 'Directly playable Instagram video reels theater, pulse indicators, and follower link badge.',
        'badge' => 'REELS',
        'allow_align' => true,
        'default_align' => 'left'
    ],
    'cta_banner' => [
        'name' => 'Healing Journey CTA Banner',
        'desc' => 'Light luxury card with "First Session is Free", headline, description, and "Book Free Session" action button.',
        'badge' => 'CTA BANNER',
        'allow_align' => true,
        'default_align' => 'left'
    ],
];

// Handle Reset Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_default') {
    $defaultLayout = [
        ['id' => 'hero', 'name' => 'Hero Banner & Trust Numbers', 'visible' => true, 'align' => 'center'],
        ['id' => 'services', 'name' => 'Holistic Services & Modalities', 'visible' => true, 'align' => 'left'],
        ['id' => 'testimonials', 'name' => 'Testimonials & Client Stories', 'visible' => true, 'align' => 'center'],
        ['id' => 'courses', 'name' => 'Certified Energy Courses', 'visible' => true, 'align' => 'left'],
        ['id' => 'products', 'name' => 'Sacred Products & Bracelets', 'visible' => true, 'align' => 'left'],
        ['id' => 'reels', 'name' => 'Instagram Reels Showcase', 'visible' => true, 'align' => 'left'],
        ['id' => 'cta_banner', 'name' => 'Healing Journey CTA Banner', 'visible' => true, 'align' => 'left']
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_homepage_layout', json_encode($defaultLayout)]);
        $siteSettings['superadmin_homepage_layout'] = json_encode($defaultLayout);
        $successMsg = "Homepage layout architecture successfully reset to factory defaults!";
    } catch (\PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

// Handle Save Layout Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_layout') {
    $sectionIds = $_POST['section_ids'] ?? [];
    $visibilities = $_POST['visible'] ?? [];
    $alignments = $_POST['align'] ?? [];

    $newLayout = [];
    foreach ($sectionIds as $idx => $id) {
        if (!isset($sectionDefinitions[$id])) continue;

        $isVisible = isset($visibilities[$id]) && ($visibilities[$id] === '1' || $visibilities[$id] === 'on');
        $align = $alignments[$id] ?? ($sectionDefinitions[$id]['default_align'] ?? 'left');
        if (!in_array($align, ['left', 'center', 'right'])) {
            $align = 'left';
        }

        $newLayout[] = [
            'id' => $id,
            'name' => $sectionDefinitions[$id]['name'],
            'visible' => $isVisible,
            'align' => $align
        ];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_homepage_layout', json_encode($newLayout)]);
        $siteSettings['superadmin_homepage_layout'] = json_encode($newLayout);
        $successMsg = "Homepage section architecture & heading alignments saved successfully! Changes are live immediately.";
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
            'align' => $secDef['default_align']
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
                Section Ordering, Visibility &amp; Heading Alignments
            </h2>
            <p class="sa-card-desc">
                Drag cards or use <strong>▲ Move Up / ▼ Move Down</strong> to reorder sections. Use the switches to show/hide sections, and click <strong>Left / Center / Right</strong> to control section heading alignments.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <form method="POST" onsubmit="return confirm('Reset all homepage sections to original factory order and alignments?');">
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
                    'default_align' => 'left'
                ];
                $isVisible = !empty($sec['visible']);
                $align = $sec['align'] ?? $meta['default_align'];
            ?>
                <div class="section-reorder-card <?= !$isVisible ? 'disabled-section' : '' ?>" data-section-id="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" name="section_ids[]" value="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" class="section-order-input" name="order[<?= htmlspecialchars($id) ?>]" value="<?= $pos + 1 ?>">

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
                    <div class="section-meta-info">
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
