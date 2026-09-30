/**
 * Contact Form AJAX Submission & Validation JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  const contactForm = document.getElementById('contact-form-element');
  const alertBox = document.getElementById('form-response-alert');
  const submitBtn = document.getElementById('contact-submit-btn');

  if (!contactForm) return;

  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Reset Alert Box
    if (dismissTimer) {
      clearTimeout(dismissTimer);
      dismissTimer = null;
    }
    if (alertBox) {
      alertBox.style.display = 'none';
      alertBox.style.opacity = '1';
      alertBox.className = 'form-alert-box';
      alertBox.textContent = '';
    }

    // Client-side Validation
    const nameInput = contactForm.querySelector('[name="name"]');
    const phoneInput = contactForm.querySelector('[name="phone"]');
    const messageInput = contactForm.querySelector('[name="message"]');

    if (!nameInput.value.trim()) {
      showAlert('Please enter your full name.', 'error');
      nameInput.focus();
      return;
    }

    if (!phoneInput.value.trim()) {
      showAlert('Please enter your phone number.', 'error');
      phoneInput.focus();
      return;
    }

    if (!messageInput.value.trim()) {
      showAlert('Please enter your message or inquiry.', 'error');
      messageInput.focus();
      return;
    }

    // Disable Submit Button & Show Loading State
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.origText = submitBtn.textContent;
      submitBtn.textContent = 'Sending Message...';
    }

    try {
      const formData = new FormData(contactForm);
      const response = await fetch(contactForm.action, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        showAlert(result.message, 'success', true);
        contactForm.reset();

        if (result.whatsapp_url) {
          // Open WhatsApp in new tab / app
          setTimeout(() => {
            const waWindow = window.open(result.whatsapp_url, '_blank');
            if (!waWindow || waWindow.closed || typeof waWindow.closed === 'undefined') {
              // If popup was blocked by browser, redirect current window
              window.location.href = result.whatsapp_url;
            }
          }, 800);
        }
      } else {
        showAlert(result.message || 'Failed to submit form.', 'error', false);
      }
    } catch (error) {
      console.error('Contact submission error:', error);
      showAlert('An unexpected network error occurred. Please try again or WhatsApp us directly.', 'error', false);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.origText || 'Send Message →';
      }
    }
  });

  let dismissTimer = null;

  function showAlert(msg, type, autoDismiss = false) {
    if (!alertBox) return;
    if (dismissTimer) {
      clearTimeout(dismissTimer);
      dismissTimer = null;
    }
    alertBox.textContent = msg;
    alertBox.className = 'form-alert-box ' + (type === 'success' ? 'alert-success' : 'alert-danger');
    alertBox.style.display = 'block';
    alertBox.style.opacity = '1';
    alertBox.style.transition = 'opacity 0.4s ease';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (autoDismiss) {
      dismissTimer = setTimeout(() => {
        alertBox.style.opacity = '0';
        setTimeout(() => {
          alertBox.style.display = 'none';
          alertBox.className = 'form-alert-box';
          alertBox.textContent = '';
        }, 400);
      }, 5000);
    }
  }
});
