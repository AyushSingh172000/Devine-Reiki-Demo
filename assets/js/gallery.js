/**
 * Gallery Lightbox and Category Filtering JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Category Filtering Logic
  const filterBtns = document.querySelectorAll('#gallery-filter-tabs .gallery-filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-item');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter').toLowerCase();

      galleryItems.forEach(item => {
        const category = (item.getAttribute('data-category') || '').toLowerCase();
        if (filter === 'all' || category.includes(filter)) {
          item.classList.remove('hidden');
        } else {
          item.classList.add('hidden');
        }
      });
    });
  });

  // 2. Lightbox Modal Logic
  const lightbox = document.getElementById('gallery-lightbox');
  if (!lightbox) return;

  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxCaption = document.getElementById('lightbox-caption');
  const lightboxCat = document.getElementById('lightbox-cat');
  const closeBtn = document.getElementById('lightbox-close');
  const prevBtn = document.getElementById('lightbox-prev');
  const nextBtn = document.getElementById('lightbox-next');

  let visibleItems = [];
  let currentIndex = 0;

  const updateVisibleItems = () => {
    visibleItems = Array.from(document.querySelectorAll('.gallery-item:not(.hidden)'));
  };

  const openLightbox = (index) => {
    updateVisibleItems();
    if (visibleItems.length === 0) return;

    currentIndex = index;
    if (currentIndex < 0) currentIndex = visibleItems.length - 1;
    if (currentIndex >= visibleItems.length) currentIndex = 0;

    const item = visibleItems[currentIndex];
    const imgUrl = item.getAttribute('data-full-img') || item.querySelector('img').src;
    const caption = item.getAttribute('data-caption') || '';
    const category = item.getAttribute('data-category') || '';

    lightboxImg.src = imgUrl;
    lightboxCaption.textContent = caption;
    lightboxCat.textContent = category;

    lightbox.classList.add('active');
    document.body.classList.add('no-scroll');
  };

  const closeLightbox = () => {
    lightbox.classList.remove('active');
    document.body.classList.remove('no-scroll');
  };

  // Event Listeners for Gallery Item Clicks
  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      updateVisibleItems();
      const index = visibleItems.indexOf(item);
      if (index !== -1) {
        openLightbox(index);
      }
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  if (prevBtn) prevBtn.addEventListener('click', () => openLightbox(currentIndex - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => openLightbox(currentIndex + 1));

  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) {
      closeLightbox();
    }
  });

  // Keyboard Navigation Support
  document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') openLightbox(currentIndex - 1);
    if (e.key === 'ArrowRight') openLightbox(currentIndex + 1);
  });
});
