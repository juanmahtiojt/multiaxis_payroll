<?php
// save_manual_attendance.php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_no        = $_POST['id_no'] ?? '';
    $department   = $_POST['department'] ?? '';
    $name         = $_POST['name'] ?? '';
    $pay_schedule = $_POST['pay_schedule'] ?? '';
    $start_date = $_POST['time_in'] ?? null;   
    $end_date   = $_POST['time_out'] ?? null;  
    $work_days_count = (int)($_POST['work_days_count'] ?? 0);
    // Safely sum OT and UT
    $ot_hours = isset($_POST['ot_hours']) ? array_sum($_POST['ot_hours']) : 0;
    $ut_hours = isset($_POST['ut_hours']) ? array_sum($_POST['ut_hours']) : 0;

    if ($id_no && $name && $work_days_count > 0) {

        $check = $conn->prepare("SELECT id FROM manual_attendance WHERE id_no = ?");
        $check->bind_param("s", $id_no);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $check->close();
            $conn->close();
            header("Location: manual.php?msg=duplicate");
            exit();
        }
        $check->close();

        $stmt = $conn->prepare("
            INSERT INTO manual_attendance 
            (id_no, department, name, pay_schedule, start_date, end_date, work_days_count, ot_hours, ut_hours) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssiii",
            $id_no,
            $department,
            $name,
            $pay_schedule,
            $start_date,
            $end_date,
            $work_days_count,
            $ot_hours,
            $ut_hours
        );

        $stmt->execute();
        $stmt->close();
        $conn->close();

        header("Location: manual.php?msg=success");
        exit();
    } else {
        header("Location: manual.php?msg=error");
        exit();
    }
} else {
    header("Location: manual.php");
    exit();
}
