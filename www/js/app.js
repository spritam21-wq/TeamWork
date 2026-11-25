// TeamWork Real-Time AI Chatroom Client

const broadcast = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('teamwork_room_sync') : null;

const getAutoApiUrl = () => {
  if (typeof window !== 'undefined' && window.location && window.location.origin) {
    return window.location.origin + window.location.pathname.replace(/\/index\.html?$/, '').replace(/\/index\.php?$/, '') + '/index.php';
  }
  return 'index.php';
};

const state = {
  currentUserId: parseInt(sessionStorage.getItem('tw_user_id') || '1'),
  apiUrl: getAutoApiUrl(),
  maxMsgId: 0,
  seenMsgIds: new Set(),
  users: [
    { id: 1, name: 'Admin (Me)', role: 'admin', avatar: 'AD', status: 'online' },
    { id: 2, name: 'Rumi', role: 'muse_agent', avatar: 'RU', status: 'online' },
    { id: 3, name: 'Milo', role: 'muse_agent', avatar: 'MI', status: 'online' },
    { id: 4, name: 'Azar', role: 'muse_agent', avatar: 'AZ', status: 'online' },
    { id: 5, name: 'Faiz', role: 'muse_agent', avatar: 'FA', status: 'online' },
    { id: 6, name: 'Bobby', role: 'muse_agent', avatar: 'BO', status: 'online' }
  ],
  messages: []
};

document.addEventListener('DOMContentLoaded', () => {
  loadStoredMessages();
  fetchUsersFromAPI();
  setupAccountSwitcher();
  setupForm();
  setupAdminModal();
  setupBroadcastChannel();
  fetchNewMessages();
  renderMembers();

  setInterval(fetchNewMessages, 1500);
});

function setupBroadcastChannel() {
  if (broadcast) {
    broadcast.onmessage = (event) => {
      if (event.data && event.data.type === 'new_message') {
        appendMessageOnce(event.data.message);
        scrollToBottom();
      } else if (event.data && event.data.type === 'clear_history') {
        state.messages = [];
        state.seenMsgIds.clear();
        state.maxMsgId = 0;
        localStorage.removeItem('tw_messages');
        renderMessages();
      }
    };
  }
}

function loadStoredMessages() {
  try {
    const stored = localStorage.getItem('tw_messages');
    if (stored) {
      const parsed = JSON.parse(stored);
      if (Array.isArray(parsed)) {
        parsed.forEach(m => appendMessageOnce(m, false));
        renderMessages();
        scrollToBottom();
      }
    }
  } catch (e) {}
}

function saveStoredMessages() {
  try {
    localStorage.setItem('tw_messages', JSON.stringify(state.messages.slice(-100)));
  } catch (e) {}
}

async function fetchUsersFromAPI() {
  try {
    const res = await fetch(`${state.apiUrl}?action=get_users`);
    if (res.ok) {
      const data = await res.json();
      if (data.users && data.users.length > 0) {
        state.users = data.users.map(u => ({
          id: parseInt(u.id),
          name: u.name,
          role: u.role,
          avatar: u.name ? u.name.substring(0, 2).toUpperCase() : 'AI',
          status: u.status || 'online'
        }));
        renderMembers();
        updateAccountDropdown();
      }
    }
  } catch (e) {}
}

function updateAccountDropdown() {
  const select = document.getElementById('accountSelect');
  if (select) {
    select.innerHTML = state.users.map(u => `
      <option value="${u.id}">${u.role === 'admin' ? '👑' : '🤖'} ${u.name}</option>
    `).join('');
    select.value = state.currentUserId.toString();
  }
}

function setupAccountSwitcher() {
  const select = document.getElementById('accountSelect');
  const avatar = document.getElementById('currentUserAvatar');

  if (select) {
    select.value = state.currentUserId.toString();
    select.addEventListener('change', (e) => {
      state.currentUserId = parseInt(e.target.value);
      sessionStorage.setItem('tw_user_id', state.currentUserId);

      const user = state.users.find(u => u.id === state.currentUserId);
      if (user && avatar) avatar.textContent = user.avatar;

      renderMessages();
      renderMembers();
    });
  }

  const currentUser = state.users.find(u => u.id === state.currentUserId);
  if (currentUser && avatar) avatar.textContent = currentUser.avatar;
}

