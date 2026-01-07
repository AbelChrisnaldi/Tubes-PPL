<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit();
}

// Hitung notifikasi belum dibaca
$count_stmt = $conn->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
$unread_count = $count_stmt->fetchColumn();

// Ambil 5 notifikasi terbaru
$notif_stmt = $conn->query("SELECT n.*, o.order_type, o.total_amount, u.full_name 
                            FROM admin_notifications n
                            JOIN orders o ON n.order_id = o.id
                            JOIN users u ON o.user_id = u.id
                            WHERE n.is_read = 0
                            ORDER BY n.created_at DESC LIMIT 5");
$notifications = $notif_stmt->fetchAll();

// Format data untuk JSON
$data = [
    'count' => $unread_count,
    'notifications' => []
];

foreach($notifications as $notif) {
    $data['notifications'][] = [
        'id' => $notif['id'],
        'order_id' => $notif['order_id'],
        'message' => $notif['message'],
        'customer_name' => $notif['full_name'],
        'order_type' => $notif['order_type'],
        'total' => number_format($notif['total_amount'], 0, ',', '.'),
        'time' => date('H:i', strtotime($notif['created_at']))
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>
