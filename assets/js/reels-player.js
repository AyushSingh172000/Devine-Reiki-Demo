/**
 * Reiki Bliss - Instagram Reels Interactive Player & Theater
 * Allows playing real Instagram Reels from @reiki_bliss directly on the website
 */

(function () {
  'use strict';

  // Reels Data Registry
  let reelsData = [];
  let currentReelIndex = 0;

  // DOM Elements
  const modal = document.getElementById('reels-theater-modal');
  const modalIframeContainer = document.getElementById('theater-iframe-container');
  const modalTitle = document.getElementById('theater-reel-title');
  const modalAudio = document.getElementById('theater-reel-audio');
  const modalCounter = document.getElementById('theater-reel-counter');
  const modalCloseBtn = document.getElementById('theater-close-btn');
  const modalPrevBtn = document.getElementById('theater-prev-btn');
  const modalNextBtn = document.getElementById('theater-next-btn');
  const modalSpinner = document.getElementById('theater-loading-spinner');
  const viewSwitcher = document.querySelectorAll('.reels-switcher-tab');
  const reelsGrid = document.querySelector('.reels-cards-grid');

  // Initialize
  function init() {
    collectReelsData();
    bindCardEvents();
    bindModalEvents();
    bindSwitcherEvents();
  }

  // Collect data from HTML cards
  function collectReelsData() {
    const cards = document.querySelectorAll('.reel-card');
    reelsData = Array.from(cards).map((card, index) => {
      return {
        index: index,
        id: card.getAttribute('data-reel-id'),
        title: card.getAttribute('data-title') || '',
        category: card.getAttribute('data-category') || '',
        views: card.getAttribute('data-views') || '',
        likes: card.getAttribute('data-likes') || '',
        audio: card.getAttribute('data-audio') || 'Reiki Bliss · Original Audio',
        embedUrl: `https://www.instagram.com/reel/${card.getAttribute('data-reel-id')}/embed/`,
        cardEl: card
      };
    });
  }

  // Bind click handlers to cards and play triggers
  function bindCardEvents() {
    document.querySelectorAll('.reel-card').forEach((card, index) => {
      // Main Card Click or Play Trigger -> Open Theater Modal
      const playTrigger = card.querySelector('.reel-play-trigger');
      const inlineBtn = card.querySelector('.btn-play-inline');
      const expandBtn = card.querySelector('.btn-expand-theater');

      // Click card opens theater modal by default (unless clicked inline play button)
      card.addEventListener('click', function (e) {
        // If inline button was clicked
        if (e.target.closest('.btn-play-inline')) {
          e.preventDefault();
          e.stopPropagation();
          playInlineInCard(index);
          return;
        }
        
        // If already playing inline, do not intercept clicks intended for inline player
        if (card.classList.contains('is-playing-inline')) {
          return;
        }

        // Prevent normal navigation, open theater player modal
        e.preventDefault();
        openTheaterModal(index);
      });

      if (inlineBtn) {
        inlineBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          playInlineInCard(index);
        });
      }

      if (playTrigger) {
        playTrigger.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          openTheaterModal(index);
        });
      }

      if (expandBtn) {
        expandBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          openTheaterModal(index);
        });
      }
    });
  }

  // Play reel inline right inside its card in the grid
  function playInlineInCard(index) {
    const reel = reelsData[index];
    if (!reel || !reel.cardEl) return;

    const mediaWrapper = reel.cardEl.querySelector('.reel-media-wrapper');
    if (!mediaWrapper) return;

    // Check if already playing inline
    if (reel.cardEl.classList.contains('is-playing-inline')) {
      return;
    }

    // Stop any other currently playing inline reels to save CPU & prevent audio clash
    document.querySelectorAll('.reel-card.is-playing-inline').forEach(otherCard => {
      stopInlineCard(otherCard);
    });

    reel.cardEl.classList.add('is-playing-inline');

    // Create container for iframe
    const inlineContainer = document.createElement('div');
    inlineContainer.className = 'reel-inline-player-box';
    inlineContainer.innerHTML = `
      <div class="inline-player-topbar">
        <span class="inline-badge">▶ Playing on Website</span>
        <div class="inline-actions">
          <button type="button" class="btn-inline-theater" title="Watch in Theater Fullscreen">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
            Theater
          </button>
          <button type="button" class="btn-inline-close" title="Stop & Close Player">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
      </div>
      <iframe 
        class="reel-inline-iframe"
        src="${reel.embedUrl}" 
        frameborder="0" 
        scrolling="no" 
        allowtransparency="true"
        allowfullscreen="true"
        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
      </iframe>
    `;

    mediaWrapper.appendChild(inlineContainer);

    // Bind theater and close buttons inside inline player
    inlineContainer.querySelector('.btn-inline-theater').addEventListener('click', (e) => {
      e.stopPropagation();
      stopInlineCard(reel.cardEl);
      openTheaterModal(index);
    });

    inlineContainer.querySelector('.btn-inline-close').addEventListener('click', (e) => {
      e.stopPropagation();
      stopInlineCard(reel.cardEl);
    });
  }

  function stopInlineCard(cardEl) {
    if (!cardEl) return;
    cardEl.classList.remove('is-playing-inline');
    const existingInline = cardEl.querySelector('.reel-inline-player-box');
    if (existingInline) {
      existingInline.remove();
    }
  }

  // Open the full Theater Lightbox Modal
  function openTheaterModal(index) {
    if (!modal) return;
    currentReelIndex = (index >= 0 && index < reelsData.length) ? index : 0;
    
    // Stop any inline playing reels first
    document.querySelectorAll('.reel-card.is-playing-inline').forEach(card => stopInlineCard(card));

    document.body.classList.add('reels-modal-open');
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');

    loadTheaterReel(currentReelIndex);
  }

  // Load specific reel into the theater modal iframe
  function loadTheaterReel(index) {
    const reel = reelsData[index];
    if (!reel || !modalIframeContainer) return;

    if (modalSpinner) modalSpinner.style.display = 'flex';
    if (modalTitle) modalTitle.textContent = reel.title;
    if (modalAudio) modalAudio.textContent = reel.audio;
    if (modalCounter) modalCounter.textContent = `Reel ${index + 1} of ${reelsData.length}`;

    // Clear previous iframe
    modalIframeContainer.innerHTML = '';

    // Create fresh iframe
    const iframe = document.createElement('iframe');
    iframe.className = 'theater-reel-iframe';
    iframe.src = reel.embedUrl;
    iframe.setAttribute('frameborder', '0');
    iframe.setAttribute('scrolling', 'no');
    iframe.setAttribute('allowtransparency', 'true');
    iframe.setAttribute('allowfullscreen', 'true');
    iframe.setAttribute('allow', 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share');

    iframe.onload = function () {
      if (modalSpinner) modalSpinner.style.display = 'none';
    };

    modalIframeContainer.appendChild(iframe);
  }

  // Close Theater Modal and stop audio
  function closeTheaterModal() {
    if (!modal) return;
    document.body.classList.remove('reels-modal-open');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    if (modalIframeContainer) {
      modalIframeContainer.innerHTML = ''; // completely unload iframe to stop audio
    }
  }

  function nextReel() {
    currentReelIndex = (currentReelIndex + 1) % reelsData.length;
    loadTheaterReel(currentReelIndex);
  }

  function prevReel() {
    currentReelIndex = (currentReelIndex - 1 + reelsData.length) % reelsData.length;
    loadTheaterReel(currentReelIndex);
  }

  // Bind modal UI controls & keyboard events
  function bindModalEvents() {
    if (!modal) return;

    if (modalCloseBtn) {
      modalCloseBtn.addEventListener('click', closeTheaterModal);
    }

    if (modalNextBtn) {
      modalNextBtn.addEventListener('click', nextReel);
    }

    if (modalPrevBtn) {
      modalPrevBtn.addEventListener('click', prevReel);
    }

    // Click on backdrop to close
    modal.addEventListener('click', function (e) {
      if (e.target === modal || e.target.classList.contains('reels-theater-backdrop')) {
        closeTheaterModal();
      }
    });

    // Keyboard navigation: ESC, Left, Right, Up, Down
    window.addEventListener('keydown', function (e) {
      if (!modal.classList.contains('active')) return;

      if (e.key === 'Escape') {
        closeTheaterModal();
      } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
        e.preventDefault();
        nextReel();
      } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
        e.preventDefault();
        prevReel();
      }
    });

    // Touch Swipe support for Mobile
    let touchStartX = 0;
    let touchEndX = 0;

    modal.addEventListener('touchstart', function (e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    modal.addEventListener('touchend', function (e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    }, { passive: true });

    function handleSwipe() {
      const swipeDistance = touchEndX - touchStartX;
      if (Math.abs(swipeDistance) > 60) {
        if (swipeDistance < 0) {
          nextReel(); // swiped left
        } else {
          prevReel(); // swiped right
        }
      }
    }
  }

  // Switch between "Interactive Cards" and "Live Embedded Reels Feed"
  function bindSwitcherEvents() {
    if (!viewSwitcher.length || !reelsGrid) return;

    viewSwitcher.forEach(tab => {
      tab.addEventListener('click', function () {
        const viewMode = this.getAttribute('data-view');
        
        viewSwitcher.forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        if (viewMode === 'live-feed') {
          // Mount all live iframes directly in cards
          reelsData.forEach((reel, i) => {
            playInlineInCard(i);
          });
          reelsGrid.classList.add('is-live-feed-mode');
        } else {
          // Revert to interactive cards
          document.querySelectorAll('.reel-card.is-playing-inline').forEach(card => {
            stopInlineCard(card);
          });
          reelsGrid.classList.remove('is-live-feed-mode');
        }
      });
    });
  }

  // Auto-run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
