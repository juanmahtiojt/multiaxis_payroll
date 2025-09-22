<?php
$host = "localhost";
$user = "root";  // Default XAMPP username
$pass = "argonza@@@";      // Leave empty if no password
$db = "mathipms"; // Updated database name

$conn = mysqli_connect($host, $user, $pass, $db);
date_default_timezone_set('Asia/Manila');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
