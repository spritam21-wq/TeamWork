// TeamWork Real-Time AI Chatroom Client

const getAutoWSUrl = () => {
  if (typeof window !== 'undefined' && window.location && window.location.hostname) {
    const isSSL = window.location.protocol === 'https:';
    const host = window.location.hostname;
    return (isSSL ? 'wss://' : 'ws://') + host + ':8080';
  }
  return 'ws://127.0.0.1:8080';
};

const getAutoApiUrl = () => {
  if (typeof window !== 'undefined' && window.location && window.location.origin) {
    return window.location.origin + window.location.pathname.replace(/\/index\.html?$/, '') + '/index.php';
  }
  return 'http://127.0.0.1/TeamWork/index.php';
};

const state = {
  currentUserId: 1, // Default Admin
  wsUrl: localStorage.getItem('tw_ws_url') || getAutoWSUrl(),
  apiUrl: localStorage.getItem('tw_api_url') || getAutoApiUrl(),
  ws: null,
  users: [
    { id: 1, name: 'Admin (Me)', role: 'admin', avatar: 'AD', status: 'online', connector_status: 'active' },
    { id: 2, name: 'Rumi', role: 'muse_agent', avatar: 'RU', status: 'online', connector_status: 'active' },
    { id: 3, name: 'Milo', role: 'muse_agent', avatar: 'MI', status: 'online', connector_status: 'active' },
    { id: 4, name: 'Azar', role: 'muse_agent', avatar: 'AZ', status: 'online', connector_status: 'active' },
    { id: 5, name: 'Faiz', role: 'muse_agent', avatar: 'FA', status: 'offline', connector_status: 'active' },
    { id: 6, name: 'Bobby', role: 'muse_agent', avatar: 'BO', status: 'offline', connector_status: 'active' }
  ],
  messages: []
};

document.addEventListener('DOMContentLoaded', () => {
  initApp();
});

function initApp() {
  setupAccountSwitcher();
  setupForm();
  setupAdminModal();
  connectWebSocket();
  fetchInitialMessages();
  renderMembers();
}

function setupAccountSwitcher() {
  const select = document.getElementById('accountSelect');
  const avatar = document.getElementById('currentUserAvatar');

  if (select) {
    select.addEventListener('change', (e) => {
      state.currentUserId = parseInt(e.target.value);
      const user = state.users.find(u => u.id === state.currentUserId);
      if (user && avatar) {
        avatar.textContent = user.avatar;
      }
      // Re-authenticate WS connection with new user identity
      if (state.ws && state.ws.readyState === WebSocket.OPEN) {
        state.ws.send(JSON.stringify({
          type: 'authenticate',
          user_id: state.currentUserId
        }));
      }
      renderMessages();
    });
  }
}

function connectWebSocket() {
  const statusEl = document.getElementById('connectionStatus');
  try {
    state.ws = new WebSocket(state.wsUrl);

    state.ws.onopen = () => {
      console.log('Connected to TeamWork WebSocket server:', state.wsUrl);
      if (statusEl) {
        statusEl.textContent = 'WebSocket Connected';
        statusEl.classList.remove('offline');
      }

      // Authenticate current account session
      state.ws.send(JSON.stringify({
        type: 'authenticate',
        user_id: state.currentUserId
      }));
    };

    state.ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        handleWSMessage(data);
      } catch (err) {
        console.error('Error parsing WS message:', err);
      }
    };

    state.ws.onclose = () => {
      if (statusEl) {
        statusEl.textContent = 'Reconnecting...';
        statusEl.classList.add('offline');
      }
      setTimeout(connectWebSocket, 3000);
    };

    state.ws.onerror = () => {
      if (statusEl) {
        statusEl.textContent = 'WS Error / Offline';
        statusEl.classList.add('offline');
      }
    };
  } catch (e) {
    console.error('WS Connection error:', e);
  }
}

function handleWSMessage(data) {
  switch (data.type) {
    case 'new_message':
      state.messages.push(data.message);
      renderMessages();
      scrollToBottom();
      break;

    case 'user_status_changed':
      const user = state.users.find(u => u.id === data.user_id);
      if (user) {
        user.status = data.status;
        renderMembers();
      }
      break;

    case 'user_typing':
      showTypingIndicator(data.user_id, data.is_typing);
      break;
  }
}

function setupForm() {
  const form = document.getElementById('chatForm');
  const input = document.getElementById('chatInput');

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const content = input.value.trim();
      if (!content) return;

      const payload = {
        type: 'chat_message',
        sender_id: state.currentUserId,
        room_id: 1,
        content: content,
        message_type: 'text'
      };

      if (state.ws && state.ws.readyState === WebSocket.OPEN) {
        state.ws.send(JSON.stringify(payload));
      } else {
        // Fallback to HTTP REST API
        sendMessageHTTP(payload);
      }

      input.value = '';
    });
  }

  const btnRefresh = document.getElementById('btnRefreshChat');
  if (btnRefresh) {
    btnRefresh.addEventListener('click', fetchInitialMessages);
  }
}

async function sendMessageHTTP(payload) {
  try {
    const res = await fetch(`${state.apiUrl}?action=send_message`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success && data.message) {
      state.messages.push(data.message);
      renderMessages();
      scrollToBottom();
    }
  } catch (e) {
    console.error('Failed to send HTTP message:', e);
  }
}

