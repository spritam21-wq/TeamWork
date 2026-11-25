<?php
// TeamWork Configuration File with Environment Variable Support

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'teamwork_chat');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'tw_super_secret_key_change_in_production_2026');
define('WS_PORT', (int)(getenv('WS_PORT') ?: 8080));
define('WS_HOST', getenv('WS_HOST') ?: '0.0.0.0');

// CORS configuration
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
