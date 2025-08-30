<?php 
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");  // Redirect to login page if not logged in
    exit();
}

$username = $_SESSION['user'];  // Get the logged-in user's username
$role = $_SESSION['role'];  // Get the user's role (assuming you're storing it in session)
$current_page = basename($_SERVER['PHP_SELF']);  // Get the current page name

// Database connection
$conn = new mysqli("localhost", "root", "", "payroll_management_system");  // Use your DB credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);  // Handle connection errors
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeName = $_POST['employee_name'];
    $date = $_POST['date'];
    $netTotal = $_POST['net_total'];

    // Prepare SQL to insert data into employee_summary table
    $stmt = $conn->prepare("INSERT INTO employee_summary (employee_name, date, net_total) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $employeeName, $date, $netTotal);  // "ssd" means string, string, decimal

    // Execute the query
    if ($stmt->execute()) {
        echo "<p>Summary saved successfully!</p>";
    } else {
        echo "<p>Error: " . $stmt->error . "</p>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    header("Location: reports.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summary View</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5 p-4 bg-white rounded shadow">
        <h2 class="mb-4">Employee Summary</h2>
        <p><strong>Employee Name:</strong> <?= htmlspecialchars($employeeName) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($date))) ?></p>
        <p><strong>Subtotal Daily Wage - Total Deductions:</strong> ₱<?= number_format($netTotal, 2) ?></p>
        <a href="reports.php" class="btn btn-primary mt-3">⬅ Back to Deductions</a>
    </div>
</body>
</html>
