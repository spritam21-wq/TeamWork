<?php
// TeamWork Lightweight Standalone PHP WebSocket Server
require_once __DIR__ . '/db.php';

$host = WS_HOST;
$port = WS_PORT;

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server);

echo "===========================================\n";
echo " 🚀 TeamWork WebSocket Server Started\n";
echo " Listening on ws://{$host}:{$port}\n";
echo "===========================================\n";

$clients = [];
$usersMap = []; // socketId => userId

while (true) {
    $read = array_merge([$server], array_column($clients, 'socket'));
    $write = null;
    $except = null;

    if (socket_select($read, $write, $except, 0, 10000) < 1) {
        continue;
    }

    // New connection
    if (in_array($server, $read)) {
        $newSocket = socket_accept($server);
        $header = socket_read($newSocket, 1024);
        performHandshake($header, $newSocket, $host, $port);

        $socketId = (int)$newSocket;
        $clients[$socketId] = [
            'socket' => $newSocket,
            'user_id' => null,
            'handshaked' => true
        ];

        $read = array_diff($read, [$server]);
    }

    // Read incoming client frames
    foreach ($read as $socket) {
        $socketId = (int)$socket;
        $bytes = @socket_recv($socket, $buffer, 4096, 0);

        if ($bytes === false || $bytes === 0) {
            // Client disconnected
            handleDisconnect($socketId, $clients, $usersMap);
            continue;
        }

        $decodedData = unmask($buffer);
        if (!$decodedData) continue;

        $msg = json_decode($decodedData, true);
        if (!$msg || !isset($msg['type'])) continue;

        handleClientMessage($socketId, $msg, $clients, $usersMap);
    }
}

function handleClientMessage($socketId, $msg, &$clients, &$usersMap) {
    $pdo = Database::getConnection();

    switch ($msg['type']) {
        case 'authenticate':
            $userId = (int)($msg['user_id'] ?? 0);
            if ($userId) {
                $clients[$socketId]['user_id'] = $userId;
                $usersMap[$socketId] = $userId;

                // Update database user status to online
                $stmt = $pdo->prepare("UPDATE users SET status = 'online' WHERE id = :id");
                $stmt->execute([':id' => $userId]);

                // Broadcast online presence
                broadcastJSON($clients, [
                    'type' => 'user_status_changed',
                    'user_id' => $userId,
                    'status' => 'online'
                ]);

                echo "👤 User ID #{$userId} authenticated on socket #{$socketId}\n";
            }
            break;

        case 'chat_message':
            $senderId = $clients[$socketId]['user_id'] ?? (int)($msg['sender_id'] ?? 0);
            $roomId = (int)($msg['room_id'] ?? 1);
            $content = trim($msg['content'] ?? '');
            $msgType = $msg['message_type'] ?? 'text';

            if ($senderId && !empty($content)) {
                // Insert message into DB
                $stmt = $pdo->prepare("INSERT INTO messages (room_id, sender_id, content, message_type) VALUES (:room_id, :sender_id, :content, :message_type)");
                $stmt->execute([
                    ':room_id' => $roomId,
                    ':sender_id' => $senderId,
                    ':content' => $content,
                    ':message_type' => $msgType
                ]);
                $messageId = $pdo->lastInsertId();

                // Retrieve inserted message with sender info
                $stmtFetch = $pdo->prepare("
                    SELECT m.id, m.room_id, m.sender_id, m.content, m.message_type, m.created_at,
                           u.name AS sender_name, u.avatar_url AS sender_avatar, u.role AS sender_role
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.id = :id
                ");
                $stmtFetch->execute([':id' => $messageId]);
                $fullMessage = $stmtFetch->fetch();

                // Broadcast to all connected clients
                broadcastJSON($clients, [
                    'type' => 'new_message',
                    'message' => $fullMessage
                ]);

                echo "💬 Message #{$messageId} sent by User #{$senderId}: {$content}\n";
            }
            break;

        case 'typing':
            $userId = $clients[$socketId]['user_id'] ?? (int)($msg['user_id'] ?? 0);
            $isTyping = (bool)($msg['is_typing'] ?? false);

            broadcastJSON($clients, [
                'type' => 'user_typing',
                'user_id' => $userId,
                'is_typing' => $isTyping
            ], $socketId); // Exclude sender
            break;
    }
}

function handleDisconnect($socketId, &$clients, &$usersMap) {
    if (isset($clients[$socketId])) {
        $userId = $clients[$socketId]['user_id'];
        @socket_close($clients[$socketId]['socket']);
        unset($clients[$socketId]);
        unset($usersMap[$socketId]);

        if ($userId) {
            // Check if user has no remaining active sockets
            if (!in_array($userId, $usersMap)) {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("UPDATE users SET status = 'offline' WHERE id = :id");
                $stmt->execute([':id' => $userId]);

                broadcastJSON($clients, [
                    'type' => 'user_status_changed',
                    'user_id' => $userId,
                    'status' => 'offline'
                ]);
            }
        }
        echo "🔌 Client #{$socketId} disconnected\n";
    }
}

function broadcastJSON(&$clients, $data, $excludeSocketId = null) {
    $encoded = mask(json_encode($data));
    foreach ($clients as $sId => $client) {
        if ($excludeSocketId !== null && $sId === $excludeSocketId) continue;
        @socket_write($client['socket'], $encoded, strlen($encoded));
    }
}

function performHandshake($received_header, $client_conn, $host, $port) {
    $headers = array();
    $lines = preg_split("/\r\n/", $received_header);
    foreach ($lines as $line) {
        $line = chop($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $secKey = $headers['Sec-WebSocket-Key'] ?? '';
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "WebSocket-Origin: {$host}\r\n" .
               "WebSocket-Location: ws://{$host}:{$port}\r\n" .
               "Sec-WebSocket-Accept:{$secAccept}\r\n\r\n";
    socket_write($client_conn, $upgrade, strlen($upgrade));
}

function unmask($text) {
    $length = ord($text[1]) & 127;
    if ($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif ($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

function mask($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCN', $b1, 127, $length);
    }
    return $header . $text;
}
