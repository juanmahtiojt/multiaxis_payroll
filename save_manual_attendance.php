<?php
// save_manual_attendance.php
include 'config.php'; // Make sure this connects properly ($conn)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_no        = $_POST['id_no'] ?? '';
    $department   = $_POST['department'] ?? '';
    $name         = $_POST['name'] ?? '';
    $pay_schedule = $_POST['pay_schedule'] ?? '';
    $work_days_count = (int)($_POST['work_days_count'] ?? 0);
    $ot_hours     = $_POST['ot_hours'] ?? 0;
    $ut_hours     = $_POST['ut_hours'] ?? 0;

    if ($id_no && $name && $work_days_count > 0) {

        // 🔎 Step 1: Check if ID number already exists
        $check = $conn->prepare("SELECT id FROM manual_attendance WHERE id_no = ?");
        $check->bind_param("s", $id_no);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Duplicate found 🚫
            $check->close();
            $conn->close();
            header("Location: manual.php?msg=duplicate");
            exit();
        }
        $check->close();

        // ✅ Step 2: If no duplicate, insert new record
        $stmt = $conn->prepare("
            INSERT INTO manual_attendance 
            (id_no, department, name, pay_schedule, work_days_count, ot_hours, ut_hours) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssiii",
            $id_no,
            $department,
            $name,
            $pay_schedule,
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
