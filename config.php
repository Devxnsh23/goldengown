<?php
$servername = "localhost";    
$username = "root";           
$password = "";               
$dbname = "goldengown";       

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
