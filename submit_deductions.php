<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // DB connection
    $conn = new mysqli("localhost", "root", "", "payroll_management_system");
    if ($conn->connect_error) {
        echo "<script>alert('Database connection failed.'); window.history.back();</script>";
        exit();
    }

    // Collect data from POST
    $employee_id = $_POST['employee_id']; // 🆕 New
    $employee_name = $_POST['employee_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $subtotal_daily_wage = $_POST['subtotal_daily_wage'];
    $subtotal_minus_deductions = $_POST['subtotal_minus_deductions'];

    // Check for duplicates using employee_id instead of just name
    $check_sql = "SELECT * FROM employee_deductions WHERE employee_id = ? AND start_date = ? AND end_date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sss", $employee_id, $start_date, $end_date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<script>alert('This payroll record already exists for this employee.'); window.history.back();</script>";
    } else {
        // Proceed to insert with employee_id
        $sql = "INSERT INTO employee_deductions (employee_id, employee_name, start_date, end_date, subtotal_daily_wage, subtotal_minus_deductions) 
                VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssss", $employee_id, $employee_name, $start_date, $end_date, $subtotal_daily_wage, $subtotal_minus_deductions);

            if ($stmt->execute()) {
                echo "<script>alert('Data saved successfully!'); window.location.href='reports.php';</script>";
            } else {
                echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
            }

            $stmt->close();
        } else {
            echo "<script>alert('Error preparing statement: " . $conn->error . "'); window.history.back();</script>";
        }
    }

    $check_stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}
?>
