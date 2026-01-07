<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Hitung ringkasan
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$income = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Ordering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {min-height: 100vh;background: #343a40;color: white;}
        .nav-link {color: rgba(255,255,255,.8);}
        .nav-link:hover, .nav-link.active {color: white;background: rgba(255,255,255,.1);}
        .card-stat {border: none;border-radius: 10px;color: white;}
        
        /* Notification Bell Styles */
        .notification-bell {position: relative;cursor: pointer;font-size: 24px;color: white;}
        .notification-badge {
            position: absolute;top: -8px;right: -8px;background: #dc3545;color: white;
            border-radius: 50%;padding: 2px 7px;font-size: 11px;font-weight: bold;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {transform: scale(1);}
            50% {transform: scale(1.1);}
        }
        .notification-dropdown {
            position: absolute;top: 50px;right: 20px;width: 400px;
            background: white;border-radius: 10px;box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;z-index: 9999;max-height: 500px;overflow-y: auto;
        }
        .notification-dropdown.show {display: block;}
        .notification-item {
            padding: 15px;border-bottom: 1px solid #eee;cursor: pointer;
            transition: 0.3s;position: relative;color: #333;
        }
        .notification-item:hover {background: #f8f9fa;}
        .notification-item.unread {background: #e7f3ff;}
        .notification-sound {display: none;}
    </style>
</head>
<body>
    <!-- Audio untuk notifikasi -->
    <audio id="notificationSound" class="notification-sound">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBB" preload="auto">
    </audio>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4"><i class="fas fa-utensils"></i> Admin Panel</h4>
                
                <!-- NOTIFICATION BELL -->
                <div class="text-center mb-4 position-relative">
                    <div class="notification-bell" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notifBadge" style="display:none;">0</span>
                    </div>
                </div>

                <nav class="nav flex-column nav-pills">
                    <a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a class="nav-link" href="admin_menu.php"><i class="fas fa-hamburger me-2"></i> Kelola Menu</a>
                    <a class="nav-link" href="admin_stock.php"><i class="fas fa-boxes me-2"></i> Kelola Stok</a>
                    <a class="nav-link" href="admin_orders.php"><i class="fas fa-list-alt me-2"></i> Data Pesanan</a>
                    <a class="nav-link mt-5 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- Content -->
            <div class="col-md-10 p-4 bg-light">
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="p-3 border-bottom bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-bell me-2"></i>Notifikasi Pesanan</h6>
                        <button onclick="markAllRead()" class="btn btn-sm btn-light">Tandai Semua Dibaca</button>
                    </div>
                    <div id="notificationList">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>Belum ada notifikasi</p>
                        </div>
                    </div>
                </div>

                <h2 class="mb-4">Dashboard Overview</h2>

                <!-- Cards Statistik -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card card-stat bg-primary p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?php echo $total_orders; ?></h3>
                                    <p class="mb-0">Total Pesanan</p>
                                </div>
                                <i class="fas fa-shopping-bag fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat bg-warning p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?php echo $pending_orders; ?></h3>
                                    <p class="mb-0">Pesanan Pending</p>
                                </div>
                                <i class="fas fa-clock fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-stat bg-success p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3>Rp <?php echo number_format($income ?? 0, 0, ',', '.'); ?></h3>
                                    <p class="mb-0">Total Pendapatan</p>
                                </div>
                                <i class="fas fa-wallet fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pesanan Terbaru -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Pesanan Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan</th>
                                    <th>Tipe</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $stmt = $conn->query("SELECT o.*, u.full_name FROM orders o 
                                                     JOIN users u ON o.user_id = u.id 
                                                     ORDER BY o.id DESC LIMIT 5");
                                while($row = $stmt->fetch()): 
                                    $badge = match($row['status']) {
                                        'Pending' => 'bg-warning',
                                        'Completed' => 'bg-success',
                                        'Cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['full_name']; ?></td>
                                    <td><?php echo $row['order_type']; ?></td>
                                    <td>Rp <?php echo number_format($row['total_amount'], 0, ',', '.'); ?></td>
                                    <td><span class="badge <?php echo $badge; ?>"><?php echo $row['status']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lastNotificationCount = 0;
        
        // Check notifikasi setiap 3 detik
        function checkNotifications() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notifBadge');
                    const notifList = document.getElementById('notificationList');
                    
                    if(data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'block';
                        
                        // Play sound jika ada notifikasi baru
                        if(data.count > lastNotificationCount) {
                            playNotificationSound();
                        }
                        lastNotificationCount = data.count;
                        
                        // Render notifikasi
                        let html = '';
                        data.notifications.forEach(notif => {
                            html += `
                                <div class="notification-item unread" onclick="markAsRead(${notif.id}, ${notif.order_id})">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong class="text-primary">Pesanan #${notif.order_id}</strong>
                                        <small class="text-muted">${notif.time}</small>
                                    </div>
                                    <div class="small mb-1">${notif.message}</div>
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-secondary">${notif.order_type}</span>
                                        <span class="text-success fw-bold">Rp ${notif.total}</span>
                                    </div>
                                </div>
                            `;
                        });
                        notifList.innerHTML = html;
                    } else {
                        badge.style.display = 'none';
                        notifList.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><p>Semua notifikasi telah dibaca</p></div>';
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Toggle dropdown
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Mark as read
        function markAsRead(notifId, orderId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'notification_id=' + notifId
            }).then(() => {
                checkNotifications();
                window.location.href = 'admin_orders.php';
            });
        }
        
        // Mark all read
        function markAllRead() {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'mark_all=1'
            }).then(() => checkNotifications());
        }
        
        // Play notification sound
        function playNotificationSound() {
            const audio = document.getElementById('notificationSound');
            audio.play().catch(e => console.log('Audio autoplay prevented'));
        }
        
        // Close dropdown saat klik di luar
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificationDropdown');
            const bell = document.querySelector('.notification-bell');
            if(!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Mulai polling setiap 3 detik
        setInterval(checkNotifications, 3000);
        checkNotifications(); // Check langsung saat load
    </script>
</body>
</html>
