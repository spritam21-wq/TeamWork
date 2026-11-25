<?php
// TeamWork Real-Time AI Chatroom Entry Point & API Gateway

// Handle API requests if 'action' parameter is present
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_once __DIR__ . '/backend/api.php';
    exit();
}

$version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>TeamWork - Real-time AI Team Chatroom</title>
  <style>
    :root {
      --primary: #4F46E5;
      --primary-hover: #4338CA;
      --primary-gradient: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
      --bg-main: #F8FAFC;
      --bg-card: #FFFFFF;
      --bg-sidebar: #F1F5F9;
      --text-main: #0F172A;
      --text-muted: #64748B;
      --border-color: #E2E8F0;
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.08);
      --radius-md: 12px;
      --radius-sm: 8px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg-main); color: var(--text-main); height: 100vh; width: 100vw; overflow: hidden; }

    .app-container { display: flex; flex-direction: column; height: 100vh; width: 100vw; }

    /* Header */
    .app-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); z-index: 10; }
    .header-left { display: flex; align-items: center; gap: 12px; }
    .brand-logo { width: 38px; height: 38px; background: var(--primary-gradient); color: #fff; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
    .brand-logo svg { width: 22px; height: 22px; }
    .brand-title { font-size: 1.15rem; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
    .connection-status { font-size: 0.72rem; font-weight: 600; color: #10B981; display: flex; align-items: center; gap: 4px; }
    .connection-status::before { content: ''; width: 6px; height: 6px; background: #10B981; border-radius: 50%; display: inline-block; }
    .connection-status.offline { color: #EF4444; }
    .connection-status.offline::before { background: #EF4444; }

    .header-right { display: flex; align-items: center; gap: 12px; }
    .account-dropdown { padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-main); font-weight: 600; font-size: 0.88rem; outline: none; cursor: pointer; }
    .user-avatar { width: 36px; height: 36px; background: var(--primary-gradient); color: #fff; font-weight: 700; font-size: 0.85rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    /* Layout */
    .workspace-layout { display: flex; flex: 1; overflow: hidden; }
    .members-sidebar { width: 270px; background: var(--bg-sidebar); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; padding: 16px; }
    .sidebar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .sidebar-header h3 { font-size: 0.95rem; font-weight: 700; margin: 0; }
    .online-count { font-size: 0.75rem; background: #DCFCE7; color: #15803D; font-weight: 700; padding: 2px 8px; border-radius: 12px; }
    .members-list { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }

    .member-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: var(--radius-sm); background: var(--bg-card); border: 1px solid var(--border-color); }
    .member-avatar { width: 34px; height: 34px; border-radius: 50%; background: #E0E7FF; color: var(--primary); font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; position: relative; }
    .status-indicator { width: 9px; height: 9px; border-radius: 50%; border: 2px solid #fff; position: absolute; bottom: 0; right: 0; background: #94A3B8; }
    .status-indicator.online { background: #10B981; }
    .member-name { font-size: 0.88rem; font-weight: 600; }
    .member-role { font-size: 0.7rem; color: var(--text-muted); }

    /* Chat Main */
    .chat-main { flex: 1; display: flex; flex-direction: column; background: var(--bg-main); overflow: hidden; }
    .chat-header { padding: 16px 24px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); }
    .chat-header h2 { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .subtitle { font-size: 0.82rem; color: var(--text-muted); margin-top: 2px; }

    .messages-container { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; }
    .message-item { display: flex; gap: 12px; max-width: 80%; }
    .message-item.own { align-self: flex-end; flex-direction: row-reverse; }
    .msg-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-gradient); color: #fff; font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .msg-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .msg-sender { font-size: 0.8rem; font-weight: 700; }
    .msg-role-badge { font-size: 0.65rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: #EEF2FF; color: var(--primary); }
    .msg-time { font-size: 0.7rem; color: var(--text-muted); }
    .msg-bubble { background: var(--bg-card); border: 1px solid var(--border-color); padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.9rem; line-height: 1.45; box-shadow: var(--shadow-sm); color: var(--text-main); }
    .message-item.own .msg-bubble { background: var(--primary); color: #FFFFFF; border: none; }

    /* Typing Indicator */
    .typing-bar { display: flex; align-items: center; gap: 6px; padding: 6px 24px; font-size: 0.78rem; color: var(--text-muted); }
    .typing-dot { width: 6px; height: 6px; background: var(--primary); border-radius: 50%; animation: typingBounce 1.2s infinite ease-in-out; }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingBounce { 0%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-5px); } }

    /* Chat Form */
    .chat-input-form { display: flex; align-items: center; gap: 12px; padding: 16px 24px; background: var(--bg-card); border-top: 1px solid var(--border-color); }
    .chat-input { flex: 1; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-main); font-size: 0.9rem; outline: none; }
    .btn-send { background: var(--primary); color: #fff; border: none; padding: 12px 20px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; cursor: pointer; }

    /* Admin Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px); }
    .modal.active { display: flex; }
    .modal-content { background: var(--bg-card); border-radius: var(--radius-md); padding: 24px; width: 100%; max-width: 500px; box-shadow: var(--shadow-md); }
    .btn-secondary { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; width: 100%; }
    .btn-danger { background: #EF4444; color: #fff; border: none; padding: 10px 16px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
  </style>
</head>
<body>
  <div id="app" class="app-container">

    <!-- Top Header -->
    <header class="app-header">
      <div class="header-left">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="brand-info">
          <h1 class="brand-title">TeamWork AI Chatroom</h1>
          <span class="connection-status" id="connectionStatus">WebSocket Connected</span>
        </div>
      </div>

      <div class="header-right">
        <div class="account-switcher">
          <select id="accountSelect" class="account-dropdown">
            <option value="1">👑 Admin (Me)</option>
            <option value="2">🤖 Rumi</option>
            <option value="3">🤖 Milo</option>
            <option value="4">🤖 Azar</option>
            <option value="5">🤖 Faiz</option>
            <option value="6">🤖 Bobby</option>
          </select>
        </div>
        <div class="user-avatar" id="currentUserAvatar">AD</div>
      </div>
    </header>

    <!-- Workspace -->
    <div class="workspace-layout">

      <!-- Members Sidebar -->
      <aside class="members-sidebar">
        <div class="sidebar-header">
          <h3>Team Members (6)</h3>
          <span class="online-count" id="onlineCountTag">4 Online</span>
        </div>

        <div class="members-list" id="membersList">
          <!-- Dynamic Member Items -->
        </div>

        <div class="admin-quick-panel">
          <button class="btn-secondary" id="btnAdminPanelToggle">⚙️ Open Admin Control Panel</button>
        </div>
      </aside>

      <!-- Main Chat Stream -->
      <main class="chat-main">
        <div class="chat-header">
          <h2># Main AI Team Chatroom</h2>
          <p class="subtitle">Shared real-time workspace for Admin, Rumi, Milo, Azar, Faiz & Bobby</p>
        </div>

        <div class="messages-container" id="messagesContainer">
          <!-- Messages -->
        </div>

        <div class="typing-bar" id="typingBar" style="display: none;">
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
          <span id="typingText">Someone is typing...</span>
        </div>

        <form class="chat-input-form" id="chatForm">
          <input type="text" id="chatInput" class="chat-input" placeholder="Type a message or @mention Rumi, Milo, Azar, Faiz or Bobby..." autocomplete="off" required>
          <button type="submit" class="btn-send">Send</button>
        </form>
      </main>

    </div>

    <!-- Admin Modal -->
    <div class="modal" id="adminModal">
      <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="margin:0;">👑 Admin Controls</h3>
          <button onclick="closeAdminModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div id="connectorTable"></div>
        <button id="btnClearHistory" class="btn-danger" style="margin-top:16px;">🗑️ Clear Room History</button>
      </div>
    </div>

  </div>

  <script>
    // Embedded App Controller
    const getAutoWSUrl = () => {
      if (typeof window !== 'undefined' && window.location && window.location.hostname) {
        const isSSL = window.location.protocol === 'https:';
        return (isSSL ? 'wss://' : 'ws://') + window.location.hostname + ':8080';
      }
      return 'ws://127.0.0.1:8080';
    };

    const getAutoApiUrl = () => {
      if (typeof window !== 'undefined' && window.location && window.location.origin) {
        return window.location.origin + window.location.pathname.replace(/\/index\.php?$/, '') + '/index.php';
      }
      return 'index.php';
    };

    const state = {
      currentUserId: parseInt(sessionStorage.getItem('tw_user_id') || '1'),
      wsUrl: localStorage.getItem('tw_ws_url') || getAutoWSUrl(),
      apiUrl: getAutoApiUrl(),
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
      fetchUsersFromAPI();
      setupAccountSwitcher();
      setupForm();
      setupAdminModal();
      connectWebSocket();
      fetchInitialMessages();
      renderMembers();
    });

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
              status: u.status || 'offline',
              connector_status: u.connector_status || 'active'
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

          if (state.ws && state.ws.readyState === WebSocket.OPEN) {
            state.ws.send(JSON.stringify({ type: 'authenticate', user_id: state.currentUserId }));
          }

          renderMessages();
          renderMembers();
        });
      }

      const currentUser = state.users.find(u => u.id === state.currentUserId);
      if (currentUser && avatar) avatar.textContent = currentUser.avatar;
    }

    function connectWebSocket() {
      const statusEl = document.getElementById('connectionStatus');
      try {
        state.ws = new WebSocket(state.wsUrl);

        state.ws.onopen = () => {
          if (statusEl) {
            statusEl.textContent = 'WebSocket Connected';
            statusEl.classList.remove('offline');
          }
          state.ws.send(JSON.stringify({ type: 'authenticate', user_id: state.currentUserId }));
        };

        state.ws.onmessage = (event) => {
          try {
            const data = JSON.parse(event.data);
            handleWSMessage(data);
          } catch (err) {}
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
            statusEl.textContent = 'WS Offline';
            statusEl.classList.add('offline');
          }
        };
      } catch (e) {}
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
          if (user) { user.status = data.status; renderMembers(); }
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
            sendMessageHTTP(payload);
          }

          input.value = '';
        });
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
      } catch (e) {}
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
      if (btnToggle) btnToggle.addEventListener('click', openAdminModal);
      if (btnClear) {
        btnClear.addEventListener('click', async () => {
          if (confirm('Clear chat history?')) {
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
          <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:8px;">
            <div>
              <strong>${u.name}</strong>
              <div style="font-size:0.75rem; color:#64748b;">Status: ${u.connector_status}</div>
            </div>
            <button class="btn-secondary" style="width:auto;" onclick="toggleConnector(${u.id})">
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
      if (container) container.scrollTop = container.scrollHeight;
    }

    function escapeHTML(str) {
      return str.replace(/[&<>'"]/g,
        tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
      );
    }
  </script>
</body>
</html>
