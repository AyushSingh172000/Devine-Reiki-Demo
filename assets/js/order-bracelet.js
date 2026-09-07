/**
 * Order Bracelet AJAX Submission & Intention Toggle JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
  const orderForm = document.getElementById('bracelet-order-form');
  const alertBox = document.getElementById('order-alert-box');
  const successBox = document.getElementById('order-success-box');
  const submitBtn = document.getElementById('order-submit-btn');
  const intentionSelect = document.getElementById('intention-select');
  const customIntentionGroup = document.getElementById('custom-intention-group');

  // Toggle custom intention field
  if (intentionSelect && customIntentionGroup) {
    intentionSelect.addEventListener('change', () => {
      if (intentionSelect.value === 'Custom Intention') {
        customIntentionGroup.style.display = 'block';
      } else {
        customIntentionGroup.style.display = 'none';
      }
    });
  }

  if (!orderForm) return;

  orderForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (alertBox) {
      alertBox.style.display = 'none';
      alertBox.className = 'form-alert-box';
      alertBox.textContent = '';
    }

    // Disable Submit Button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.origText = submitBtn.textContent;
      submitBtn.textContent = 'Processing Order...';
    }

    try {
      const formData = new FormData(orderForm);
      const response = await fetch(orderForm.action, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        orderForm.style.display = 'none';
        if (successBox) {
          successBox.style.display = 'block';
          const successMsg = successBox.querySelector('.success-msg-text');
          const waBtn = successBox.querySelector('.wa-followup-btn');
          if (successMsg) successMsg.textContent = result.message;
          if (waBtn && result.whatsapp_url) waBtn.href = result.whatsapp_url;
          successBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      } else {
        showAlert(result.message || 'Failed to submit order.', 'error');
      }
    } catch (error) {
      console.error('Order submission error:', error);
      showAlert('An unexpected network error occurred. Please try again or WhatsApp us directly.', 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.origText || 'Submit Order →';
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
