<?php
require_once 'connection/connect.php'; // Adjust path if needed

if (!is_db_connected()) {
    json_response(['status' => 'error', 'message' => 'Database connection failed'], 500);
}

// Determine who is marking as read
$reader_id = null;
$reader_type = null; // 'user' or 'admin'

if (!empty($_SESSION['user_id'])) {
    $reader_id = (int)$_SESSION['user_id'];
    $reader_type = 'user';
} elseif (!empty($_SESSION['adm_id'])) {
    $reader_id = (int)$_SESSION['adm_id'];
    $reader_type = 'admin';
} else {
    json_response(['status' => 'error', 'message' => 'Not authenticated'], 403);
}

$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
// Optional: Get reader type from JS if needed, but session is more secure
// $reader_type_from_post = $_POST['reader_type'] ?? null;
// if ($reader_type_from_post && ($reader_type_from_post === 'user' || $reader_type_from_post === 'admin')) {
//    $reader_type = $reader_type_from_post;
// }

if ($conversation_id === null || $reader_type === null) {
    json_response(['status' => 'error', 'message' => 'Missing required parameters'], 400);
}

// Determine which unread count field to reset
$unread_field_to_reset = ($reader_type === 'user') ? 'user_unread_count' : 'admin_unread_count';

// --- Update Database ---
$sql_update = "UPDATE chat_conversations SET $unread_field_to_reset = 0 WHERE conversation_id = ?";

// Add authorization check: Make sure the user/admin belongs to this conversation
if ($reader_type === 'user') {
    $sql_update .= " AND user_id = ?";
} elseif ($reader_type === 'admin') {
    // Admin can mark read if assigned OR if it's unassigned (they are viewing it)
     $sql_update .= " AND (admin_id = ? OR admin_id IS NULL)";
}

$stmt_update = mysqli_prepare($db, $sql_update);
 if(!$stmt_update) { json_response(['status' => 'error', 'message' => 'DB Error (mark read)'], 500); }

mysqli_stmt_bind_param($stmt_update, "ii", $conversation_id, $reader_id);

if (mysqli_stmt_execute($stmt_update)) {
    // Check if any rows were actually affected
    if (mysqli_stmt_affected_rows($stmt_update) > 0) {
        json_response(['status' => 'success', 'message' => 'Marked as read']);
    } else {
         // This can happen if the count was already 0, or if the user/admin didn't have permission
         json_response(['status' => 'success', 'message' => 'No update needed or not authorized']);
         // You might want different logic/logging if not authorized vs already 0
    }
} else {
    error_log("mark_read.php - Failed to execute update: " . mysqli_stmt_error($stmt_update));
    json_response(['status' => 'error', 'message' => 'Failed to mark as read'], 500);
}

mysqli_stmt_close($stmt_update);

?>