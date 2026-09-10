/**
 * Shree Sai Reiki Healing Center — Admin Panel Core Interactions
 * File: admin/assets/js/admin.js
 */

document.addEventListener('DOMContentLoaded', () => {
  initResponsiveSidebar();
  initSlugAutoGeneration();
  initCharacterCounters();
  initTagInputs();
  initStarRatingInputs();
  initRichTextToolbars();
  initImageDragAndDrop();
  initModalSystem();
  initConfirmDelete();
  initClientTableSearch();
  initAlertAutoHide();
});

/* ==========================================================================
   1. RESPONSIVE SIDEBAR & OVERLAY
   ========================================================================== */
function initResponsiveSidebar() {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.getElementById('sidebarToggle');
  let overlay = document.getElementById('adminSidebarOverlay');

  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'adminSidebarOverlay';
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
  }

  function openSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (overlay) overlay.classList.add('active');
    document.body.classList.add('sidebar-open');
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.classList.remove('sidebar-open');
  }

  if (toggle && sidebar) {
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    overlay.addEventListener('click', closeSidebar);

    // Close on navigation link click when on mobile
    sidebar.querySelectorAll('.sidebar-nav a').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 768) closeSidebar();
      });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });
  }
}

/* ==========================================================================
   2. SLUG AUTO-GENERATION
   ========================================================================== */
function generateSlug(text) {
  return text.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

function initSlugAutoGeneration() {
  const titleInputs = document.querySelectorAll('input#title, input[name="title"], input[name="name"], [data-slug-source]');
  
  titleInputs.forEach(titleInput => {
    // Find matching slug input in the same form
    const form = titleInput.closest('form');
    if (!form) return;
    const slugInput = form.querySelector('input#slug, input[name="slug"], [data-slug-target]');
    if (!slugInput) return;

    let autoSlug = generateSlug(titleInput.value);
    let userModified = slugInput.value.trim() !== '' && slugInput.value.trim() !== autoSlug;

    slugInput.addEventListener('input', () => {
      // If user clears or types custom slug
      userModified = slugInput.value.trim() !== '' && slugInput.value.trim() !== generateSlug(titleInput.value);
    });

    titleInput.addEventListener('input', () => {
      if (!userModified || slugInput.value.trim() === '') {
        const newSlug = generateSlug(titleInput.value);
        slugInput.value = newSlug;
      }
    });
  });
}

/* ==========================================================================
   3. CHARACTER COUNTERS
   ========================================================================== */
function initCharacterCounters() {
  const textareas = document.querySelectorAll('textarea[data-maxlength], textarea[maxlength]');
  
  textareas.forEach(textarea => {
    const max = parseInt(textarea.getAttribute('data-maxlength') || textarea.getAttribute('maxlength'), 10);
    if (!max) return;

    // Check if counter element already exists
    let counter = textarea.parentElement.querySelector('.char-counter');
    if (!counter) {
      counter = document.createElement('div');
      counter.className = 'char-counter';
      textarea.parentNode.insertBefore(counter, textarea.nextSibling);
    }

    function updateCounter() {
      const currentLength = textarea.value.length;
      counter.textContent = `${currentLength} / ${max} characters`;
      
      const ratio = currentLength / max;
      if (ratio >= 1.0) {
        counter.className = 'char-counter limit-exceeded';
      } else if (ratio >= 0.85) {
        counter.className = 'char-counter limit-warning';
      } else {
        counter.className = 'char-counter';
      }
    }

    textarea.addEventListener('input', updateCounter);
    updateCounter();
  });
}

/* ==========================================================================
   4. TAG INPUT SYSTEM (Blog Tags, Team Specialties)
   ========================================================================== */
function initTagInputs() {
  const wrappers = document.querySelectorAll('.tag-input-wrapper');

  wrappers.forEach(wrapper => {
    const hiddenInput = wrapper.querySelector('input[type="hidden"]');
    const textInput = wrapper.querySelector('.tag-text-input') || wrapper.querySelector('input[type="text"]');
    let container = wrapper.querySelector('.tags-container');

    if (!hiddenInput || !textInput) return;

    if (!container) {
      container = document.createElement('div');
      container.className = 'tags-container';
      wrapper.insertBefore(container, textInput);
    }

    let tags = [];

    // Parse initial value from hidden input (support JSON or CSV)
    const rawVal = hiddenInput.value.trim();
    if (rawVal) {
      if (rawVal.startsWith('[') && rawVal.endsWith(']')) {
        try {
          tags = JSON.parse(rawVal).map(t => String(t).trim()).filter(Boolean);
        } catch (e) {
          tags = rawVal.split(',').map(t => t.trim()).filter(Boolean);
        }
      } else {
        tags = rawVal.split(',').map(t => t.trim()).filter(Boolean);
      }
    }

    function renderTags() {
      container.innerHTML = '';
      tags.forEach((tag, idx) => {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `<span>${escapeHtml(tag)}</span><button type="button" class="tag-remove" data-index="${idx}" aria-label="Remove tag">×</button>`;
        container.appendChild(chip);
      });
      // Store back to hidden input as CSV
      hiddenInput.value = tags.join(', ');
    }

    function addTag(text) {
      const clean = text.trim().replace(/^,+|,+$/g, '');
      if (clean && !tags.includes(clean)) {
        tags.push(clean);
        renderTags();
      }
      textInput.value = '';
    }

    textInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(textInput.value);
      } else if (e.key === 'Backspace' && textInput.value === '' && tags.length > 0) {
        tags.pop();
        renderTags();
      }
    });

    textInput.addEventListener('blur', () => {
      if (textInput.value.trim()) {
        addTag(textInput.value);
      }
    });

    container.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('.tag-remove');
      if (removeBtn) {
        e.preventDefault();
        const idx = parseInt(removeBtn.getAttribute('data-index'), 10);
        if (!isNaN(idx) && idx >= 0 && idx < tags.length) {
          tags.splice(idx, 1);
          renderTags();
        }
      }
    });

    renderTags();
  });
}

