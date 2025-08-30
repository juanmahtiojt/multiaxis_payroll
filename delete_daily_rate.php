<?php
session_start();
include __DIR__ . "/config.php";
include_once "functions.php";  // ✅ Include for log_activity()

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Validate the ID parameter
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_no = $_GET['id'];

    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM multiaxis_payroll_system.daily_rate WHERE id_no = ?");
    $stmt->bind_param("s", $id_no);

    if ($stmt->execute()) {
        // ✅ Log the activity
        log_activity($conn, $_SESSION['user'], "Deleted employee with ID: $id_no", "add_user.php");

        // Optional: Add a success message via session
        $_SESSION['message'] = "✅ Employee with ID $id_no has been deleted.";
    } else {
        $_SESSION['message'] = "❌ Error deleting employee: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['message'] = "⚠️ Invalid employee ID.";
}

// Redirect back to employee management page
header("Location: add_user.php");
exit();