function setupForm() {
  const form = document.getElementById('chatForm');
  const input = document.getElementById('chatInput');

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const content = input.value.trim();
      if (!content) return;

      input.value = ''; // Clear input field immediately

      // 1. INSTANT LOCAL RENDERING (0ms delay)
      const currentUser = state.users.find(u => u.id === state.currentUserId) || {
        id: state.currentUserId,
        name: state.currentUserId === 1 ? 'Admin (Me)' : 'Member',
        role: state.currentUserId === 1 ? 'admin' : 'muse_agent'
      };

      const localMsg = {
        id: 'local_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
        sender_id: state.currentUserId,
        sender_name: currentUser.name,
        sender_role: currentUser.role,
        content: content,
        created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      };

      // FORCE DISPLAY IN SENDER'S CHATBOX
      appendMessageOnce(localMsg);
      scrollToBottom();

      // 2. BROADCAST TO ALL OTHER OPEN WINDOWS
      if (broadcast) {
        broadcast.postMessage({ type: 'new_message', message: localMsg });
      }

      // 3. POST TO BACKEND API IN BACKGROUND
      sendMessageHTTP({
        sender_id: state.currentUserId,
        room_id: 1,
        content: content,
        message_type: 'text'
      });
    });
  }
}

async function sendMessageHTTP(payload) {
  try {
    await fetch(`${state.apiUrl}?action=send_message`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
  } catch (e) {}
}

async function fetchNewMessages() {
  try {
    const res = await fetch(`${state.apiUrl}?action=get_messages&room_id=1&since_id=${state.maxMsgId}`);
    if (res.ok) {
      const data = await res.json();
      if (data.messages && data.messages.length > 0) {
        let hasNew = false;
        data.messages.forEach(m => {
          if (appendMessageOnce(m)) hasNew = true;
        });
        if (hasNew) scrollToBottom();
      }
    }
  } catch (e) {}
}

function appendMessageOnce(msg, save = true) {
  const msgId = msg.id ? msg.id.toString() : 'msg_' + Date.now();
  if (state.seenMsgIds.has(msgId)) return false;

  state.seenMsgIds.add(msgId);
  state.messages.push(msg);

  if (typeof msg.id === 'number' && msg.id > state.maxMsgId) {
    state.maxMsgId = msg.id;
  }

  if (save) saveStoredMessages();
  renderMessages();
  return true;
}

function renderMembers() {
  const container = document.getElementById('membersList');
  const onlineTag = document.getElementById('onlineCountTag');
  if (!container) return;

  if (onlineTag) onlineTag.textContent = `${state.users.length} Active`;

  container.innerHTML = state.users.map(u => `
    <div class="member-item">
      <div class="member-avatar">
        ${u.avatar}
        <span class="status-indicator online"></span>
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
    const isOwn = parseInt(m.sender_id) === state.currentUserId;
    const timeStr = m.created_at ? (m.created_at.length > 10 ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : m.created_at) : '';

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

function setupAdminModal() {
  const btnToggle = document.getElementById('btnAdminPanelToggle');
  const btnClear = document.getElementById('btnClearHistory');
  if (btnToggle) btnToggle.addEventListener('click', openAdminModal);
  if (btnClear) {
    btnClear.addEventListener('click', async () => {
      if (confirm('Clear chat history for everyone?')) {
        state.messages = [];
        state.seenMsgIds.clear();
        state.maxMsgId = 0;
        localStorage.removeItem('tw_messages');
        if (broadcast) broadcast.postMessage({ type: 'clear_history' });
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
      <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:8px;">
        <div>
          <strong>${u.name}</strong>
          <div style="font-size:0.75rem; color:#64748b;">Active Account Session</div>
        </div>
        <span style="font-size:0.8rem; color:#10b981; font-weight:700;">🟢 Connected</span>
      </div>
    `).join('');
  }
  if (modal) modal.classList.add('active');
}

function closeAdminModal() {
  const modal = document.getElementById('adminModal');
  if (modal) modal.classList.remove('active');
}

function scrollToBottom() {
  const container = document.getElementById('messagesContainer');
  if (container) container.scrollTop = container.scrollHeight;
}

function escapeHTML(str) {
  return str.replace(/[&<>'"]/g,
    tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
  );
}
