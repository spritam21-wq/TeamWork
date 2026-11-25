<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = Database::getConnection();

// Auto-migrate user names to Rumi, Milo, Azar, Faiz, Bobby if old names exist
try {
    $pdo->exec("UPDATE users SET name = 'Rumi' WHERE id = 2");
    $pdo->exec("UPDATE users SET name = 'Milo' WHERE id = 3");
    $pdo->exec("UPDATE users SET name = 'Azar' WHERE id = 4");
    $pdo->exec("UPDATE users SET name = 'Faiz' WHERE id = 5");
    $pdo->exec("UPDATE users SET name = 'Bobby' WHERE id = 6");
} catch (Exception $e) {
    // Migration check silent fallback
}

switch ($action) {
    case 'get_users':
        $stmt = $pdo->query("SELECT u.id, u.google_id, u.email, u.name, u.avatar_url, u.role, u.status, c.status AS connector_status, c.last_ping FROM users u LEFT JOIN connectors c ON u.id = c.user_id ORDER BY u.id ASC");
        echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
        break;

    case 'get_messages':
        $roomId = (int)($_GET['room_id'] ?? 1);
        $sinceId = (int)($_GET['since_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);

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
        break;

    case 'send_message':
        $input = json_decode(file_get_contents('php://input'), true);
        $senderId = (int)($input['sender_id'] ?? 0);
        $roomId = (int)($input['room_id'] ?? 1);
        $content = trim($input['content'] ?? '');
        $msgType = $input['message_type'] ?? 'text';

        if (!$senderId || empty($content)) {
            echo json_encode(['success' => false, 'error' => 'Missing sender_id or content']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO messages (room_id, sender_id, content, message_type) VALUES (:room_id, :sender_id, :content, :message_type)");
        $stmt->execute([
            ':room_id' => $roomId,
            ':sender_id' => $senderId,
            ':content' => $content,
            ':message_type' => $msgType
        ]);
        $messageId = $pdo->lastInsertId();

        // Retrieve full created message
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
        break;

    case 'update_status':
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($input['user_id'] ?? 0);
        $status = $input['status'] ?? 'offline';

        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        }
        break;

    case 'admin_control':
        $input = json_decode(file_get_contents('php://input'), true);
        $targetUserId = (int)($input['target_user_id'] ?? 0);
        $connectorStatus = $input['connector_status'] ?? null;
        $clearHistory = $input['clear_history'] ?? false;

        if ($clearHistory) {
            $pdo->exec("DELETE FROM messages WHERE room_id = 1");
            echo json_encode(['success' => true, 'message' => 'Room history cleared']);
            exit();
        }

        if ($targetUserId && $connectorStatus) {
            $stmt = $pdo->prepare("UPDATE connectors SET status = :status WHERE user_id = :user_id");
            $stmt->execute([':status' => $connectorStatus, ':user_id' => $targetUserId]);
            echo json_encode(['success' => true, 'message' => "Connector status updated to {$connectorStatus}"]);
            exit();
        }

        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;

    default:
        echo json_encode(['success' => true, 'service' => 'TeamWork AI Chat API Ready']);
        break;
}
