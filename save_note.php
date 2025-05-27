<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orderId = $_POST['orderId'];
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    $query = "UPDATE orders SET notes = '$note' WHERE orderId = '$orderId'";
    mysqli_query($conn, $query);
}

header("Location: dashboard.php");
exit;
?>