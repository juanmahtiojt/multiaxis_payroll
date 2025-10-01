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

/**
 * Get employee rate details
 * @param mysqli $conn
 * @param string $id_no
 * @return array|null
 */
function getEmployeeRate($conn, $id_no) {
    $sql = "SELECT daily_rate, pay_schedule 
            FROM daily_rate 
            WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $rate = $result->fetch_assoc();
    $stmt->close();

    return $rate ?: null; // return null if not found
}

/**
 * Get employee deductions and leave info
 * @param mysqli $conn
 * @param string $id_no
 * @return array|null
 */
function getEmployeeDeductions($conn, $id_no) {
    $sql = "SELECT 
                id_no, sss_no, pagibig_no, tin_no, philhealth_no, 
                sss_premium, sss_loan, pagibig_premium, pagibig_loan, 
                philhealth, cash_advance, leave_with_pay, leave_without_pay, available_leave 
            FROM employees 
            WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $deductions = $result->fetch_assoc();
    $stmt->close();

    return $deductions ?: null; // return null if not found
}

/**
 * Get holiday + Sunday (rest day) multipliers
 *
 * @param string $date Y-m-d date
 * @param bool   $isSunday true if the date is a Sunday / rest day
 * @param array  $holidays list of holidays from DB (indexed by date)
 * @param array  $sundayRates config for regular Sunday multipliers
 *
 * @return array ['regular' => float, 'overtime' => float]
 */
function getHolidayMultipliers($date, $isSunday, $holidayInfo, $postedMultipliers) {
    $multipliers = [
        "regular"   => (float)($postedMultipliers['regular_rate'] ?? 1),
        "overtime"  => (float)($postedMultipliers['overtime_rate'] ?? 1.25),
    ];

    // Add holiday logic
    if (!empty($holidayInfo['holiday_type'])) {
        if ($holidayInfo['holiday_type'] === "Regular") {
            // Regular Holiday rules
            $multipliers["regular"]   = (float)($postedMultipliers['restdayholiday_regular'] ?? 2);
            $multipliers["overtime"]  = (float)($postedMultipliers['restdayholiday_overtime'] ?? 2.6);
        } elseif ($holidayInfo['holiday_type'] === "Special") {
            // Special Holiday rules
            $multipliers["regular"]   = (float)($postedMultipliers['restdayholiday_special'] ?? 1.3);
            $multipliers["overtime"]  = (float)($postedMultipliers['restdayspecialholiday_overtime'] ?? 1.69);
        }
    }

    // Sunday adjustment
    if ($isSunday && empty($holidayInfo['holiday_type'])) {
        $multipliers["regular"]  = (float)($postedMultipliers['regular_rate'] ?? 1.3);
        $multipliers["overtime"] = (float)($postedMultipliers['overtime_rate'] ?? 1.69);
    }

    return $multipliers;
}
function getRates($conn) {
    // Fetch global rates
    $rates = [];
    $sql = "SELECT * FROM sunday_rates LIMIT 1"; // adjust table/columns
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $rates = [
            'regular_multiplier'  => $row['regular_multiplier'] ?? 1.3,
            'overtime_multiplier' => $row['overtime_multiplier'] ?? 1.69,
            // add other global rates here
        ];
    }

    // Fetch holidays
    $holidays = [];
    $sql2 = "SELECT holiday_date, holiday_type FROM holidays";
    $res2 = $conn->query($sql2);
    while ($h = $res2->fetch_assoc()) {
        $holidays[$h['holiday_date']] = $h['holiday_type'];
    }

    return [
        'rates' => $rates,
        'holidays' => $holidays
    ];
}



?>