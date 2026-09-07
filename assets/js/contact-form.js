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
    if (alertBox) {
      alertBox.style.display = 'none';
      alertBox.className = 'form-alert-box';
      alertBox.textContent = '';
    }

    // Client-side Validation
    const nameInput = contactForm.querySelector('[name="name"]');
    const emailInput = contactForm.querySelector('[name="email"]');
    const phoneInput = contactForm.querySelector('[name="phone"]');
    const messageInput = contactForm.querySelector('[name="message"]');

    if (!nameInput.value.trim()) {
      showAlert('Please enter your full name.', 'error');
      nameInput.focus();
      return;
    }

    if (!emailInput.value.trim() || !emailInput.value.includes('@')) {
      showAlert('Please enter a valid email address.', 'error');
      emailInput.focus();
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
        showAlert(result.message, 'success');
        contactForm.reset();
      } else {
        showAlert(result.message || 'Failed to submit form.', 'error');
      }
    } catch (error) {
      console.error('Contact submission error:', error);
      showAlert('An unexpected network error occurred. Please try again or WhatsApp us directly.', 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.origText || 'Send Message →';
      }
    }
  });

  function showAlert(msg, type) {
    if (!alertBox) return;
    alertBox.textContent = msg;
    alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
    alertBox.style.display = 'block';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
});
