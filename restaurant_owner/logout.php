<?php
session_start();
session_destroy();
header("Location: restaurant_owner_login.php");
exit();
?>