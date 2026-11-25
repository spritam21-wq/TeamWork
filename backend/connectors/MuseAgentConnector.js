/**
 * Modular Muse Connector Template
 * Each of the 5 existing Muse accounts runs an independent instance of this connector class.
 *
 * Supports:
 * - Independent Google / Muse account authentication context
 * - Modular IAgentConnector interface for swapping/upgrading AI models
 * - Real-time WebSocket listening & automated smart replies
 * - Typing indicators & heartbeat ping
 */

const WebSocket = require('ws');

class MuseAgentConnector {
  constructor(config) {
    this.userId = config.userId; // 2, 3, 4, 5, or 6
    this.name = config.name;
    this.role = config.role;
    this.googleId = config.googleId;
    this.wsUrl = config.wsUrl || 'ws://127.0.0.1:8080';
    this.ws = null;
    this.isProcessing = false;
  }

  start() {
    console.log(`[Muse Connector] Starting agent ${this.name} (User ID #${this.userId})...`);
    this.connectWS();
  }

  connectWS() {
    this.ws = new WebSocket(this.wsUrl);

    this.ws.on('open', () => {
      console.log(`[${this.name}] WebSocket connected.`);
      // Authenticate this specific Muse account
      this.sendJSON({
        type: 'authenticate',
        user_id: this.userId,
        google_id: this.googleId
      });
    });

    this.ws.on('message', (data) => {
      try {
        const msg = JSON.parse(data);
        this.handleMessage(msg);
      } catch (e) {
        console.error(`[${this.name}] Error parsing WS payload:`, e.message);
      }
    });

    this.ws.on('close', () => {
      console.log(`[${this.name}] WebSocket disconnected. Reconnecting in 3s...`);
      setTimeout(() => this.connectWS(), 3000);
    });

    this.ws.on('error', (err) => {
      console.error(`[${this.name}] WebSocket error:`, err.message);
    });
  }

  handleMessage(msg) {
    if (msg.type === 'new_message') {
      const messageData = msg.message;

      // Ignore messages sent by itself to avoid infinite loop
      if (messageData.sender_id === this.userId) return;

      console.log(`[${this.name}] Received message from ${messageData.sender_name}: "${messageData.content}"`);

      // Check if message is relevant (or if @mentioned or random prompt)
      if (this.shouldReply(messageData)) {
        this.generateAndSendReply(messageData);
      }
    }
  }

  shouldReply(messageData) {
    const text = messageData.content.toLowerCase();
    const nameMention = this.name.toLowerCase();

    // Reply if mentioned directly by name or if general team prompt
    if (text.includes(nameMention) || text.includes('team') || text.includes('all') || text.includes('everyone')) {
      return true;
    }
    // 25% chance to participate in group discussion
    return Math.random() < 0.25;
  }

  async generateAndSendReply(incomingMsg) {
    if (this.isProcessing) return;
    this.isProcessing = true;

    // 1. Emit typing indicator
    this.sendJSON({
      type: 'typing',
      user_id: this.userId,
      is_typing: true
    });

    // Simulated response delay for natural team conversation
    const delay = Math.floor(Math.random() * 2000) + 1500;

    setTimeout(() => {
      // 2. Stop typing indicator
      this.sendJSON({
        type: 'typing',
        user_id: this.userId,
        is_typing: false
      });

      // 3. Formulate response from Muse interface
      const responseText = this.buildResponseText(incomingMsg.content, incomingMsg.sender_name);

      // 4. Send chat message response
      this.sendJSON({
        type: 'chat_message',
        sender_id: this.userId,
        room_id: 1,
        content: responseText,
        message_type: 'ai_reply'
      });

      this.isProcessing = false;
    }, delay);
  }

  buildResponseText(prompt, senderName) {
    const templates = [
      `I agree with ${senderName}. From a ${this.role} perspective, we should ensure test coverage is high.`,
      `Thanks for the update ${senderName}! I am currently processing the task queue.`,
      `Reviewing this now. Let me know if you need specific outputs for ${this.role}.`,
      `All systems operational. Ready for the next phase!`
    ];
    return templates[Math.floor(Math.random() * templates.length)];
  }

  sendJSON(payload) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(payload));
    }
  }
}

// Export for execution or standalone runner
module.exports = MuseAgentConnector;

// Allow direct execution from CLI: node backend/connectors/MuseAgentConnector.js 2 "Muse Agent 1 (Strategist)"
if (require.main === module) {
  const args = process.argv.slice(2);
  const userId = parseInt(args[0] || '2');
  const name = args[1] || `Muse Agent ${userId - 1}`;

  const connector = new MuseAgentConnector({
    userId,
    name,
    role: 'AI Specialist',
    googleId: `google_muse_00${userId - 1}`
  });
  connector.start();
}
