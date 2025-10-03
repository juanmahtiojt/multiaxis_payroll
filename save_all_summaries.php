<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

// DB connection
$conn = new mysqli("localhost", "root", "", "payroll_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['summaries'])) {
    foreach ($_POST['summaries'] as $summary) {
        $employeeName = $summary['employee_name'];
        $date = $summary['date'];
        $netTotal = $summary['net_total'];

        $stmt = $conn->prepare("INSERT INTO employee_summary (employee_name, date, net_total) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $employeeName, $date, $netTotal);

        if (!$stmt->execute()) {
            echo "<p>Error: " . $stmt->error . "</p>";
        }
    }

    echo "<p>All summaries saved successfully!</p>";
}

$conn->close();
?>
