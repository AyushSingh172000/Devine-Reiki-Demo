/**
 * Super Admin Master Control Engine
 * Handles Drag & Drop reordering, alignment switching, live preview & AJAX sync
 */

document.addEventListener('DOMContentLoaded', function() {
    initLucideIcons();
    initSectionReorderEngine();
    initAlignmentSelectors();
    initVisibilityToggles();
});

function initLucideIcons() {
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

/**
 * Homepage Section Reorder Engine
 */
function initSectionReorderEngine() {
    const list = document.getElementById('sectionReorderList');
    if (!list) return;

    // Up / Down Button Handlers
    list.addEventListener('click', function(e) {
        const btn = e.target.closest('.reorder-btn');
        if (!btn) return;

        const card = btn.closest('.section-reorder-card');
        if (!card) return;

        if (btn.classList.contains('btn-move-up')) {
            const prev = card.previousElementSibling;
            if (prev) {
                list.insertBefore(card, prev);
                updateOrderNumbers();
            }
        } else if (btn.classList.contains('btn-move-down')) {
            const next = card.nextElementSibling;
            if (next) {
                list.insertBefore(next, card);
                updateOrderNumbers();
            }
        }
    });

    // Drag and Drop (HTML5 Drag & Drop API)
    let draggedItem = null;

    const cards = list.querySelectorAll('.section-reorder-card');
    cards.forEach(card => {
        card.setAttribute('draggable', 'true');

        card.addEventListener('dragstart', function(e) {
            draggedItem = card;
            card.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', function() {
            draggedItem = null;
            card.style.opacity = '1';
            updateOrderNumbers();
        });

        card.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const targetCard = e.target.closest('.section-reorder-card');
            if (targetCard && targetCard !== draggedItem) {
                const rect = targetCard.getBoundingClientRect();
                const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                list.insertBefore(draggedItem, next ? targetCard.nextSibling : targetCard);
            }
        });
    });

    updateOrderNumbers();
}

/**
 * Updates order numbers and Up/Down button states
 */
function updateOrderNumbers() {
    const list = document.getElementById('sectionReorderList');
    if (!list) return;

    const cards = list.querySelectorAll('.section-reorder-card');
    cards.forEach((card, index) => {
        const numBadge = card.querySelector('.section-order-badge');
        if (numBadge) {
            numBadge.textContent = (index + 1);
        }

        const upBtn = card.querySelector('.btn-move-up');
        const downBtn = card.querySelector('.btn-move-down');

        if (upBtn) upBtn.disabled = (index === 0);
        if (downBtn) downBtn.disabled = (index === cards.length - 1);

        // Update hidden order input
        const orderInp = card.querySelector('input.section-order-input');
        if (orderInp) {
            orderInp.value = index + 1;
        }
    });
}

/**
 * Section Heading Alignment Selector
 */
function initAlignmentSelectors() {
    document.querySelectorAll('.align-selector-group').forEach(group => {
        group.addEventListener('click', function(e) {
            const btn = e.target.closest('.align-btn');
            if (!btn) return;

            const alignVal = btn.getAttribute('data-align');
            const hiddenInp = group.querySelector('input.section-align-input');
            if (hiddenInp) {
                hiddenInp.value = alignVal;
            }

            group.querySelectorAll('.align-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Visual feedback indicator on card
            const card = group.closest('.section-reorder-card');
            if (card) {
                const alignTag = card.querySelector('.section-align-indicator');
                if (alignTag) {
                    alignTag.textContent = alignVal.toUpperCase();
                }
            }
        });
    });
}

/**
 * Section Visibility Toggles
 */
function initVisibilityToggles() {
    document.querySelectorAll('.section-visibility-toggle').forEach(chk => {
        chk.addEventListener('change', function() {
            const card = chk.closest('.section-reorder-card');
            if (!card) return;

            if (chk.checked) {
                card.classList.remove('disabled-section');
            } else {
                card.classList.add('disabled-section');
            }
        });
    });
}
