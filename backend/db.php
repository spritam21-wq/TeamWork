<?php
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Return JSON error response if connected via HTTP
                if (php_sapi_name() !== 'cli') {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database Connection Error: ' . $e->getMessage()]);
                    exit();
                } else {
                    die("Database Connection Error: " . $e->getMessage() . "\n");
                }
            }
        }
        return self::$instance;
    }
}
