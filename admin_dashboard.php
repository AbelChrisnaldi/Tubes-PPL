<?php
require_once 'config.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
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
    <title>Admin Dashboard - Food Ordering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .nav-link { color: rgba(255,255,255,.8); }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,.1); }
        .card-stat { border: none; border-radius: 10px; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4"><i class="fas fa-utensils"></i> Admin Panel</h4>
                <div class="nav flex-column nav-pills">
                    <a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a class="nav-link" href="admin_menu.php"><i class="fas fa-hamburger me-2"></i> Kelola Menu</a>
                    <a class="nav-link" href="admin_orders.php"><i class="fas fa-list-alt me-2"></i> Data Pesanan</a>
                    <a class="nav-link mt-5 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-10 p-4 bg-light">
                <h2 class="mb-4">Dashboard Overview</h2>
                
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
                                $stmt = $conn->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 5");
                                while($row = $stmt->fetch()){
                                    $badge = match($row['status']) {
                                        'Pending' => 'bg-warning',
                                        'Completed' => 'bg-success',
                                        'Cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    echo "<tr>
                                        <td>#{$row['id']}</td>
                                        <td>{$row['full_name']}</td>
                                        <td>{$row['order_type']}</td>
                                        <td>Rp " . number_format($row['total_amount'],0,',','.') . "</td>
                                        <td><span class='badge $badge'>{$row['status']}</span></td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
