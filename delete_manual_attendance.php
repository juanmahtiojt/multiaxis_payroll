<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

session_start();
include 'config.php';
header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM manual_attendance WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $response['status'] = 'success';
            } else {
                $response['status'] = 'not_found';
            }

            $stmt->close();
        } else {
            $response['status'] = 'prepare_failed';
        }
    } else {
        $response['status'] = 'invalid_id';
    }
} else {
    $response['status'] = 'invalid_request';
}

ob_end_clean();
echo json_encode($response);
exit;
