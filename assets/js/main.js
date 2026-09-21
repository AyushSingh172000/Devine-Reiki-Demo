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

  // 5. Active Nav Link Highlighting with Homepage Scrollspy
  const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
  const servicesSection = document.getElementById('services');

  if (servicesSection) {
    // Homepage Scrollspy: Follows user scroll position smoothly
    const homeLinks = document.querySelectorAll('.nav-link[href*="index.php"], .mobile-nav-link[href*="index.php"]');
    const servicesLinks = document.querySelectorAll('.nav-link[href*="services.php"], .mobile-nav-link[href*="services.php"]');
    const coursesLinks = document.querySelectorAll('.nav-link[href*="courses.php"], .mobile-nav-link[href*="courses.php"]');
    const shopLinks = document.querySelectorAll('.nav-link[href*="products.php"], .mobile-nav-link[href*="products.php"]');

    const coursesSection = document.getElementById('courses');
    const productsSection = document.getElementById('products');

    const updateScrollspy = () => {
      const scrollPos = window.scrollY + 200;
      let activeSection = 'home';

      if (productsSection && scrollPos >= productsSection.offsetTop) {
        activeSection = 'shop';
      } else if (coursesSection && scrollPos >= coursesSection.offsetTop) {
        activeSection = 'courses';
      } else if (servicesSection && scrollPos >= servicesSection.offsetTop) {
        activeSection = 'services';
      } else {
        activeSection = 'home';
      }

      navLinks.forEach(l => l.classList.remove('active'));

      if (activeSection === 'shop') {
        shopLinks.forEach(l => l.classList.add('active'));
      } else if (activeSection === 'courses') {
        coursesLinks.forEach(l => l.classList.add('active'));
      } else if (activeSection === 'services') {
        servicesLinks.forEach(l => l.classList.add('active'));
      } else {
        homeLinks.forEach(l => l.classList.add('active'));
      }
    };

    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          updateScrollspy();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });

    updateScrollspy(); // Run immediately on load
  } else {
    // Static link highlighting for other dedicated pages
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    navLinks.forEach((link) => {
      const href = link.getAttribute('href');
      if (href) {
        const linkPath = href.split('/').pop().split('?')[0].split('#')[0];
        if (linkPath === currentPath) {
          link.classList.add('active');
        }
      }
    });
  }

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

  // 7. Google Calendar Appointment Scheduling Trigger (Mobile & Desktop Optimized)
  document.addEventListener('click', (e) => {
    const bookingBtn = e.target.closest('a[href*="calendar.google.com"], .btn-book-session, .open-gcal-popup');
    if (bookingBtn) {
      // Detect mobile screens or touch phones
      const isMobile = window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
      
      if (isMobile) {
        // On mobile devices, open Google Calendar's official mobile-responsive scheduling app
        // in a new tab without desktop grid view (?gv=true).
        // This delivers the full touch-friendly calendar, stacked time slots, and Google Meet integration.
        e.preventDefault();
        let targetUrl = bookingBtn.getAttribute('href') || 'https://calendar.google.com/calendar/appointments/schedules/AcZssZ1RI6bVu-iU0Oi4_H09OlL-bQglgmpskaOrSO0nCevRuaKlWfCVYv1XsrEzLz-g7HUkgeiO0C2c';
        targetUrl = targetUrl.replace('?gv=true', '').replace('&gv=true', '');
        window.open(targetUrl, '_blank', 'noopener,noreferrer');
      } else {
        // On desktop/laptop, trigger Google's desktop modal popup
        const gcalBtn = document.querySelector('.qxCTlb');
        if (gcalBtn) {
          e.preventDefault();
          gcalBtn.click();
        }
      }
    }
  });

  // 8. 528 Hz Solfeggio Vibration Sound Generator (Sacred Healing Frequency)
  let audioCtx = null;
  let isSoundPlaying = false;
  let activeOscillators = [];

  function play528HzChime() {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      if (!audioCtx) {
        audioCtx = new AudioContext();
      }
      if (audioCtx.state === 'suspended') {
        audioCtx.resume();
      }

      stop528HzSound();

      const now = audioCtx.currentTime;
      const gainNode = audioCtx.createGain();
      gainNode.connect(audioCtx.destination);

      // Bell envelope: smooth fade-in, long resonant healing decay
      gainNode.gain.setValueAtTime(0, now);
      gainNode.gain.linearRampToValueAtTime(0.18, now + 0.12);
      gainNode.gain.exponentialRampToValueAtTime(0.0001, now + 4.5);

      // Fundamental Solfeggio 528 Hz
      const osc1 = audioCtx.createOscillator();
      osc1.type = 'sine';
      osc1.frequency.setValueAtTime(528, now);
      osc1.connect(gainNode);

      // Singing bowl overtone 1056 Hz
      const osc2 = audioCtx.createOscillator();
      osc2.type = 'sine';
      osc2.frequency.setValueAtTime(1056, now);
      const overtoneGain = audioCtx.createGain();
      overtoneGain.gain.setValueAtTime(0.04, now);
      osc2.connect(overtoneGain);
      overtoneGain.connect(gainNode);

      osc1.start(now);
      osc2.start(now);
      osc1.stop(now + 4.5);
      osc2.stop(now + 4.5);

      activeOscillators = [osc1, osc2];
      isSoundPlaying = true;
      updateSoundUI(true);

      setTimeout(() => {
        isSoundPlaying = false;
        updateSoundUI(false);
      }, 4500);

    } catch (err) {
      console.warn('Audio playback not supported or blocked:', err);
    }
  }

  function stop528HzSound() {
    if (activeOscillators.length > 0) {
      activeOscillators.forEach(osc => {
        try { osc.stop(); } catch (e) {}
      });
      activeOscillators = [];
    }
    isSoundPlaying = false;
    updateSoundUI(false);
  }

  function updateSoundUI(playing) {
    const soundBtn = document.getElementById('solfeggioAudioBtn');
    const heroCard = document.getElementById('heroSolfeggioCard');
    if (soundBtn) {
      soundBtn.classList.toggle('active', playing);
    }
    if (heroCard) {
      heroCard.classList.toggle('playing', playing);
    }
  }

  const soundToggleBtn = document.getElementById('solfeggioAudioBtn');
  if (soundToggleBtn) {
    soundToggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (isSoundPlaying) {
        stop528HzSound();
      } else {
        play528HzChime();
      }
    });
  }

  const heroSolfeggio = document.getElementById('heroSolfeggioCard');
  if (heroSolfeggio) {
    heroSolfeggio.addEventListener('click', (e) => {
      e.preventDefault();
      play528HzChime();
    });
  }

  // 12. Testimonials Slider Navigation (Top Right Next & Prev Buttons)
  const testSlider = document.getElementById('testimonialsSlider');
  const testPrevBtn = document.getElementById('testimonialPrevBtn');
  const testNextBtn = document.getElementById('testimonialNextBtn');

  if (testSlider && testPrevBtn && testNextBtn) {
    const getScrollStep = () => {
      const firstCard = testSlider.querySelector('.testimonial-card-box');
      return firstCard ? (firstCard.offsetWidth + 24) : 340;
    };

    testNextBtn.addEventListener('click', () => {
      const step = getScrollStep();
      const maxScrollLeft = testSlider.scrollWidth - testSlider.clientWidth;
      if (testSlider.scrollLeft >= maxScrollLeft - 15) {
        testSlider.scrollTo({ left: 0, behavior: 'smooth' });
      } else {
        testSlider.scrollBy({ left: step, behavior: 'smooth' });
      }
    });

    testPrevBtn.addEventListener('click', () => {
      const step = getScrollStep();
      if (testSlider.scrollLeft <= 15) {
        testSlider.scrollTo({ left: testSlider.scrollWidth, behavior: 'smooth' });
      } else {
        testSlider.scrollBy({ left: -step, behavior: 'smooth' });
      }
    });
  }
});

