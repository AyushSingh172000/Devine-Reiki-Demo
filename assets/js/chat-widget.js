/**
 * Divine Reiki & Energy Healing Center - AI Chatbot Widget JS
 * Self-contained, framework-free client chat logic.
 */

(function () {
  'use strict';

  // Config & API Endpoints
  const API_BASE = window.location.origin + '/DemoWebsite/api';
  const SESSION_API = API_BASE + '/session.php';
  const HISTORY_API = API_BASE + '/history.php';
  const CHAT_API = API_BASE + '/chat.php';
  const STORAGE_KEY = 'reiki_chat_session';

  let sessionId = localStorage.getItem(STORAGE_KEY) || null;
  let isOpen = false;
  let isLoading = false;

  // DOM Elements cache
  let triggerBtn, widgetContainer, messagesBody, chatForm, chatTextarea, sendBtn, errorToast, typingIndicator;

  // Suggested starter prompts
  const STARTER_PROMPTS = [
    "✨ What is Reiki Healing?",
    "🔮 Recommend a Crystal Bracelet",
    "📅 How do I Book a Free Session?",
    "📜 Tell me about Reiki Courses"
  ];

  document.addEventListener('DOMContentLoaded', () => {
    initWidget();
  });

  async function initWidget() {
    renderWidgetHTML();
    bindEvents();
    await initSession();
    await loadHistory();
  }

  // Obtain or validate session_id from PHP backend
  async function initSession() {
    try {
      const response = await fetch(SESSION_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId })
      });
      const data = await response.json();
      if (data.status === 'success' && data.session_id) {
        sessionId = data.session_id;
        localStorage.setItem(STORAGE_KEY, sessionId);
      }
    } catch (err) {
      console.warn('Reiki Chat: Session init warning', err);
    }
  }

  // Fetch full conversation history from MySQL and render
  async function loadHistory() {
    if (!sessionId) return;

    try {
      const response = await fetch(`${HISTORY_API}?session_id=${encodeURIComponent(sessionId)}`);
      const data = await response.json();

      if (data.status === 'success' && Array.isArray(data.history) && data.history.length > 0) {
        messagesBody.innerHTML = ''; // Clear default container

        let hasUserMsg = false;
        data.history.forEach((msg) => {
          if (msg.role === 'user') hasUserMsg = true;
          appendMessageUI(msg.role, msg.content, msg.created_at);
        });

        // Add starter suggestion chips if no user messages exist yet
        if (!hasUserMsg) {
          renderSuggestions();
        }

        scrollToBottom();
      }
    } catch (err) {
      console.warn('Reiki Chat: History load warning', err);
    }
  }

  // Inject HTML structure into document.body
  function renderWidgetHTML() {
    // 1. Floating Trigger Button
    triggerBtn = document.createElement('button');
    triggerBtn.className = 'reiki-chat-trigger-btn';
    triggerBtn.setAttribute('aria-label', 'Chat with Reiki Bliss AI');
    triggerBtn.innerHTML = `
      <span class="reiki-chat-badge"></span>
      <svg viewBox="0 0 24 24">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/>
        <circle cx="8" cy="10" r="1.2"/>
        <circle cx="12" cy="10" r="1.2"/>
        <circle cx="16" cy="10" r="1.2"/>
      </svg>
    `;
    document.body.appendChild(triggerBtn);

    // 2. Chat Window Container
    widgetContainer = document.createElement('div');
    widgetContainer.className = 'reiki-chat-widget-container';
    widgetContainer.innerHTML = `
      <!-- Header -->
      <div class="reiki-chat-header">
        <div class="reiki-chat-header-info">
          <div class="reiki-chat-avatar-wrapper">
            <img src="assets/images/reikilogo.jpg" alt="Reiki Bliss Avatar" class="reiki-chat-avatar-img">
            <div class="reiki-chat-status-dot"></div>
          </div>
          <div class="reiki-chat-header-text">
            <h3>Reiki Bliss Assistant</h3>
            <p>Powered by Reiki AI • Online</p>
          </div>
        </div>
        <div class="reiki-chat-header-actions">
          <button type="button" class="reiki-chat-header-btn" id="reiki-reset-chat" title="New Session">
            <svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
          </button>
          <button type="button" class="reiki-chat-header-btn" id="reiki-close-chat" title="Minimize Chat">
            <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
          </button>
        </div>
      </div>

      <!-- Messages Body -->
      <div class="reiki-chat-body" id="reiki-chat-messages">
        <!-- Messages & history will be loaded here dynamically -->
      </div>

      <!-- Error Toast -->
      <div class="reiki-chat-error-toast" id="reiki-chat-error"></div>

      <!-- Footer / Input Form -->
      <div class="reiki-chat-footer">
        <form class="reiki-chat-input-form" id="reiki-chat-form">
          <textarea class="reiki-chat-textarea" id="reiki-chat-input" placeholder="Type your message..." rows="1"></textarea>
          <button type="submit" class="reiki-chat-send-btn" id="reiki-chat-send" aria-label="Send message">
            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
          </button>
        </form>
      </div>
    `;
    document.body.appendChild(widgetContainer);

    // Cache internal elements
    messagesBody = widgetContainer.querySelector('#reiki-chat-messages');
    chatForm = widgetContainer.querySelector('#reiki-chat-form');
    chatTextarea = widgetContainer.querySelector('#reiki-chat-input');
    sendBtn = widgetContainer.querySelector('#reiki-chat-send');
    errorToast = widgetContainer.querySelector('#reiki-chat-error');
  }

  // Render starter prompt chips
  function renderSuggestions() {
    let sugDiv = widgetContainer.querySelector('#reiki-suggestions');
    if (!sugDiv) {
      sugDiv = document.createElement('div');
      sugDiv.className = 'reiki-chat-suggestions';
      sugDiv.id = 'reiki-suggestions';
      messagesBody.appendChild(sugDiv);
    }
    sugDiv.innerHTML = STARTER_PROMPTS.map(p => `<button class="reiki-suggestion-chip">${escapeHTML(p)}</button>`).join('');
  }

  // Bind Event Listeners
  function bindEvents() {
    // Toggle chat open/close (minimize)
    triggerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleChat();
    });

    widgetContainer.addEventListener('click', (e) => {
      e.stopPropagation();
    });

    widgetContainer.querySelector('#reiki-close-chat').addEventListener('click', (e) => {
      e.stopPropagation();
      toggleChat(false);
    });

    // Minimize widget on clicking outside
    document.addEventListener('click', (e) => {
      if (isOpen && !widgetContainer.contains(e.target) && !triggerBtn.contains(e.target)) {
        toggleChat(false);
      }
    });

    // Reset Chat session
    widgetContainer.querySelector('#reiki-reset-chat').addEventListener('click', resetSession);

    // Textarea Auto-height & submit on Enter
    chatTextarea.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        chatForm.dispatchEvent(new Event('submit'));
      }
    });

    chatTextarea.addEventListener('input', () => {
      chatTextarea.style.height = 'auto';
      chatTextarea.style.height = Math.min(chatTextarea.scrollHeight, 100) + 'px';
    });

    // Form submit
    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const text = chatTextarea.value.trim();
      if (text && !isLoading) {
        handleUserSend(text);
      }
    });

    // Quick suggestion chips
    widgetContainer.addEventListener('click', (e) => {
      if (e.target.classList.contains('reiki-suggestion-chip')) {
        const promptText = e.target.textContent.replace(/^[^\w\s]+/, '').trim();
        handleUserSend(promptText);
      }
    });
  }

  function toggleChat(forceState) {
    if (typeof forceState === 'boolean') {
      isOpen = forceState;
    } else {
      isOpen = !isOpen;
    }

    if (isOpen) {
      widgetContainer.classList.add('reiki-chat-open');
      chatTextarea.focus();
      scrollToBottom();
    } else {
      widgetContainer.classList.remove('reiki-chat-open');
    }
  }

  async function resetSession() {
    localStorage.removeItem(STORAGE_KEY);
    sessionId = null;
    messagesBody.innerHTML = '';
    await initSession();
    await loadHistory();
  }

  async function handleUserSend(text) {
    // Render user message immediately
    appendMessageUI('user', text);
    chatTextarea.value = '';
    chatTextarea.style.height = '42px';
    hideError();

    // Hide suggestion chips after user sends a message
    const sugDiv = widgetContainer.querySelector('#reiki-suggestions');
    if (sugDiv) sugDiv.style.display = 'none';

    // Show typing indicator
    showTyping();
    setLoadingState(true);

    try {
      if (!sessionId) {
        await initSession();
      }

      const response = await fetch(CHAT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: sessionId,
          message: text
        })
      });

      const data = await response.json();
      hideTyping();

      if (response.ok && data.status === 'success') {
        appendMessageUI('assistant', data.reply);
      } else {
        const errorMsg = data.message || 'Unable to connect to AI Assistant. Please try again.';
        showError(errorMsg);
        appendMessageUI('assistant', '🙏 I am temporarily having trouble reaching the energy network. Please call/WhatsApp us directly at +91 97265 81787 for immediate healing guidance.');
      }
    } catch (err) {
      console.error('Reiki Chat API Error:', err);
      hideTyping();
      showError('Network error connecting to proxy service.');
      appendMessageUI('assistant', '⚠️ Connection error. Please ensure your network is connected and try again.');
    } finally {
      setLoadingState(false);
      scrollToBottom();
    }
  }

  function appendMessageUI(role, text, timestamp) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `reiki-chat-msg reiki-msg-${role}`;
    
    const formattedText = formatMarkdown(text);
    const dateObj = timestamp ? new Date(timestamp) : new Date();
    const timeStr = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    msgDiv.innerHTML = `
      <div class="reiki-chat-bubble">${formattedText}</div>
      <div class="reiki-chat-time">${timeStr}</div>
    `;

    messagesBody.appendChild(msgDiv);
    scrollToBottom();
  }

  function showTyping() {
    if (typingIndicator) return;
    typingIndicator = document.createElement('div');
    typingIndicator.className = 'reiki-chat-typing-indicator';
    typingIndicator.innerHTML = `
      <div class="reiki-typing-dot"></div>
      <div class="reiki-typing-dot"></div>
      <div class="reiki-typing-dot"></div>
    `;
    messagesBody.appendChild(typingIndicator);
    scrollToBottom();
  }

  function hideTyping() {
    if (typingIndicator && typingIndicator.parentNode) {
      typingIndicator.parentNode.removeChild(typingIndicator);
      typingIndicator = null;
    }
  }

  function setLoadingState(loading) {
    isLoading = loading;
    chatTextarea.disabled = loading;
    sendBtn.disabled = loading;
    if (!loading) {
      chatTextarea.focus();
    }
  }

  function showError(msg) {
    errorToast.textContent = msg;
    errorToast.style.display = 'block';
    setTimeout(() => hideError(), 6000);
  }

  function hideError() {
    errorToast.style.display = 'none';
    errorToast.textContent = '';
  }

  function scrollToBottom() {
    messagesBody.scrollTop = messagesBody.scrollHeight;
  }

  function formatMarkdown(str) {
    if (!str) return '';
    let html = escapeHTML(str);
    // Bold
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Bullet points
    html = html.replace(/^\s*[\-\*]\s+(.*)$/gm, '• $1');
    // Linebreaks
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  function escapeHTML(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

})();
