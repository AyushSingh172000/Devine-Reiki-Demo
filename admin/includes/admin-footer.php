<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
?>
    </main><!-- .admin-content -->
  </div><!-- .admin-main -->
</div><!-- .admin-layout -->

<!-- Global Styled Confirm Delete Modal -->
<div class="admin-modal-overlay" id="confirmDeleteModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="admin-modal-dialog" style="max-width: 450px; text-align: center;">
    <div style="margin-bottom: 12px; color: #f87171;">
      <i data-lucide="alert-triangle" style="width: 48px; height: 48px; stroke-width: 1.5;"></i>
    </div>
    <h3 class="admin-modal-title" style="margin-bottom: 10px; font-size: 1.3rem; color: #ffffff;">Confirm Deletion</h3>
    <p id="confirmDeleteMessage" style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 22px;">
      Are you sure you want to permanently delete this item? This action cannot be undone.
    </p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <button type="button" class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">Cancel</button>
      <button type="button" class="btn btn-danger" id="confirmDeleteProceedBtn" style="background: #ef4444; color: #ffffff; border: none; font-weight: 600; padding: 10px 22px;">
        <i data-lucide="trash-2"></i> Delete Permanently
      </button>
    </div>
  </div>
</div>

<!-- Global HTML Content Live Preview Modal -->
<div class="admin-modal-overlay" id="richTextPreviewModal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="admin-modal-dialog" style="max-width: 740px;">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title">HTML Content Live Preview</h3>
      <button type="button" class="admin-modal-close" onclick="closeModal('richTextPreviewModal')"><i data-lucide="x"></i></button>
    </div>
    <div class="admin-modal-body" id="richTextPreviewBody" style="max-height: 65vh; overflow-y: auto; line-height: 1.7; font-size: 1.02rem; padding: 10px 4px;">
    </div>
    <div class="admin-modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('richTextPreviewModal')">Close Preview</button>
    </div>
  </div>
</div>

<!-- Core Admin JavaScript -->
<script src="assets/js/admin.js"></script>

<script>
// Image preview on file input change
document.querySelectorAll('.image-upload-input').forEach(input => {
  input.addEventListener('change', function() {
    const preview = this.closest('.form-group') ? this.closest('.form-group').querySelector('.upload-preview') : null;
    if (preview && this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview"><button type="button" class="remove-preview" onclick="removePreview(this)" title="Remove image">✕</button>';
        preview.style.display = 'block';
      };
      reader.readAsDataURL(this.files[0]);
    }
  });
});

function removePreview(btn) {
  const formGroup = btn.closest('.form-group');
  if (formGroup) {
    const input = formGroup.querySelector('.image-upload-input');
    if (input) input.value = '';
    const preview = formGroup.querySelector('.upload-preview');
    if (preview) preview.style.display = 'none';
  }
}
</script>

<!-- Particle Animation Script -->
<script>
(function() {
    const canvas = document.getElementById('adminParticles');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let width, height, particles, animationId;
    
    // Particle configuration — matches main website style
    const CONFIG = {
        particleCount: 60,         // Number of floating particles
        colors: [
            'rgba(201, 168, 76, ',  // Gold
            'rgba(124, 107, 196, ', // Purple
            'rgba(255, 255, 255, ', // White
            'rgba(167, 139, 250, ', // Light purple
        ],
        minSize: 1.5,
        maxSize: 4,
        minSpeed: 0.15,
        maxSpeed: 0.5,
        connectionDistance: 120,    // Distance to draw lines between particles
        connectionOpacity: 0.08,   // Very subtle connecting lines
        glowParticles: true,       // Add glow effect to some particles
        mouseInteraction: false,   // Keep false — admin panel shouldn't be distracting
    };
    
    class Particle {
        constructor() {
            this.reset();
        }
        
        reset() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.size = CONFIG.minSize + Math.random() * (CONFIG.maxSize - CONFIG.minSize);
            this.speedX = (Math.random() - 0.5) * CONFIG.maxSpeed;
            this.speedY = (Math.random() - 0.5) * CONFIG.maxSpeed;
            this.color = CONFIG.colors[Math.floor(Math.random() * CONFIG.colors.length)];
            this.opacity = 0.3 + Math.random() * 0.5;
            this.targetOpacity = this.opacity;
            this.glow = CONFIG.glowParticles && Math.random() > 0.7;
            this.pulseSpeed = 0.005 + Math.random() * 0.01;
            this.pulsePhase = Math.random() * Math.PI * 2;
        }
        
        update() {
            // Move
            this.x += this.speedX;
            this.y += this.speedY;
            
            // Pulse opacity
            this.pulsePhase += this.pulseSpeed;
            this.opacity = this.targetOpacity + Math.sin(this.pulsePhase) * 0.15;
            
            // Wrap around edges with buffer
            if (this.x < -20) this.x = width + 20;
            if (this.x > width + 20) this.x = -20;
            if (this.y < -20) this.y = height + 20;
            if (this.y > height + 20) this.y = -20;
        }
        
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = this.color + this.opacity + ')';
            ctx.fill();
            
            // Add glow effect to selected particles
            if (this.glow) {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size * 3, 0, Math.PI * 2);
                const gradient = ctx.createRadialGradient(
                    this.x, this.y, this.size * 0.5,
                    this.x, this.y, this.size * 3
                );
                gradient.addColorStop(0, this.color + (this.opacity * 0.3) + ')');
                gradient.addColorStop(1, this.color + '0)');
                ctx.fillStyle = gradient;
                ctx.fill();
            }
        }
    }
    
    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < CONFIG.connectionDistance) {
                    const opacity = (1 - distance / CONFIG.connectionDistance) * CONFIG.connectionOpacity;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = 'rgba(124, 107, 196, ' + opacity + ')';
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }
            }
        }
    }
    
    function animate() {
        ctx.clearRect(0, 0, width, height);
        
        drawConnections();
        
        particles.forEach(p => {
            p.update();
            p.draw();
        });
        
        animationId = requestAnimationFrame(animate);
    }
    
    function init() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
        
        particles = [];
        for (let i = 0; i < CONFIG.particleCount; i++) {
            particles.push(new Particle());
        }
    }
    
    // Handle resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }, 200);
    });
    
    // Reduce animation when tab is not visible (performance)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cancelAnimationFrame(animationId);
        } else {
            animate();
        }
    });
    
    // Start
    init();
    animate();
})();
</script>

<!-- Initialize Lucide Icons -->
<script>
if (typeof lucide !== 'undefined' && lucide.createIcons) {
    lucide.createIcons();
}
</script>
</body>
</html>
