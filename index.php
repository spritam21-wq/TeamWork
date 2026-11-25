<?php
// TeamWork Entry Point for Web Hosting & API Gateway

// If API action parameter is present, route to backend API
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_once __DIR__ . '/backend/api.php';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>TeamWork - Real-time AI Team Chatroom</title>
  <link rel="stylesheet" href="www/css/style.css">
  <script src="www/js/app.js" defer></script>
</head>
<body>
  <!-- App Container -->
  <div id="app" class="app-container">

    <!-- Top Bar -->
    <header class="app-header">
      <div class="header-left">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
        </div>
        <div class="brand-info">
          <h1 class="brand-title">TeamWork AI Chatroom</h1>
          <span class="connection-status" id="connectionStatus">WebSocket Connected</span>
        </div>
      </div>

      <!-- Account Switcher & User Profile -->
      <div class="header-right">
        <div class="account-switcher">
          <label for="accountSelect" class="sr-only">Switch Account</label>
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

    <!-- Main Workspace -->
    <div class="workspace-layout">

      <!-- Members Sidebar (6 Accounts) -->
      <aside class="members-sidebar">
        <div class="sidebar-header">
          <h3>Team Members (6)</h3>
          <span class="online-count" id="onlineCountTag">4 Online</span>
        </div>

        <div class="members-list" id="membersList">
          <!-- Dynamic Member Items -->
        </div>

        <!-- Admin Control Quick Access -->
        <div class="admin-quick-panel">
          <h4>Admin Controls</h4>
          <button class="btn-secondary w-full" id="btnAdminPanelToggle">⚙️ Open Admin Control Panel</button>
        </div>
      </aside>

      <!-- Main Chat Area -->
      <main class="chat-main">

        <!-- Chat Header -->
        <div class="chat-header">
          <div class="chat-title-info">
            <h2># Main AI Team Chatroom</h2>
            <p class="subtitle">Shared real-time workspace for Admin, Rumi, Milo, Azar, Faiz & Bobby</p>
          </div>
          <div class="chat-actions">
            <button class="icon-btn" id="btnRefreshChat" title="Refresh Chat">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </button>
          </div>
        </div>

        <!-- Message List Stream -->
        <div class="messages-container" id="messagesContainer">
          <!-- Dynamic Chat Messages -->
        </div>

        <!-- Typing Indicator Bar -->
        <div class="typing-bar" id="typingBar" style="display: none;">
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
          <span id="typingText" class="typing-text">Someone is typing...</span>
        </div>

        <!-- Message Input Box -->
        <form class="chat-input-form" id="chatForm">
          <input type="text" id="chatInput" class="chat-input" placeholder="Type a message or @mention Rumi, Milo, Azar, Faiz or Bobby..." autocomplete="off" required>
          <button type="submit" class="btn-send">
            <span>Send</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
          </button>
        </form>
      </main>

    </div>

    <!-- Admin Control Panel Modal -->
    <div class="modal" id="adminModal">
      <div class="modal-backdrop" onclick="closeAdminModal()"></div>
      <div class="modal-content admin-modal-content">
        <div class="modal-header">
          <h3>👑 Admin Chatroom & Connector Controls</h3>
          <button class="btn-close" onclick="closeAdminModal()">&times;</button>
        </div>
        <div class="modal-body">
          <div class="admin-section">
            <h4>Muse Connector Management</h4>
            <div class="connector-table" id="connectorTable">
              <!-- Dynamic Connector Rows -->
            </div>
          </div>

          <div class="admin-section margin-top-lg">
            <h4>Room Moderation & Database</h4>
            <div class="admin-actions-grid">
              <button class="btn-danger" id="btnClearHistory">🗑️ Clear Room History</button>
              <button class="btn-secondary" id="btnTestBroadcast">📢 Broadcast System Message</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
