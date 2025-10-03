<?php
$host = "localhost";
$user = "root";  // Default XAMPP username
// $pass = "cvsuOJT@2025";
$pass = "juan1234";
$db = "mathipms"; 

$conn = mysqli_connect($host, $user, $pass, $db);
date_default_timezone_set('Asia/Manila');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>