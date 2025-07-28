<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use Ratchet\Client\WebSocket;

$db_id = isset($_GET['db_id']) ? (int)$_GET['db_id'] : 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($db_id && $order_id) {
    // Connect to the WebSocket server
    \Ratchet\Client\connect('ws://localhost:8080')->then(function($conn) use ($db_id, $order_id) {
        $conn->send(json_encode([
            'type' => 'new_order',
            'db_id' => $db_id,
            'order_id' => $order_id
        ]));
        $conn->close();
    }, function ($e) {
        echo "Could not connect: {$e->getMessage()}\n";
    });
}

http_response_code(200);
?>