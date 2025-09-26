<?php
$host = "localhost";
$user = "root";  // Default XAMPP username

$pass = "juan1234";      // Leave empty if no password

// $pass = "argonza@@@";      // Leave empty if no password; password:cvsuOJT@2025 
// >>>>>>> 072030f35afea24f2a68512d08169494f69a3d1e
$db = "mathipms"; // Updated database name

$conn = mysqli_connect($host, $user, $pass, $db);
date_default_timezone_set('Asia/Manila');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
