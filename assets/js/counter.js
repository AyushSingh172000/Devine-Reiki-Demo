/**
 * Animated Number Counter JavaScript
 * Uses IntersectionObserver to trigger smooth counting animations when stat counters enter viewport
 */

document.addEventListener('DOMContentLoaded', () => {
  const counterElements = document.querySelectorAll('.stat-number, .counter-value');

  if (counterElements.length === 0) return;

  const easeOutQuad = (t) => t * (2 - t);

  const formatNumber = (num, hasK = false, suffix = '+') => {
    if (hasK || num >= 1000) {
      const thousands = Math.floor(num / 1000);
      return thousands + 'K' + suffix;
    }
    return Math.floor(num) + suffix;
  };

  const animateCounter = (element) => {
    const rawTarget = element.getAttribute('data-target') || element.innerText.replace(/[^0-9]/g, '');
    const target = parseInt(rawTarget, 10);

    if (isNaN(target)) return;

    // Detect if target is thousands or contains 'K'
    const originalText = element.getAttribute('data-suffix') || element.innerText;
    const isThousands = target >= 1000 || originalText.includes('K') || originalText.includes('k');
    const customSuffix = element.getAttribute('data-plus') !== 'false' ? '+' : '';

    const duration = 2000; // 2 seconds
    const startTime = performance.now();

    const updateCounter = (currentTime) => {
      const elapsedTime = currentTime - startTime;
      const progress = Math.min(elapsedTime / duration, 1);
      const easedProgress = easeOutQuad(progress);
      const currentVal = easedProgress * target;

      if (isThousands) {
        element.textContent = formatNumber(currentVal, true, customSuffix);
      } else {
        element.textContent = Math.floor(currentVal) + customSuffix;
      }

      if (progress < 1) {
        requestAnimationFrame(updateCounter);
      } else {
        if (isThousands) {
          element.textContent = formatNumber(target, true, customSuffix);
        } else {
          element.textContent = target + customSuffix;
        }
      }
    };

    requestAnimationFrame(updateCounter);
  };

  if ('IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });

    counterElements.forEach((el) => counterObserver.observe(el));
  } else {
    // Fallback if IntersectionObserver not available
    counterElements.forEach((el) => animateCounter(el));
  }
});