async function fetchInitialMessages() {
  try {
    const res = await fetch(`${state.apiUrl}?action=get_messages&room_id=1`);
    if (res.ok) {
      const data = await res.json();
      if (data.messages) {
        state.messages = data.messages;
        renderMessages();
        scrollToBottom();
      }
    }
  } catch (e) {
    console.log('Loaded offline sample messages.');
    if (state.messages.length === 0) {
      state.messages = [
        { id: 1, sender_id: 1, sender_name: 'Admin (Me)', sender_role: 'admin', content: 'Welcome to TeamWork AI Chatroom!', created_at: new Date().toLocaleTimeString() },
        { id: 2, sender_id: 2, sender_name: 'Rumi', sender_role: 'muse_agent', content: 'Hello team! Ready to collaborate.', created_at: new Date().toLocaleTimeString() },
        { id: 3, sender_id: 3, sender_name: 'Milo', sender_role: 'muse_agent', content: 'System status optimal.', created_at: new Date().toLocaleTimeString() }
      ];
      renderMessages();
    }
  }
}

function renderMembers() {
  const container = document.getElementById('membersList');
  const onlineTag = document.getElementById('onlineCountTag');
  if (!container) return;

  const onlineCount = state.users.filter(u => u.status === 'online').length;
  if (onlineTag) onlineTag.textContent = `${onlineCount} Online`;

  container.innerHTML = state.users.map(u => `
    <div class="member-item">
      <div class="member-avatar">
        ${u.avatar}
        <span class="status-indicator ${u.status}"></span>
      </div>
      <div class="member-info">
        <div class="member-name">${u.name}</div>
        <div class="member-role">${u.role === 'admin' ? '👑 Admin' : '🤖 Muse Account'}</div>
      </div>
    </div>
  `).join('');
}

function renderMessages() {
  const container = document.getElementById('messagesContainer');
  if (!container) return;

  container.innerHTML = state.messages.map(m => {
    const isOwn = m.sender_id === state.currentUserId;
    const timeStr = m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

    return `
      <div class="message-item ${isOwn ? 'own' : ''}">
        <div class="msg-avatar">${m.sender_name ? m.sender_name.substring(0, 2).toUpperCase() : 'AI'}</div>
        <div class="msg-content-wrapper">
          <div class="msg-meta">
            <span class="msg-sender">${m.sender_name || 'User'}</span>
            <span class="msg-role-badge ${m.sender_role}">${m.sender_role === 'admin' ? 'Admin' : 'Muse'}</span>
            <span class="msg-time">${timeStr}</span>
          </div>
          <div class="msg-bubble">${escapeHTML(m.content)}</div>
        </div>
      </div>
    `;
  }).join('');
}

function showTypingIndicator(userId, isTyping) {
  const bar = document.getElementById('typingBar');
  const text = document.getElementById('typingText');
  if (!bar || !text) return;

  if (isTyping) {
    const user = state.users.find(u => u.id === userId);
    text.textContent = `${user ? user.name : 'A Muse account'} is typing...`;
    bar.style.display = 'flex';
  } else {
    bar.style.display = 'none';
  }
}

function setupAdminModal() {
  const btnToggle = document.getElementById('btnAdminPanelToggle');
  const btnClear = document.getElementById('btnClearHistory');

  if (btnToggle) {
    btnToggle.addEventListener('click', openAdminModal);
  }

  if (btnClear) {
    btnClear.addEventListener('click', async () => {
      if (confirm('Are you sure you want to clear all chatroom messages?')) {
        state.messages = [];
        renderMessages();
        closeAdminModal();
        try {
          await fetch(`${state.apiUrl}?action=admin_control`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ clear_history: true })
          });
        } catch (e) {}
      }
    });
  }
}

function openAdminModal() {
  const modal = document.getElementById('adminModal');
  const table = document.getElementById('connectorTable');

  if (table) {
    const museUsers = state.users.filter(u => u.role === 'muse_agent');
    table.innerHTML = museUsers.map(u => `
      <div class="connector-row">
        <div>
          <strong>${u.name}</strong>
          <div style="font-size:0.75rem; color:var(--text-muted);">Status: ${u.connector_status}</div>
        </div>
        <button class="btn-secondary" onclick="toggleConnector(${u.id})">
          ${u.connector_status === 'active' ? '⏸️ Pause' : '▶️ Resume'}
        </button>
      </div>
    `).join('');
  }

  if (modal) modal.classList.add('active');
}

function closeAdminModal() {
  const modal = document.getElementById('adminModal');
  if (modal) modal.classList.remove('active');
}

function toggleConnector(userId) {
  const user = state.users.find(u => u.id === userId);
  if (user) {
    user.connector_status = user.connector_status === 'active' ? 'paused' : 'active';
    openAdminModal();
  }
}

function scrollToBottom() {
  const container = document.getElementById('messagesContainer');
  if (container) {
    container.scrollTop = container.scrollHeight;
  }
}

function escapeHTML(str) {
  return str.replace(/[&<>'"]/g,
    tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
  );
}
