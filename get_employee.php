<?php
session_start();
include __DIR__ . "/config.php";  // adjust if config path is different

header('Content-Type: application/json');

if (!isset($_GET['id_no']) || trim($_GET['id_no']) === '') {
    echo json_encode([
        "success" => false,
        "message" => "No ID provided"
    ]);
    exit;
}

$id_no = trim($_GET['id_no']);

$stmt = $conn->prepare("SELECT name, department, pay_schedule FROM daily_rate WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "data" => $row
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Employee not found"
    ]);
}

$stmt->close();
$conn->close();