/* ==========================================================================
   5. STAR RATING INPUT
   ========================================================================== */
function initStarRatingInputs() {
  const ratingWidgets = document.querySelectorAll('.star-rating-widget');

  ratingWidgets.forEach(widget => {
    const hiddenInput = widget.querySelector('input[type="hidden"]') || widget.closest('.form-group')?.querySelector('input[name="rating"]');
    let label = widget.querySelector('.star-rating-label');
    const stars = widget.querySelectorAll('.star-btn, .star-icon');

    if (!hiddenInput || stars.length === 0) return;

    if (!label) {
      label = document.createElement('span');
      label.className = 'star-rating-label';
      widget.appendChild(label);
    }

    function updateStars(val) {
      stars.forEach(star => {
        const starVal = parseInt(star.getAttribute('data-value'), 10);
        if (starVal <= val) {
          star.classList.add('active');
          star.textContent = '★';
        } else {
          star.classList.remove('active');
          star.textContent = '★';
        }
      });
      label.textContent = `${val} / 5 stars`;
    }

    const initialVal = parseInt(hiddenInput.value, 10) || 5;
    updateStars(initialVal);

    stars.forEach(star => {
      const starVal = parseInt(star.getAttribute('data-value'), 10);

      star.addEventListener('click', (e) => {
        e.preventDefault();
        hiddenInput.value = starVal;
        updateStars(starVal);
      });

      star.addEventListener('mouseenter', () => {
        updateStars(starVal);
      });
    });

    widget.addEventListener('mouseleave', () => {
      const currentVal = parseInt(hiddenInput.value, 10) || 5;
      updateStars(currentVal);
    });
  });
}

/* ==========================================================================
   6. RICH TEXT TOOLBAR (WrapSelection & Preview)
   ========================================================================== */
