/**
 * Hero Background Healing Energy Canvas Animation
 * Renders glowing Reiki particles, concentric aura rings, and radiant energy pulses
 * 100% in the background layer (z-index: 1) behind all hero section text (z-index: 2).
 */
document.addEventListener('DOMContentLoaded', () => {
  const canvases = document.querySelectorAll('.hero-bg-canvas, #hero-bg-canvas');
  if (!canvases || canvases.length === 0) return;

  canvases.forEach(canvas => {
    initSingleHeroCanvas(canvas);
  });
});

function initSingleHeroCanvas(canvas) {
  const ctx = canvas.getContext('2d');
  let animationFrameId = null;
  let width = 0;
  let height = 0;

  // Particle configuration
  const particles = [];
  const particleCount = 45;

  // Concentric aura wave configuration
  const auraWaves = [];
  const waveCount = 3;

  function resize() {
    const parent = canvas.parentElement;
    if (!parent) return;
    width = parent.clientWidth;
    height = parent.clientHeight;

    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = width * dpr;
    canvas.height = height * dpr;
    ctx.scale(dpr, dpr);
  }

  class Particle {
    constructor() {
      this.reset(true);
    }

    reset(initial = false) {
      this.x = Math.random() * width;
      this.y = initial ? Math.random() * height : height + 10;
      this.radius = Math.random() * 2.5 + 1;
      this.speedY = -(Math.random() * 0.45 + 0.15);
      this.speedX = (Math.random() - 0.5) * 0.35;
      this.alpha = Math.random() * 0.65 + 0.25;
      this.fade = Math.random() * 0.005 + 0.002;
      // Alternate between gold and amethyst/purple colors
      this.isGold = Math.random() > 0.45;
      this.color = this.isGold ? '212, 175, 55' : '175, 135, 235';
    }

    update() {
      this.y += this.speedY;
      this.x += Math.sin(this.y * 0.015) * 0.3;

      if (this.y < -10 || this.x < -10 || this.x > width + 10) {
        this.reset(false);
      }
    }

    draw() {
      ctx.save();
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${this.color}, ${this.alpha})`;
      ctx.shadowColor = `rgba(${this.color}, 0.8)`;
      ctx.shadowBlur = 8;
      ctx.fill();
      ctx.restore();
    }
  }

  class AuraWave {
    constructor(delayIndex) {
      this.delay = delayIndex * 140;
      this.timer = this.delay;
      this.reset();
    }

    reset() {
      this.radius = 20;
      this.maxRadius = Math.max(width, height) * 0.6;
      this.alpha = 0.5;
    }

    update() {
      if (this.timer > 0) {
        this.timer--;
        return;
      }
      this.radius += 0.45;
      const progress = this.radius / this.maxRadius;
      this.alpha = Math.sin(progress * Math.PI) * 0.35;

      if (this.radius >= this.maxRadius) {
        this.reset();
      }
    }

    draw() {
      if (this.timer > 0 || this.alpha <= 0) return;

      const centerX = width / 2;
      const centerY = height * 0.42; // Positioned behind hero title text

      ctx.save();
      ctx.beginPath();
      ctx.arc(centerX, centerY, this.radius, 0, Math.PI * 2);
      ctx.lineWidth = 1.5;
      ctx.strokeStyle = `rgba(212, 175, 55, ${this.alpha * 0.6})`;
      ctx.shadowColor = 'rgba(212, 175, 55, 0.4)';
      ctx.shadowBlur = 12;
      ctx.stroke();
      ctx.restore();
    }
  }

  function init() {
    resize();
    particles.length = 0;
    for (let i = 0; i < particleCount; i++) {
      particles.push(new Particle());
    }

    auraWaves.length = 0;
    for (let i = 0; i < waveCount; i++) {
      auraWaves.push(new AuraWave(i));
    }
  }

  function animate() {
    // 1. Clear previous frame cleanly (transparent background)
    ctx.clearRect(0, 0, width, height);

    // 2. Draw smooth radial aura glow ONLY as a circle behind hero title text (No rectangular box!)
    const centerX = width / 2;
    const centerY = height * 0.42;
    const glowRadius = Math.min(width * 0.45, 450);

    ctx.save();
    ctx.beginPath();
    ctx.arc(centerX, centerY, glowRadius, 0, Math.PI * 2);
    
    const time = Date.now() * 0.0015;
    const pulseIntensity = 0.28 + Math.sin(time) * 0.08;

    const pulseGlow = ctx.createRadialGradient(centerX, centerY, 5, centerX, centerY, glowRadius);
    pulseGlow.addColorStop(0, `rgba(212, 175, 55, ${pulseIntensity})`);
    pulseGlow.addColorStop(0.45, `rgba(147, 112, 219, ${pulseIntensity * 0.65})`);
    pulseGlow.addColorStop(1, 'rgba(17, 7, 36, 0)');

    ctx.fillStyle = pulseGlow;
    ctx.fill();
    ctx.restore();

    // 3. Draw expanding concentric aura rings
    auraWaves.forEach(wave => {
      wave.update();
      wave.draw();
    });

    // 4. Draw floating reiki energy particles
    particles.forEach(p => {
      p.update();
      p.draw();
    });

    animationFrameId = requestAnimationFrame(animate);
  }

  window.addEventListener('resize', () => {
    resize();
  });

  // Pause animation when offscreen or tab hidden
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        if (!animationFrameId) animate();
      } else {
        if (animationFrameId) {
          cancelAnimationFrame(animationFrameId);
          animationFrameId = null;
        }
      }
    });
  }, { threshold: 0.1 });

  observer.observe(canvas.parentElement || canvas);

  init();
  animate();
}
