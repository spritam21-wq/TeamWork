module.exports = {
  apps: [
    {
      name: 'teamwork-websocket',
      script: 'backend/websocket_server.php',
      interpreter: 'php',
      autorestart: true,
      watch: false,
      max_memory_restart: '200M',
      env: {
        WS_PORT: 8080,
        WS_HOST: '0.0.0.0'
      }
    },
    {
      name: 'teamwork-muse-connectors',
      script: 'backend/connectors/run_all_connectors.js',
      interpreter: 'node',
      autorestart: true,
      watch: false,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: 'production'
      }
    }
  ]
};
