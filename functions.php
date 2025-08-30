<?php
// functions.php

// Include database connection
include_once "config.php";

/**
 * Log a user activity to the activity_logs table
 *
 * @param mysqli $conn      The database connection
 * @param string $username  Username of the user
 * @param string $activity  Description of the activity
 * @param string $page      Page where the activity occurred
 */
function log_activity($conn, $username, $activity, $page) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $username, $activity, $page, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Format a date string into a readable format
 *
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date("F j, Y", strtotime($date)); // e.g., August 5, 2025
}

/**
 * Get employee name by ID (optional helper)
 *
 * @param mysqli $conn
 * @param string $id_no
 * @return string
 */
function getEmployeeName($conn, $id_no) {
    $stmt = $conn->prepare("SELECT name FROM employees WHERE id_no = ?");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = "Unknown";

    if ($row = $result->fetch_assoc()) {
        $name = $row['name'];
    }

    $stmt->close();
    return $name;
}
?>