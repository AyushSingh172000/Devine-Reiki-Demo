/**
 * Contact Form AJAX Submission & Validation JavaScript
 */

function initContactForm() {
  const contactForm = document.getElementById('contact-form-element');
  const alertBox = document.getElementById('form-response-alert');
  const submitBtn = document.getElementById('contact-submit-btn');

  if (!contactForm) return;

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

    // Client-side Validation with defensive null-checks
    const nameInput = contactForm.querySelector('[name="name"]');
    const phoneInput = contactForm.querySelector('[name="phone"]');
    const messageInput = contactForm.querySelector('[name="message"]');

    const nameVal = nameInput ? nameInput.value.trim() : '';
    const phoneVal = phoneInput ? phoneInput.value.trim() : '';
    const messageVal = messageInput ? messageInput.value.trim() : '';

    if (!nameVal) {
      showAlert('Please enter your full name.', 'error');
      if (nameInput) nameInput.focus();
      return;
    }

    if (!phoneVal) {
      showAlert('Please enter your phone number.', 'error');
      if (phoneInput) phoneInput.focus();
      return;
    }

    // Disable Submit Button & Show Loading State
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.origText = submitBtn.textContent;
      submitBtn.textContent = 'Sending Message...';
    }

    try {
      let actionUrl = contactForm.getAttribute('action') || 'api/contact-submit.php';
      // If current page is loaded over HTTPS, force actionUrl to HTTPS to prevent mixed-content blocking & 301 drop
      if (window.location.protocol === 'https:' && actionUrl.startsWith('http://')) {
        actionUrl = actionUrl.replace(/^http:\/\//i, 'https://');
      }

      const formData = new FormData(contactForm);
      const response = await fetch(actionUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const responseText = await response.text();
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (jsonErr) {
        console.error('Non-JSON server response received:', responseText);
        throw new Error('Server returned an unexpected response format.');
      }

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
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initContactForm);
} else {
  initContactForm();
}
