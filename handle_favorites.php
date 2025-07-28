<?php
session_start();
require 'connection/connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$u_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);

if ($type !== 'restaurant' && $type !== 'dish') {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit;
}

$table = $type === 'restaurant' ? 'user_favorite_restaurants' : 'user_favorite_dishes';
$id_column = $type === 'restaurant' ? 'rs_id' : 'd_id';

mysqli_begin_transaction($db);

try {
    $check_sql = "SELECT 1 FROM $table WHERE u_id = ? AND $id_column = ? FOR UPDATE";
    $check_stmt = mysqli_prepare($db, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'ii', $u_id, $id);
    mysqli_stmt_execute($check_stmt);
    $exists = mysqli_stmt_fetch($check_stmt) ? true : false;
    mysqli_stmt_close($check_stmt);

    if ($action === 'add') {
        if ($exists) {
            throw new Exception('Already favorited');
        }
        $stmt = mysqli_prepare($db, "INSERT INTO $table (u_id, $id_column) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $u_id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $response = ['success' => true, 'is_favorite' => true];
    } elseif ($action === 'remove') {
        if (!$exists) {
            throw new Exception('Not favorited');
        }
        $stmt = mysqli_prepare($db, "DELETE FROM $table WHERE u_id = ? AND $id_column = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $u_id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $response = ['success' => true, 'is_favorite' => false];
    } else {
        throw new Exception('Invalid action');
    }

    mysqli_commit($db);
    echo json_encode($response);
} catch (Exception $e) {
    mysqli_rollback($db);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

mysqli_close($db);
?>