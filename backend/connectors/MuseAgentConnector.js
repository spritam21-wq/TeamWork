/**
 * Modular Muse Connector
 * Manages real-time connection and AI response generation for actual Muse AI accounts:
 * - Rumi
 * - Milo
 * - Azar
 * - Faiz
 * - Bobby
 */

const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');

class MuseAgentConnector {
  constructor(config) {
    this.userId = config.userId; // 2, 3, 4, 5, or 6
    this.name = config.name;
    this.role = config.role;
    this.googleId = config.googleId;
    this.wsUrl = config.wsUrl || 'ws://127.0.0.1:8080';
    this.ws = null;
    this.isProcessing = false;
    this.accountConfig = this.loadAccountConfig();
  }

  loadAccountConfig() {
    try {
      const configPath = path.join(__dirname, 'session_config.json');
      if (fs.existsSync(configPath)) {
        const raw = fs.readFileSync(configPath, 'utf8');
        const data = JSON.parse(raw);
        const accountKey = this.name.toLowerCase();
        return data.accounts[accountKey] || {};
      }
    } catch (e) {
      console.error(`[${this.name}] Error reading session_config.json:`, e.message);
    }
    return {};
  }

  start() {
    console.log(`[Muse Connector] Starting agent ${this.name} (User ID #${this.userId})...`);
    this.connectWS();
  }

  connectWS() {
    this.ws = new WebSocket(this.wsUrl);

    this.ws.on('open', () => {
      console.log(`[${this.name}] WebSocket connected as ID #${this.userId}`);
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
      console.log(`[${this.name}] Connection lost. Reconnecting in 3s...`);
      setTimeout(() => this.connectWS(), 3000);
    });

    this.ws.on('error', (err) => {
      console.error(`[${this.name}] WebSocket error:`, err.message);
    });
  }

  handleMessage(msg) {
    if (msg.type === 'new_message') {
      const messageData = msg.message;

      // Ignore messages sent by itself to prevent loops
      if (messageData.sender_id === this.userId) return;

      console.log(`[${this.name}] Received message from ${messageData.sender_name}: "${messageData.content}"`);

      // Check if this Muse account should reply
      if (this.shouldReply(messageData)) {
        this.generateAndSendReply(messageData);
      }
    }
  }

  shouldReply(messageData) {
    const text = messageData.content.toLowerCase();
    const nameMention = this.name.toLowerCase();

    // Reply if mentioned directly by name or general prompt
    if (text.includes(nameMention) || text.includes('team') || text.includes('all') || text.includes('everyone')) {
      return true;
    }
    // 25% chance to join group discussion
    return Math.random() < 0.25;
  }

  async generateAndSendReply(incomingMsg) {
    if (this.isProcessing) return;
    this.isProcessing = true;

    // 1. Emit typing status indicator
    this.sendJSON({
      type: 'typing',
      user_id: this.userId,
      is_typing: true
    });

    try {
      // 2. Query actual Muse AI session API or compute response
      const responseText = await this.queryMuseAIService(incomingMsg.content, incomingMsg.sender_name);

      // 3. Stop typing indicator
      this.sendJSON({
        type: 'typing',
        user_id: this.userId,
        is_typing: false
      });

      // 4. Send chat message
      this.sendJSON({
        type: 'chat_message',
        sender_id: this.userId,
        room_id: 1,
        content: responseText,
        message_type: 'ai_reply'
      });
    } catch (err) {
      console.error(`[${this.name}] Error generating response:`, err.message);
      this.sendJSON({ type: 'typing', user_id: this.userId, is_typing: false });
    } finally {
      this.isProcessing = false;
    }
  }

  async queryMuseAIService(prompt, senderName) {
    const sessionToken = this.accountConfig.session_token || process.env[`MUSE_${this.name.toUpperCase()}_TOKEN`];
    const museApiUrl = this.accountConfig.muse_api_url || process.env.MUSE_API_URL;

    // If real Muse API endpoint and Session Token are provided:
    if (sessionToken && sessionToken !== `YOUR_MUSE_${this.name.toUpperCase()}_SESSION_TOKEN_HERE` && museApiUrl) {
      try {
        const response = await fetch(museApiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${sessionToken}`
          },
          body: JSON.stringify({
            prompt: prompt,
            agent_role: this.role,
            sender_name: senderName
          })
        });

        if (response.ok) {
          const data = await response.json();
          if (data && data.reply) return data.reply;
        }
      } catch (e) {
        console.error(`[${this.name}] Muse API Request Failed:`, e.message);
      }
    }

    // Default intelligent response fallback
    await new Promise(res => setTimeout(res, 1800));
    return this.buildFallbackResponse(prompt, senderName);
  }

  buildFallbackResponse(prompt, senderName) {
    const templates = [
      `I hear you ${senderName}. As ${this.name} (${this.role}), I am analyzing the requirement.`,
      `Thanks ${senderName}! ${this.name} is ready for the next sprint task.`,
      `Got it ${senderName}. Reviewing specifications from a ${this.role} perspective.`,
      `All checks green! ${this.name} is standing by.`
    ];
    return templates[Math.floor(Math.random() * templates.length)];
  }

  sendJSON(payload) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(payload));
    }
  }
}

module.exports = MuseAgentConnector;

if (require.main === module) {
  const args = process.argv.slice(2);
  const userId = parseInt(args[0] || '2');
  const name = args[1] || 'Rumi';

  const connector = new MuseAgentConnector({
    userId,
    name,
    role: 'AI Specialist',
    googleId: `google_muse_${name.toLowerCase()}`
  });
  connector.start();
}
