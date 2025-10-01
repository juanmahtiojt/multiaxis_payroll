<?php
$id = intval($_POST['id']);
$ot = $_POST['ot'] ?? [];
$ut = $_POST['ut'] ?? [];

$data = [];
foreach ($ot as $date => $hours) {
    $data[$date]['ot'] = (int)$hours;
}
foreach ($ut as $date => $hours) {
    $data[$date]['ut'] = (int)$hours;
}

$attendanceDataJson = json_encode($data);

$stmt = $conn->prepare("UPDATE manual_attendance SET start_date=?, end_date=?, attendance_data=? WHERE id=?");
$stmt->bind_param("sssi", $_POST['start_date'], $_POST['end_date'], $attendanceDataJson, $id);
$stmt->execute();
?>