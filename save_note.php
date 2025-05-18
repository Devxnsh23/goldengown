<?php
include 'config.php';
$conn = mysqli_connect("localhost", "root", "", "goldengown");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orderId = $_POST['orderId'];
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    $query = "UPDATE orders SET notes = '$note' WHERE orderId = '$orderId'";
    mysqli_query($conn, $query);
}

header("Location: dashboard_d.php");
exit;
?>
