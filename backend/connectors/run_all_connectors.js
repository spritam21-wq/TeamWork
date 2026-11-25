/**
 * Launcher for the 5 independent Muse Account Connectors:
 * 1. Rumi
 * 2. Milo
 * 3. Azar
 * 4. Faiz
 * 5. Bobby
 */
const MuseAgentConnector = require('./MuseAgentConnector');

const museAccounts = [
  { userId: 2, name: 'Rumi', role: 'Strategist', googleId: 'google_muse_rumi' },
  { userId: 3, name: 'Milo', role: 'Developer', googleId: 'google_muse_milo' },
  { userId: 4, name: 'Azar', role: 'Designer', googleId: 'google_muse_azar' },
  { userId: 5, name: 'Faiz', role: 'Analyst', googleId: 'google_muse_faiz' },
  { userId: 6, name: 'Bobby', role: 'Reviewer', googleId: 'google_muse_bobby' }
];

console.log("==================================================");
console.log(" 🤖 Launching 5 Muse Account Connectors:");
console.log("    - Rumi, Milo, Azar, Faiz, Bobby");
console.log("==================================================");

museAccounts.forEach((accConfig, index) => {
  setTimeout(() => {
    const connector = new MuseAgentConnector(accConfig);
    connector.start();
  }, index * 500);
});
