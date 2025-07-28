<?php
// C:\xampp\htdocs\OnlineFood-PHP\OnlineFood-PHP\get_conversation.php
session_start();
require_once 'connection/connect.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$query = mysqli_query($db, "SELECT conversation_id, user_unread_count FROM chat_conversations WHERE user_id = '$user_id' LIMIT 1");

if (!$query) {
    echo json_encode(['status' => 'error', 'message' => 'Database query failed: ' . mysqli_error($db)]);
    exit;
}

if ($row = mysqli_fetch_assoc($query)) {
    echo json_encode([
        'status' => 'success',
        'conversation_id' => (int)$row['conversation_id'],
        'user_unread_count' => (int)($row['user_unread_count'] ?? 0)
    ]);
} else {
    $insert = mysqli_query($db, "INSERT INTO chat_conversations (user_id, created_at) VALUES ('$user_id', NOW())");
    if ($insert) {
        $conversation_id = mysqli_insert_id($db);
        echo json_encode([
            'status' => 'success',
            'conversation_id' => $conversation_id,
            'user_unread_count' => 0
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create conversation: ' . mysqli_error($db)]);
    }
}
?>