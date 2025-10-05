<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM manual_attendance WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: manual.php?msg=deleted");
            exit;
        } else {
            header("Location: manual.php?msg=delete_error");
            exit;
        }
        $stmt->close();
    } else {
        header("Location: manual.php?msg=delete_error");
        exit;
    }
}
?>
