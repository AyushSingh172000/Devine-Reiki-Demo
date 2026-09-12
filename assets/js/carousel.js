/**
 * Infinite Horizontal Auto-Scrolling Carousel JavaScript
 * Automatically duplicates track items for seamless CSS infinite translateX scrolling
 */

document.addEventListener('DOMContentLoaded', () => {
  const carouselContainers = document.querySelectorAll('.infinite-carousel, .carousel-wrapper');

  carouselContainers.forEach((container) => {
    const track = container.querySelector('.infinite-carousel-track, .carousel-track');
    if (!track) return;

    // Avoid duplicate cloning if already cloned
    if (track.getAttribute('data-cloned') === 'true') return;

    const items = Array.from(track.children);
    if (items.length === 0) return;

    // Clone all children to build a seamless double track
    items.forEach((item) => {
      const clone = item.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      track.appendChild(clone);
    });

    track.setAttribute('data-cloned', 'true');
  });

  // -------------------------------------------------------------------------
  // Manual & Auto-Scroll Support for Team Carousel (.team-carousel-wrapper)
  // Shows ONLY the exact database team members without duplicates/cloning
  // -------------------------------------------------------------------------
  const teamCarousel = document.querySelector('.team-carousel-wrapper');
  if (teamCarousel) {
    const teamTrack = teamCarousel.querySelector('.team-carousel-track');
    if (teamTrack) {
      let isInteracting = false;
      let resumeTimer = null;
      let isDown = false;
      let startX = 0;
      let scrollStart = 0;
      let hasDragged = false;
      let autoAdvanceTimer = null;
      const cardStep = 334; // Card width (310px) + gap (24px)

      function getMaxScroll() {
        return Math.max(0, teamCarousel.scrollWidth - teamCarousel.clientWidth);
      }

      function updateNavButtons() {
        const prevBtn = document.getElementById('teamCarouselPrev');
        const nextBtn = document.getElementById('teamCarouselNext');
        const maxScroll = getMaxScroll();
        if (prevBtn && nextBtn) {
          if (maxScroll <= 10) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
          } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
          }
        }
      }

      function resetResumeTimer(delay = 3500) {
        clearTimeout(resumeTimer);
        resumeTimer = setTimeout(() => {
          if (!isDown) {
            isInteracting = false;
          }
        }, delay);
      }

      // Auto-advance loop: smoothly step forward every 3.5s when not hovered or interacting
      function startAutoAdvance() {
        clearInterval(autoAdvanceTimer);
        autoAdvanceTimer = setInterval(() => {
          if (!isInteracting && !isDown) {
            const maxScroll = getMaxScroll();
            if (maxScroll <= 10) return;
            if (teamCarousel.scrollLeft >= maxScroll - 15) {
              teamCarousel.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
              teamCarousel.scrollBy({ left: cardStep, behavior: 'smooth' });
            }
          }
        }, 3500);
      }

      // Pointer Events for reliable click-and-drag across mouse, pen, and touch
      teamCarousel.addEventListener('pointerdown', (e) => {
        if (e.button !== 0) return; // Only left click
        isDown = true;
        hasDragged = false;
        isInteracting = true;
        clearTimeout(resumeTimer);
        startX = e.clientX;
        scrollStart = teamCarousel.scrollLeft;
        teamCarousel.classList.add('is-dragging');
        try {
          teamCarousel.setPointerCapture(e.pointerId);
        } catch (err) {}
      });

      teamCarousel.addEventListener('pointermove', (e) => {
        if (!isDown) return;
        const deltaX = e.clientX - startX;
        if (Math.abs(deltaX) > 4) {
          hasDragged = true;
        }
        teamCarousel.scrollLeft = scrollStart - deltaX;
      });

      function endPointerDrag(e) {
        if (!isDown) return;
        isDown = false;
        teamCarousel.classList.remove('is-dragging');
        try {
          teamCarousel.releasePointerCapture(e.pointerId);
        } catch (err) {}
        resetResumeTimer(2500);
      }

      teamCarousel.addEventListener('pointerup', endPointerDrag);
      teamCarousel.addEventListener('pointercancel', endPointerDrag);

      // Prevent link/card clicks if user was dragging
      teamCarousel.addEventListener('click', (e) => {
        if (hasDragged) {
          e.preventDefault();
          e.stopPropagation();
          hasDragged = false;
        }
      }, true);

      // Mouse wheel support
      teamCarousel.addEventListener('wheel', (e) => {
        isInteracting = true;
        clearTimeout(resumeTimer);
        const delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
        if (delta !== 0) {
          teamCarousel.scrollLeft += delta * 1.1;
        }
        resetResumeTimer(3000);
      }, { passive: true });

      // Touch events
      teamCarousel.addEventListener('touchstart', () => {
        isInteracting = true;
        clearTimeout(resumeTimer);
      }, { passive: true });

      teamCarousel.addEventListener('touchend', () => {
        resetResumeTimer(2500);
      }, { passive: true });

      // Pause when cursor enters/hovers over cards
      teamCarousel.addEventListener('mouseenter', () => {
        isInteracting = true;
        clearTimeout(resumeTimer);
      });

      teamCarousel.addEventListener('mouseleave', () => {
        if (!isDown) {
          resetResumeTimer(1500);
        }
      });

      // Left & Right Arrow Buttons
      const prevBtn = document.getElementById('teamCarouselPrev');
      const nextBtn = document.getElementById('teamCarouselNext');

      if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
          e.preventDefault();
          isInteracting = true;
          clearTimeout(resumeTimer);
          const maxScroll = getMaxScroll();
          if (teamCarousel.scrollLeft <= 15 && maxScroll > 0) {
            teamCarousel.scrollTo({ left: maxScroll, behavior: 'smooth' });
          } else {
            teamCarousel.scrollBy({ left: -cardStep, behavior: 'smooth' });
          }
          resetResumeTimer(4000);
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
          e.preventDefault();
          isInteracting = true;
          clearTimeout(resumeTimer);
          const maxScroll = getMaxScroll();
          if (teamCarousel.scrollLeft >= maxScroll - 15 && maxScroll > 0) {
            teamCarousel.scrollTo({ left: 0, behavior: 'smooth' });
          } else {
            teamCarousel.scrollBy({ left: cardStep, behavior: 'smooth' });
          }
          resetResumeTimer(4000);
        });
      }

      window.addEventListener('resize', updateNavButtons);
      setTimeout(updateNavButtons, 100);
      startAutoAdvance();
    }
  }
});
