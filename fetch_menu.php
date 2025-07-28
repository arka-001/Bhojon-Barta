<?php
include("connection/connect.php");
session_start();

header('Content-Type: application/json');

if (isset($_POST['res_id'])) {
    $res_id = mysqli_real_escape_string($db, $_POST['res_id']);
    $response = array();

    // Fetch restaurant details
    $res_query = mysqli_query($db, "SELECT * FROM restaurant WHERE rs_id='$res_id'");
    if ($res_row = mysqli_fetch_array($res_query)) {
        $response['restaurant'] = array(
            'title' => $res_row['title'],
            'address' => $res_row['address'],
            'image' => $res_row['image']
        );

        // Fetch dishes for the restaurant
        $stmt = $db->prepare("SELECT * FROM dishes WHERE rs_id=?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $products = $stmt->get_result();
        $dishes = array();

        while ($product = $products->fetch_assoc()) {
            $dishes[] = array(
                'd_id' => $product['d_id'],
                'title' => $product['title'],
                'slogan' => $product['slogan'],
                'price' => $product['price'],
                'img' => $product['img']
            );
        }

        $response['dishes'] = $dishes;
        $response['status'] = 'success';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Restaurant not found.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>