function wrapSelection(textarea, before, after) {
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const selected = textarea.value.substring(start, end);
  textarea.value = textarea.value.substring(0, start) 
    + before + selected + after 
    + textarea.value.substring(end);
  textarea.focus();
  textarea.setSelectionRange(start + before.length, end + before.length);
  textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function initRichTextToolbars() {
  const toolbars = document.querySelectorAll('.rich-text-toolbar');

  toolbars.forEach(toolbar => {
    const targetSelector = toolbar.getAttribute('data-target') || 'textarea';
    const form = toolbar.closest('form');
    const textarea = form ? form.querySelector(targetSelector) : toolbar.parentElement.querySelector('textarea');

    if (!textarea) return;

    toolbar.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-format]');
      if (!btn) return;
      e.preventDefault();

      const format = btn.getAttribute('data-format');
      switch (format) {
        case 'bold':
          wrapSelection(textarea, '<b>', '</b>');
          break;
        case 'italic':
          wrapSelection(textarea, '<i>', '</i>');
          break;
        case 'h3':
          wrapSelection(textarea, '<h3>', '</h3>');
          break;
        case 'link':
          const url = prompt('Enter link URL (e.g., https://...):', 'https://');
          if (url) {
            wrapSelection(textarea, `<a href="${url}">`, '</a>');
          }
          break;
        case 'paragraph':
          wrapSelection(textarea, '<p>', '</p>');
          break;
        case 'list':
          wrapSelection(textarea, '<ul>\n  <li>', '</li>\n</ul>');
          break;
        case 'preview':
          showHtmlPreview(textarea.value);
          break;
      }
    });
  });
}

function showHtmlPreview(htmlContent) {
  let previewModal = document.getElementById('richTextPreviewModal');
  if (!previewModal) {
    previewModal = document.createElement('div');
    previewModal.id = 'richTextPreviewModal';
    previewModal.className = 'admin-modal-overlay';
    previewModal.innerHTML = `
      <div class="admin-modal-dialog" style="max-width: 720px;">
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">HTML Content Live Preview</h3>
          <button type="button" class="admin-modal-close" onclick="closeModal('richTextPreviewModal')">✕</button>
        </div>
        <div class="admin-modal-body" id="richTextPreviewBody" style="max-height: 65vh; overflow-y: auto; line-height: 1.7; font-size: 1.05rem;">
        </div>
        <div class="admin-modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('richTextPreviewModal')">Close Preview</button>
        </div>
      </div>
    `;
    document.body.appendChild(previewModal);
  }

  const body = document.getElementById('richTextPreviewBody');
  if (body) {
    body.innerHTML = htmlContent || '<p class="text-muted" style="font-style: italic;">(No content written yet)</p>';
  }
  openModal('richTextPreviewModal');
}

/* ==========================================================================
   7. IMAGE DRAG & DROP
   ========================================================================== */
function initImageDragAndDrop() {
  const dropzones = document.querySelectorAll('.image-dropzone, .image-upload-wrapper, .upload-dropzone, .image-upload-zone');

  dropzones.forEach(zone => {
    const fileInput = zone.querySelector('input[type="file"]');
    if (!fileInput) return;

    ['dragenter', 'dragover'].forEach(eventName => {
      zone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.add('drag-over');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      zone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.remove('drag-over');
      }, false);
    });

    zone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files && files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }, false);
  });
}

/* ==========================================================================
   8. MODAL SYSTEM
   ========================================================================== */
let activeModal = null;
let lastFocusedElement = null;

window.openModal = function(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  lastFocusedElement = document.activeElement;
  modal.classList.add('active');
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('modal-open');
  activeModal = modal;

  if (window.lucide && typeof lucide.createIcons === 'function') {
    lucide.createIcons();
  }

  // Trap focus
  const focusables = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  if (focusables.length > 0) {
    focusables[0].focus();
  }
};

window.closeModal = function(modalId) {
  const modal = typeof modalId === 'string' ? document.getElementById(modalId) : (modalId || activeModal);
  if (!modal) return;

  modal.classList.remove('active');
  modal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');
  activeModal = null;

  if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
    lastFocusedElement.focus();
  }
};

function initModalSystem() {
  document.addEventListener('click', (e) => {
    // Check if clicked directly on modal overlay backdrop
    if (e.target.classList.contains('admin-modal-overlay') || e.target.classList.contains('modal-backdrop')) {
      closeModal(e.target);
    }
    // Check close button
    if (e.target.closest('.admin-modal-close') || e.target.closest('[data-modal-close]')) {
      const modal = e.target.closest('.admin-modal-overlay') || e.target.closest('.modal-backdrop');
      if (modal) closeModal(modal);
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && activeModal) {
      closeModal(activeModal);
    }

    // Accessible Focus Trap inside modal
    if (e.key === 'Tab' && activeModal) {
      const focusables = Array.from(activeModal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'));
      if (focusables.length === 0) return;

      const firstElem = focusables[0];
      const lastElem = focusables[focusables.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === firstElem) {
          e.preventDefault();
          lastElem.focus();
        }
      } else {
        if (document.activeElement === lastElem) {
          e.preventDefault();
          firstElem.focus();
        }
      }
    }
  });
}

