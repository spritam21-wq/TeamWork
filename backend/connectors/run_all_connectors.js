/**
 * Launcher for all 5 independent Muse Account Connectors
 */
const MuseAgentConnector = require('./MuseAgentConnector');

const museAccounts = [
  { userId: 2, name: 'Muse Agent 1 (Strategist)', role: 'Strategist', googleId: 'google_muse_001' },
  { userId: 3, name: 'Muse Agent 2 (Developer)', role: 'Developer', googleId: 'google_muse_002' },
  { userId: 4, name: 'Muse Agent 3 (Designer)', role: 'Designer', googleId: 'google_muse_003' },
  { userId: 5, name: 'Muse Agent 4 (Analyst)', role: 'Analyst', googleId: 'google_muse_004' },
  { userId: 6, name: 'Muse Agent 5 (Reviewer)', role: 'Reviewer', googleId: 'google_muse_005' }
];

console.log("==================================================");
console.log(" 🤖 Launching 5 Independent Muse Account Connectors");
console.log("==================================================");

museAccounts.forEach((accConfig, index) => {
  // Stagger startup slightly for clean log output
  setTimeout(() => {
    const connector = new MuseAgentConnector(accConfig);
    connector.start();
  }, index * 500);
});
