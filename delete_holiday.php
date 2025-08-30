<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['holiday_date'])) {
    $holiday_date = $_POST['holiday_date'];

    $stmt = $conn->prepare("DELETE FROM multiaxis_payroll_system.holidays WHERE holiday_date = ?");
    $stmt->bind_param("s", $holiday_date);

    if ($stmt->execute()) {
        header("Location: add_holiday.php");
        exit();
    } else {
        echo "❌ Error deleting holiday: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "⚠️ Invalid request.";
}
?>
