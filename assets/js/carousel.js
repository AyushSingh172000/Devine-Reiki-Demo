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
});
