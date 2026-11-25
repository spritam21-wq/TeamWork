<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = Database::getConnection();

$dataPath = __DIR__ . '/data';
if (!file_exists($dataPath)) {
    @mkdir($dataPath, 0777, true);
}

$usersFile = $dataPath . '/users.json';
$messagesFile = $dataPath . '/messages.json';

// Helper functions for JSON storage engine
function getJsonData($file, $default = []) {
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
    }
    return $default;
}

function saveJsonData($file, $data) {
    @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Default initial users
$defaultUsers = [
    ["id" => 1, "google_id" => "google_admin_001", "email" => "admin@lingobotix.space", "name" => "Admin (Me)", "avatar_url" => "", "role" => "admin", "status" => "online", "connector_status" => "active"],
    ["id" => 2, "google_id" => "google_muse_rumi", "email" => "rumi@lingobotix.space", "name" => "Rumi", "avatar_url" => "", "role" => "muse_agent", "status" => "online", "connector_status" => "active"],
    ["id" => 3, "google_id" => "google_muse_milo", "email" => "milo@lingobotix.space", "name" => "Milo", "avatar_url" => "", "role" => "muse_agent", "status" => "online", "connector_status" => "active"],
    ["id" => 4, "google_id" => "google_muse_azar", "email" => "azar@lingobotix.space", "name" => "Azar", "avatar_url" => "", "role" => "muse_agent", "status" => "online", "connector_status" => "active"],
    ["id" => 5, "google_id" => "google_muse_faiz", "email" => "faiz@lingobotix.space", "name" => "Faiz", "avatar_url" => "", "role" => "muse_agent", "status" => "online", "connector_status" => "active"],
    ["id" => 6, "google_id" => "google_muse_bobby", "email" => "bobby@lingobotix.space", "name" => "Bobby", "avatar_url" => "", "role" => "muse_agent", "status" => "online", "connector_status" => "active"]
];

if (!file_exists($usersFile)) {
    saveJsonData($usersFile, $defaultUsers);
}

// Auto-migrate database user names if MySQL connection is active
if ($pdo) {
    try {
        $pdo->exec("UPDATE users SET name = 'Rumi' WHERE id = 2");
        $pdo->exec("UPDATE users SET name = 'Milo' WHERE id = 3");
        $pdo->exec("UPDATE users SET name = 'Azar' WHERE id = 4");
        $pdo->exec("UPDATE users SET name = 'Faiz' WHERE id = 5");
        $pdo->exec("UPDATE users SET name = 'Bobby' WHERE id = 6");
    } catch (Exception $e) {}
}

switch ($action) {
    case 'get_users':
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT u.id, u.google_id, u.email, u.name, u.avatar_url, u.role, u.status, c.status AS connector_status, c.last_ping FROM users u LEFT JOIN connectors c ON u.id = c.user_id ORDER BY u.id ASC");
                echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
                exit();
            } catch (Exception $e) {}
        }
        $users = getJsonData($usersFile, $defaultUsers);
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'get_messages':
        $roomId = (int)($_GET['room_id'] ?? 1);
        $sinceId = (int)($_GET['since_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);

        if ($pdo) {
            try {
                if ($sinceId > 0) {
                    $stmt = $pdo->prepare("
                        SELECT m.id, m.room_id, m.sender_id, m.content, m.message_type, m.created_at,
                               u.name AS sender_name, u.avatar_url AS sender_avatar, u.role AS sender_role
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.room_id = :room_id AND m.id > :since_id
                        ORDER BY m.id ASC
                    ");
                    $stmt->execute([':room_id' => $roomId, ':since_id' => $sinceId]);
                } else {
                    $stmt = $pdo->prepare("
                        SELECT m.id, m.room_id, m.sender_id, m.content, m.message_type, m.created_at,
                               u.name AS sender_name, u.avatar_url AS sender_avatar, u.role AS sender_role
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.room_id = :room_id
                        ORDER BY m.id ASC
                        LIMIT :limit
                    ");
                    $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                }
                echo json_encode(['success' => true, 'messages' => $stmt->fetchAll()]);
                exit();
            } catch (Exception $e) {}
        }

        // Fallback to JSON messages file
        $allMessages = getJsonData($messagesFile, []);
        $filtered = [];
        foreach ($allMessages as $msg) {
            if ($sinceId > 0) {
                if ((int)$msg['id'] > $sinceId) {
                    $filtered[] = $msg;
                }
            } else {
                $filtered[] = $msg;
            }
        }
        echo json_encode(['success' => true, 'messages' => $filtered]);
        break;

    case 'send_message':
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (!$input && isset($_POST['content'])) {
            $input = $_POST;
        }

        $senderId = (int)($input['sender_id'] ?? 1);
        $roomId = (int)($input['room_id'] ?? 1);
        $content = trim($input['content'] ?? '');
        $msgType = $input['message_type'] ?? 'text';

        if (empty($content)) {
            echo json_encode(['success' => false, 'error' => 'Missing content']);
            exit();
        }

        // Lookup sender user info
        $users = getJsonData($usersFile, $defaultUsers);
        $senderUser = null;
        foreach ($users as $u) {
            if ((int)$u['id'] === $senderId) {
                $senderUser = $u;
                break;
            }
        }
        $senderName = $senderUser ? $senderUser['name'] : ($senderId === 1 ? 'Admin (Me)' : 'Team Member');
        $senderRole = $senderUser ? $senderUser['role'] : 'muse_agent';

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (room_id, sender_id, content, message_type) VALUES (:room_id, :sender_id, :content, :message_type)");
                $stmt->execute([
                    ':room_id' => $roomId,
                    ':sender_id' => $senderId,
                    ':content' => $content,
                    ':message_type' => $msgType
                ]);
                $messageId = $pdo->lastInsertId();

                $stmtFetch = $pdo->prepare("
                    SELECT m.id, m.room_id, m.sender_id, m.content, m.message_type, m.created_at,
                           u.name AS sender_name, u.avatar_url AS sender_avatar, u.role AS sender_role
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.id = :id
                ");
                $stmtFetch->execute([':id' => $messageId]);
                $msgData = $stmtFetch->fetch();

                echo json_encode(['success' => true, 'message' => $msgData]);
                exit();
            } catch (Exception $e) {}
        }

        // Save to JSON messages file
        $allMessages = getJsonData($messagesFile, []);
        $newId = count($allMessages) > 0 ? (int)end($allMessages)['id'] + 1 : 1;
        $msgData = [
            'id' => $newId,
            'room_id' => $roomId,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'sender_role' => $senderRole,
            'content' => $content,
            'message_type' => $msgType,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $allMessages[] = $msgData;
        saveJsonData($messagesFile, $allMessages);

        echo json_encode(['success' => true, 'message' => $msgData]);
        break;

    case 'admin_control':
        $input = json_decode(file_get_contents('php://input'), true);
        $clearHistory = $input['clear_history'] ?? false;

        if ($clearHistory) {
            if ($pdo) {
                try { $pdo->exec("DELETE FROM messages WHERE room_id = 1"); } catch (Exception $e) {}
            }
            saveJsonData($messagesFile, []);
            echo json_encode(['success' => true, 'message' => 'Room history cleared']);
            exit();
        }

        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => true, 'service' => 'TeamWork AI Chat API Ready']);
        break;
}
