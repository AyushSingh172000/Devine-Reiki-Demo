<?php
// Section 6: Instagram Reels Showcase
if (isset($sectionItem) && empty($sectionItem['visible'])) {
    return;
}
$reelsHeaderWrapStyle = '';
$reelsTitleBoxStyle = '';
if (($secAlign ?? 'left') === 'center') {
    $reelsHeaderWrapStyle = 'style="flex-direction: column; align-items: center; text-align: center; gap: 18px;"';
    $reelsTitleBoxStyle = 'style="display: flex; flex-direction: column; align-items: center;"';
} elseif (($secAlign ?? 'left') === 'right') {
    $reelsHeaderWrapStyle = 'style="flex-direction: row-reverse; text-align: right;"';
    $reelsTitleBoxStyle = 'style="display: flex; flex-direction: column; align-items: flex-end;"';
}

$instagram_reels = [
    [
        'id' => 'C7nY4MESYo7',
        'title' => '2-Min Sacred Exercise to Relieve Tension & Overthinking',
        'category' => 'Sacred Mudra'
    ],
    [
        'id' => 'C7gIJshyc6G',
        'title' => 'Sacred Mudra Science for Emotional Peace & Mental Clarity',
        'category' => 'Mudra Science'
    ],
    [
        'id' => 'C4La4vmSWaA',
        'title' => 'Reiki & Acupressure Points for Deep Restful Sleep',
        'category' => 'Aura Healing'
    ],
    [
        'id' => 'C6IZ9FcMKV0',
        'title' => 'Learn Reiki Healing: Cosmic Energy & Self-Healing Course',
        'category' => 'Spiritual Wisdom'
    ],
    [
        'id' => 'DZhpe4Cp_D7',
        'title' => 'Reiki Energy Channelization & Divine Blessings',
        'category' => 'Attunement'
    ],
    [
        'id' => 'C4xHuoQyzvp',
        'title' => '5-Minute Daily Mudra Ritual for Chakra Balance',
        'category' => 'Energy Reset'
    ]
];
?>
<section class="reels-section" id="instagram-reels">
    <div class="container">
        <!-- Section Header -->
        <div class="reels-header-wrap animate-on-scroll" <?php echo $reelsHeaderWrapStyle; ?>>
<?php
$reelsBadge = isset($getSecContent) ? $getSecContent('reels', 'badge', '@reiki_bliss · 104K Spiritual Seekers') : '@reiki_bliss · 104K Spiritual Seekers';
$reelsTitle = isset($getSecContent) ? $getSecContent('reels', 'title', 'Watch Our Healing <em>Reels &amp; Stories</em>') : 'Watch Our Healing <em>Reels &amp; Stories</em>';
$reelsDesc  = isset($getSecContent) ? $getSecContent('reels', 'desc', 'Daily energy resets, sacred mudras, and real healing wisdom shared by Reiki Grandmaster Anupama Agrawal. Tap any reel to play directly on this website.') : 'Daily energy resets, sacred mudras, and real healing wisdom shared by Reiki Grandmaster Anupama Agrawal. Tap any reel to play directly on this website.';
?>
            <div class="reels-title-box" <?php echo $reelsTitleBoxStyle; ?>>
                <div class="insta-live-pill">
                    <span class="insta-pulse-dot"></span>
                    <svg class="insta-gradient-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    <span><?php echo htmlspecialchars($reelsBadge); ?></span>
                </div>
                <h2 class="section-heading"><?php echo $reelsTitle; ?></h2>
                <p class="reels-subtext">
                    <?php echo htmlspecialchars($reelsDesc); ?>
                </p>
            </div>
            
            <div class="reels-header-cta-group">
                <a href="https://www.instagram.com/reiki_bliss/" target="_blank" rel="noopener noreferrer" class="btn-insta-brand">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    Follow on Instagram →
                </a>
            </div>
        </div>

        <!-- Reels Directly Playable Grid -->
        <div class="reels-cards-grid animate-on-scroll">
            <?php foreach ($instagram_reels as $idx => $reel): ?>
                <div class="reel-embed-card" data-reel-id="<?php echo htmlspecialchars($reel['id']); ?>">
                    <div class="reel-floating-header">
                        <span class="reel-badge-pill">✨ <?php echo htmlspecialchars($reel['category']); ?></span>
                        <span class="reel-brand-pill">
                            <span class="reel-pulse-dot"></span>
                            @reiki_bliss
                        </span>
                    </div>
                    <div class="reel-embed-frame">
                        <iframe 
                            class="reel-direct-iframe"
                            src="https://www.instagram.com/reel/<?php echo htmlspecialchars($reel['id']); ?>/embed/" 
                            frameborder="0" 
                            scrolling="no" 
                            allowtransparency="true" 
                            allowfullscreen="true" 
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                            loading="lazy"
                            title="<?php echo htmlspecialchars($reel['title']); ?>">
                        </iframe>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function() {
    var row = document.querySelector('.reels-cards-grid');
    var section = document.getElementById('instagram-reels');
    if (!row) return;

    var isHovered = false;
    var isVideoPlaying = false;
    var videoStartTime = 0;
    var speed = 0.8;
    var pos = 0;

    // 1. Pause on hover over reels section, resume when mouse leaves
    if (section) {
        section.addEventListener('mouseenter', function() { isHovered = true; });
        section.addEventListener('mouseleave', function() { 
            isHovered = false; 
            if (isVideoPlaying && (Date.now() - videoStartTime > 5000)) {
                isVideoPlaying = false;
            }
        });
    }

    // 2. Pause on touch
    row.addEventListener('touchstart', function() { isHovered = true; }, { passive: true });
    row.addEventListener('touchend', function() {
        setTimeout(function() { isHovered = false; }, 2000);
    }, { passive: true });

    // 3. Immediate window blur listener when clicking into an iframe
    window.addEventListener('blur', function() {
        setTimeout(function() {
            var active = document.activeElement;
            if (active && (active.tagName === 'IFRAME' || row.contains(active))) {
                isVideoPlaying = true;
                videoStartTime = Date.now();
            }
        }, 50);
    });

    // 4. Click outside reels section allows resuming scroll
    document.addEventListener('click', function(e) {
        if (!row.contains(e.target)) {
            isVideoPlaying = false;
        }
    });

    // 5. 60fps auto-scroll engine with active frame guard
    function step() {
        var active = document.activeElement;
        if (active && active.tagName === 'IFRAME' && row.contains(active)) {
            if (!isVideoPlaying) {
                isVideoPlaying = true;
                videoStartTime = Date.now();
            }
        }

        if (isVideoPlaying && videoStartTime > 0 && (Date.now() - videoStartTime > 25000)) {
            isVideoPlaying = false;
            videoStartTime = 0;
        }

        if (!isHovered && !isVideoPlaying) {
            pos += speed;
            if (pos >= 1) {
                var p = Math.floor(pos);
                row.scrollLeft += p;
                pos -= p;

                var max = row.scrollWidth - row.clientWidth;
                if (max > 0 && row.scrollLeft >= max - 2) {
                    row.scrollLeft = 0;
                }
            }
        }

        requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
})();
</script>
