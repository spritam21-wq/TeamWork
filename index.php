<?php
// TeamWork Entry Point for Web Hosting & API Gateway

// If API action parameter is present, route to backend API
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_once __DIR__ . '/backend/api.php';
    exit();
}

$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>TeamWork - Real-time AI Team Chatroom</title>
  <link rel="stylesheet" href="<?php echo $baseDir; ?>/www/css/style.css?v=<?php echo $version; ?>">
  <script src="<?php echo $baseDir; ?>/www/js/app.js?v=<?php echo $version; ?>" defer></script>
  <style>
    /* Immediate Fallback Base Styles */
    body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
    .app-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: #ffffff; border-bottom: 1px solid #e2e8f0; }
    .brand-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
    .account-dropdown { padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600; }
    .workspace-layout { display: flex; height: calc(100vh - 65px); }
    .members-sidebar { width: 260px; background: #f1f5f9; padding: 16px; border-right: 1px solid #e2e8f0; }
    .chat-main { flex: 1; display: flex; flex-direction: column; background: #f8fafc; }
    .chat-header { padding: 16px; background: #fff; border-bottom: 1px solid #e2e8f0; }
    .messages-container { flex: 1; padding: 20px; overflow-y: auto; }
    .chat-input-form { display: flex; padding: 16px; background: #fff; border-top: 1px solid #e2e8f0; gap: 10px; }
    .chat-input { flex: 1; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.9rem; }
    .btn-send { background: #4f46e5; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
  </style>
</head>
<body>
  <!-- App Container -->
  <div id="app" class="app-container">

    <!-- Top Bar -->
    <header class="app-header">
      <div class="header-left">
        <div class="brand-info">
          <h1 class="brand-title">TeamWork AI Chatroom</h1>
          <span class="connection-status" id="connectionStatus">WebSocket Connected</span>
        </div>
      </div>

      <!-- Account Switcher & User Profile -->
      <div class="header-right">
        <div class="account-switcher">
          <label for="accountSelect" style="font-size:0.8rem; font-weight:600; margin-right:6px;">Logged in as:</label>
          <select id="accountSelect" class="account-dropdown">
            <option value="1">👑 Admin (Me)</option>
            <option value="2">🤖 Rumi</option>
            <option value="3">🤖 Milo</option>
            <option value="4">🤖 Azar</option>
            <option value="5">🤖 Faiz</option>
            <option value="6">🤖 Bobby</option>
          </select>
        </div>
        <div class="user-avatar" id="currentUserAvatar" style="width:36px; height:36px; background:#4f46e5; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">AD</div>
      </div>
    </header>

    <!-- Main Workspace -->
    <div class="workspace-layout">

      <!-- Members Sidebar (6 Accounts) -->
      <aside class="members-sidebar">
        <div class="sidebar-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <h3 style="margin:0;">Team Members (6)</h3>
          <span class="online-count" id="onlineCountTag" style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px; font-size:0.75rem; font-weight:700;">4 Online</span>
        </div>

        <div class="members-list" id="membersList">
          <div style="padding:8px; background:#fff; border-radius:6px; margin-bottom:6px; font-weight:600;">👑 Admin (Me)</div>
          <div style="padding:8px; background:#fff; border-radius:6px; margin-bottom:6px;">🤖 Rumi</div>
          <div style="padding:8px; background:#fff; border-radius:6px; margin-bottom:6px;">🤖 Milo</div>
          <div style="padding:8px; background:#fff; border-radius:6px; margin-bottom:6px;">🤖 Azar</div>
          <div style="padding:8px; background:#fff; border-radius:6px; margin-bottom:6px;">🤖 Faiz</div>
          <div style="padding:8px; background:#fff; border-radius:6px;">🤖 Bobby</div>
        </div>

        <!-- Admin Control Quick Access -->
        <div class="admin-quick-panel" style="margin-top:20px;">
          <button class="btn-secondary" id="btnAdminPanelToggle" style="width:100%; padding:10px; border:1px solid #cbd5e1; background:#fff; border-radius:8px; font-weight:600; cursor:pointer;">⚙️ Admin Controls</button>
        </div>
      </aside>

      <!-- Main Chat Area -->
      <main class="chat-main">

        <!-- Chat Header -->
        <div class="chat-header">
          <h2 style="margin:0; font-size:1.1rem;"># Main AI Team Chatroom</h2>
          <p style="margin:4px 0 0 0; font-size:0.8rem; color:#64748b;">Shared real-time workspace for Admin, Rumi, Milo, Azar, Faiz & Bobby</p>
        </div>

        <!-- Message List Stream -->
        <div class="messages-container" id="messagesContainer">
          <!-- Dynamic Chat Messages -->
        </div>

        <!-- Typing Indicator Bar -->
        <div class="typing-bar" id="typingBar" style="display: none; padding:8px 20px; font-size:0.8rem; color:#64748b;">
          <span id="typingText">Someone is typing...</span>
        </div>

        <!-- Message Input Box -->
        <form class="chat-input-form" id="chatForm">
          <input type="text" id="chatInput" class="chat-input" placeholder="Type a message or @mention Rumi, Milo, Azar, Faiz or Bobby..." autocomplete="off" required>
          <button type="submit" class="btn-send">Send</button>
        </form>
      </main>

    </div>

    <!-- Admin Control Panel Modal -->
    <div class="modal" id="adminModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
      <div class="modal-content" style="background:#fff; padding:24px; border-radius:12px; max-width:500px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <h3 style="margin:0;">👑 Admin Controls</h3>
          <button onclick="closeAdminModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer;">&times;</button>
        </div>
        <div id="connectorTable" style="margin-top:16px;"></div>
        <button id="btnClearHistory" style="margin-top:16px; background:#ef4444; color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer;">🗑️ Clear Room History</button>
      </div>
    </div>

  </div>
</body>
</html>
