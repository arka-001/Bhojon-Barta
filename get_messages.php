<?php
session_start();
require_once 'connection/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['adm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$conversation_id = $_GET['conversation_id'] ?? null;
if (!$conversation_id) {
    echo json_encode(['status' => 'error', 'message' => 'Conversation ID required']);
    exit;
}

// Fetch messages with sender details
$query = "
    SELECT m.message_id, m.conversation_id, m.sender_id, m.sender_type, m.message_text, m.timestamp,
           CASE 
               WHEN m.sender_type = 'user' THEN u.username
               WHEN m.sender_type = 'admin' THEN a.username
           END as sender_name
    FROM chat_messages m
    LEFT JOIN users u ON m.sender_type = 'user' AND m.sender_id = u.u_id
    LEFT JOIN admin a ON m.sender_type = 'admin' AND m.sender_id = a.adm_id
    WHERE m.conversation_id = '$conversation_id'
    ORDER BY m.timestamp ASC
";
$result = mysqli_query($db, $query);
$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = $row;
}

// Mark messages as read
$reader_type = isset($_SESSION['user_id']) ? 'user' : 'admin';
$unread_field = $reader_type === 'user' ? 'user_unread_count' : 'admin_unread_count';
mysqli_query($db, "UPDATE chat_conversations SET $unread_field = 0 WHERE conversation_id = '$conversation_id'");

echo json_encode(['status' => 'success', 'messages' => $messages]);
?>