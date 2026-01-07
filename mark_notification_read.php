<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit();
}

if(isset($_POST['notification_id'])) {
    $id = $_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} elseif(isset($_POST['mark_all'])) {
    $conn->query("UPDATE admin_notifications SET is_read = 1");
    echo json_encode(['success' => true]);
}
?>
