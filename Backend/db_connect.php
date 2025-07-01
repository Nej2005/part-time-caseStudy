<?php
$servername = "localhost"; 
$username = "root";
$password = "admin5002";    //change password kung kaninong laptop
$database = "ptprocessing";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Manila'); 
$conn->query("SET time_zone = '+08:00'");
?>