/* ==========================================================================
   9. CONFIRM DELETE (Styled Modal)
   ========================================================================== */
function initConfirmDelete() {
  // Ensure the confirm delete modal exists in DOM
  let deleteModal = document.getElementById('confirmDeleteModal');
  if (!deleteModal) {
    deleteModal = document.createElement('div');
    deleteModal.id = 'confirmDeleteModal';
    deleteModal.className = 'admin-modal-overlay';
    deleteModal.innerHTML = `
      <div class="admin-modal-dialog" style="max-width: 460px; text-align: center;">
        <div style="font-size: 2.8rem; line-height: 1; margin-bottom: 14px;">⚠️</div>
        <h3 class="admin-modal-title" style="margin-bottom: 10px; font-size: 1.35rem; color: #ffffff;">Confirm Deletion</h3>
        <p id="confirmDeleteMessage" style="color: #9990b8; font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px;">
          Are you sure you want to permanently delete this item? This action cannot be undone.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteProceedBtn" style="background: #ef4444; color: #ffffff; border: none; font-weight: 600; padding: 10px 24px;">Delete Permanently</button>
        </div>
      </div>
    `;
    document.body.appendChild(deleteModal);
  }

  let pendingDeleteForm = null;

  document.addEventListener('submit', (e) => {
    const form = e.target.closest('.delete-form, form[data-confirm-delete]');
    if (!form || form.getAttribute('data-confirmed') === 'true') return;

    e.preventDefault();
    pendingDeleteForm = form;

    const itemName = form.getAttribute('data-item-name') || form.querySelector('[data-item-name]')?.textContent || '';
    const msg = document.getElementById('confirmDeleteMessage');
    if (msg) {
      if (itemName) {
        msg.innerHTML = `Are you sure you want to delete <strong style="color:#ffffff;">"${escapeHtml(itemName)}"</strong>? This action cannot be undone.`;
      } else {
        msg.textContent = 'Are you sure you want to permanently delete this item? This action cannot be undone.';
      }
    }

    openModal('confirmDeleteModal');
  });

  const proceedBtn = document.getElementById('confirmDeleteProceedBtn');
  if (proceedBtn) {
    proceedBtn.addEventListener('click', () => {
      if (pendingDeleteForm) {
        pendingDeleteForm.setAttribute('data-confirmed', 'true');
        closeModal('confirmDeleteModal');
        pendingDeleteForm.submit();
      }
    });
  }
}

/* ==========================================================================
   10. TABLE SEARCH (Client-Side Real-Time Filter)
   ========================================================================== */
function initClientTableSearch() {
  const searchInputs = document.querySelectorAll('.table-search-input, [data-table-search]');

  searchInputs.forEach(input => {
    const targetTableSelector = input.getAttribute('data-table-target') || 'table';
    const container = input.closest('.admin-card, .table-container, .admin-content') || document;
    const table = container.querySelector(targetTableSelector);

    if (!table) return;

    const tbody = table.querySelector('tbody') || table;
    const rows = Array.from(tbody.querySelectorAll('tr:not(.no-results-row)'));

    // Create No Results row if not present
    let noResultsRow = tbody.querySelector('.no-results-row');
    if (!noResultsRow) {
      const colCount = rows.length > 0 ? rows[0].children.length : 6;
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.style.display = 'none';
      noResultsRow.innerHTML = `<td colspan="${colCount}" style="text-align:center; padding: 36px 16px; color: #9990b8; font-style: italic;">🔍 No matching records found</td>`;
      tbody.appendChild(noResultsRow);
    }

    input.addEventListener('input', () => {
      const query = input.value.toLowerCase().trim();
      let matchCount = 0;

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (!query || text.includes(query)) {
          row.style.display = '';
          matchCount++;
        } else {
          row.style.display = 'none';
        }
      });

      noResultsRow.style.display = matchCount === 0 ? '' : 'none';
    });
  });
}

/* ==========================================================================
   11. AUTO HIDE FLASH ALERTS
   ========================================================================== */
function initAlertAutoHide() {
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 400);
    }, 4500);
  });
}

/* ==========================================================================
   HELPER UTILITIES
   ========================================================================== */
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
