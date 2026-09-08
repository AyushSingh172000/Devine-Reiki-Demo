/**
 * Main JavaScript File for Reiki Website
 * Handles Navbar Scroll, Mobile Menu Toggle, Smooth Scroll, Scroll Animations, and Link Highlighting
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Navbar Scroll Effect
  const navbar = document.getElementById('navbar');
  const handleScroll = () => {
    if (navbar) {
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
  };
  window.addEventListener('scroll', handleScroll);
  handleScroll(); // Trigger once on load

  // 2. Mobile Menu Toggle
  const hamburgerToggle = document.getElementById('hamburger-toggle');
  const mobileOverlay = document.getElementById('mobile-nav-overlay');

  const toggleMobileMenu = () => {
    if (hamburgerToggle && mobileOverlay) {
      const isOpen = hamburgerToggle.classList.toggle('open');
      mobileOverlay.classList.toggle('open', isOpen);
      document.body.classList.toggle('no-scroll', isOpen);
    }
  };

  const closeMobileMenu = () => {
    if (hamburgerToggle && mobileOverlay) {
      hamburgerToggle.classList.remove('open');
      mobileOverlay.classList.remove('open');
      document.body.classList.remove('no-scroll');
    }
  };

  if (hamburgerToggle) {
    hamburgerToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleMobileMenu();
    });
  }

  // 6. Close Mobile Menu when links are clicked
  const mobileLinks = document.querySelectorAll('#mobile-nav-overlay a');
  mobileLinks.forEach((link) => {
    link.addEventListener('click', closeMobileMenu);
  });

  // 3. Smooth Scroll for Anchor Links
  const anchorLinks = document.querySelectorAll('a[href^="#"]:not([href="#"])');
  anchorLinks.forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId && targetId !== '#') {
        let targetElement = document.querySelector(targetId);
        if (!targetElement && targetId === '#contact-form') {
          targetElement = document.getElementById('contact-form-section');
        }
        if (targetElement) {
          e.preventDefault();
          closeMobileMenu();
          const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 90;
          window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
          });
        }
      }
    });
  });

  // 4. Animate On Scroll (IntersectionObserver)
  const animatedElements = document.querySelectorAll('.animate-on-scroll');
  if ('IntersectionObserver' in window) {
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1
    };

    const scrollObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    animatedElements.forEach((el) => scrollObserver.observe(el));
  } else {
    // Fallback if IntersectionObserver is not supported
    animatedElements.forEach((el) => el.classList.add('visible'));
  }

  // 5. Active Nav Link Highlighting based on current URL
  const currentPath = window.location.pathname.split('/').pop() || 'index.php';
  const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
  navLinks.forEach((link) => {
    const href = link.getAttribute('href');
    if (href) {
      const linkPath = href.split('/').pop().split('?')[0].split('#')[0];
      if (linkPath === currentPath) {
        link.classList.add('active');
      }
    }
  });

  // 6. Global LocalStorage Cart State Persistence & Badge Manager
  window.getDivineCart = function() {
    try {
      const saved = localStorage.getItem('divine_cart');
      return saved ? JSON.parse(saved) : [];
    } catch (e) {
      console.error('Error reading divine_cart from localStorage:', e);
      return [];
    }
  };

  window.saveDivineCart = function(cart) {
    try {
      localStorage.setItem('divine_cart', JSON.stringify(cart));
      window.updateDivineCartBadges();
    } catch (e) {
      console.error('Error saving divine_cart to localStorage:', e);
    }
  };

  window.addToDivineCart = function(item) {
    const cart = window.getDivineCart();
    const existingIndex = cart.findIndex(i => i.id === item.id || i.slug === item.slug);
    if (existingIndex > -1) {
      cart[existingIndex].quantity = (cart[existingIndex].quantity || 1) + (item.quantity || 1);
    } else {
      cart.push({
        id: item.id || Date.now(),
        slug: item.slug || '',
        title: item.title || 'Crystal Item',
        price: item.price || 0,
        image: item.image || 'assets/images/products/amethyst-bracelet.jpg',
        quantity: item.quantity || 1
      });
    }
    window.saveDivineCart(cart);
  };

  window.updateDivineCartBadges = function() {
    const cart = window.getDivineCart();
    const totalCount = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
    const badges = document.querySelectorAll('.cart-badge-count, .cart-count, #cart-count, .nav-cart-badge');
    badges.forEach(b => {
      b.textContent = totalCount;
      b.style.display = totalCount > 0 ? 'inline-flex' : 'none';
    });
  };

  // Sync badges on initial page load
  window.updateDivineCartBadges();

  // 7. Interactive WhatsApp Inquiry Modal Logic
  const waFloatBtn = document.getElementById('whatsapp-float-btn');
  const waModalWrapper = document.getElementById('wa-modal-wrapper');
  const waModalClose = document.getElementById('wa-modal-close');
  const waStep1 = document.getElementById('wa-step-1');
  const waStep2 = document.getElementById('wa-step-2');
  const waOptionItems = document.querySelectorAll('.wa-option-item');
  const waPillText = document.getElementById('wa-pill-text');
  const waPillRemove = document.getElementById('wa-pill-remove');
  const waBackBtn = document.getElementById('wa-back-btn');
  const waSubmitBtn = document.getElementById('wa-submit-btn');
  const waCustomMessage = document.getElementById('wa-custom-message');

  let selectedWaTopic = '';

  const toggleWaModal = (show) => {
    if (waModalWrapper) {
      const isOpen = show !== undefined ? show : !waModalWrapper.classList.contains('open');
      waModalWrapper.classList.toggle('open', isOpen);
      if (isOpen) {
        waModalWrapper.setAttribute('aria-hidden', 'false');
      } else {
        waModalWrapper.setAttribute('aria-hidden', 'true');
      }
    }
  };

  if (waFloatBtn) {
    waFloatBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleWaModal();
    });
  }

  if (waModalClose) {
    waModalClose.addEventListener('click', () => {
      toggleWaModal(false);
    });
  }

  // Option selection (Step 1 -> Step 2)
  waOptionItems.forEach(item => {
    item.addEventListener('click', () => {
      const topic = item.getAttribute('data-topic');
      const icon = item.querySelector('.wa-option-icon')?.innerText || '';
      selectedWaTopic = topic;
      
      if (waPillText) {
        waPillText.innerText = `${icon} ${topic}`.trim();
      }

      if (waStep1 && waStep2) {
        waStep1.style.display = 'none';
        waStep2.style.display = 'block';
      }
    });
  });

  const resetToStep1 = () => {
    selectedWaTopic = '';
    if (waCustomMessage) waCustomMessage.value = '';
    if (waStep1 && waStep2) {
      waStep2.style.display = 'none';
      waStep1.style.display = 'block';
    }
  };

  if (waPillRemove) waPillRemove.addEventListener('click', resetToStep1);
  if (waBackBtn) waBackBtn.addEventListener('click', resetToStep1);

  // Submit & Navigate to WhatsApp
  if (waSubmitBtn) {
    waSubmitBtn.addEventListener('click', () => {
      if (!selectedWaTopic) {
        alert('Please select an inquiry topic.');
        resetToStep1();
        return;
      }

      const extraMsg = waCustomMessage ? waCustomMessage.value.trim() : '';
      let fullMessage = `Hello Reiki Bliss! I am reaching out regarding: ${selectedWaTopic}.`;
      if (extraMsg) {
        fullMessage += `\n\nNotes / Details: ${extraMsg}`;
      }

      const waPhone = '919971655705';
      const waUrl = `https://wa.me/${waPhone}?text=${encodeURIComponent(fullMessage)}`;

      window.open(waUrl, '_blank');
      toggleWaModal(false);
      resetToStep1();
    });
  }

  // Close modal when clicking outside
  document.addEventListener('click', (e) => {
    if (waModalWrapper && waModalWrapper.classList.contains('open')) {
      if (!waModalWrapper.contains(e.target) && (!waFloatBtn || !waFloatBtn.contains(e.target))) {
        toggleWaModal(false);
      }
    }
  });

  // 7. Google Calendar Appointment Scheduling Popup Trigger
  document.addEventListener('click', (e) => {
    const bookingBtn = e.target.closest('a[href*="calendar.google.com"], .btn-book-session, .open-gcal-popup');
    if (bookingBtn) {
      const gcalBtn = document.querySelector('.qxCTlb');
      if (gcalBtn) {
        e.preventDefault();
        gcalBtn.click();
      }
    }
  });
});


