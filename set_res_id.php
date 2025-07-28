<?php
session_start();
if (isset($_POST['res_id'])) {
    $_SESSION['res_id'] = intval($_POST['res_id']);
    echo "res_id set successfully"; // Optional success message
} else {
    echo "res_id not received";
}
?>