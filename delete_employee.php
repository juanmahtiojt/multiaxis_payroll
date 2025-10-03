<?php
session_start();
include __DIR__ . "/config.php";
include_once "functions.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $employee_id = $_GET['id'];

    // Optional: fetch employee name before delete for logging
    $emp_name = "Unknown";
    $result = $conn->query("SELECT name FROM employees WHERE employee_id = '$employee_id'");
    if ($row = $result->fetch_assoc()) {
        $emp_name = $row['name'];
    }

    $delete_query = "DELETE FROM employees WHERE employee_id = '$employee_id'";
    if (mysqli_query($conn, $delete_query)) {
        log_activity($conn, $_SESSION['user'], "Deleted employee: $emp_name (ID: $employee_id)", "add_user.php");

        // ✅ Redirect back to refresh list
        header("Location: add_user.php?status=deleted");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid employee ID.";
    exit();
}
