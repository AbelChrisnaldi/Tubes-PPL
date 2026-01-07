<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Update status pesanan
if(isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $order_id = $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if($stmt->execute([$status, $order_id])) {
        header("Location: admin_orders.php?success=1");
        exit();
    } else {
        header("Location: admin_orders.php?error=1");
        exit();
    }
}

$orders = $conn->query("SELECT o.*, u.full_name FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pesanan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {min-height: 100vh;background: #343a40;color: white;}
        .nav-link {color: rgba(255,255,255,.8);}
        .nav-link:hover, .nav-link.active {color: white;background: rgba(255,255,255,.1);}
        
        /* Status Badge Colors */
        .status-Pending {background: #fff3cd !important;color: #856404;border: 2px solid #ffeaa7;}
        .status-Process {background: #cce5ff !important;color: #004085;border: 2px solid #b8daff;}
        .status-Completed {background: #d4edda !important;color: #155724;border: 2px solid #c3e6cb;}
        .status-Cancelled {background: #f8d7da !important;color: #721c24;border: 2px solid #f5c6cb;}
        
        /* Dropdown Status - COMPACT */
        .status-dropdown {
            min-width: 180px;
            max-width: 200px;
        }
        .dropdown-toggle {
            font-weight: bold;
            border-radius: 25px;
            padding: 8px 16px;
            font-size: 0.9rem;
            border: none;
            text-align: left;
            white-space: nowrap;
        }
        .dropdown-toggle::after {
            float: right;
            margin-top: 7px;
        }
        .dropdown-item {
            padding: 10px 20px;
            font-weight: 600;
            border-left: 4px solid transparent;
        }
        .dropdown-item:hover {
            background: #f8f9fa;
        }
        .dropdown-item.status-pending {border-left-color: #ffc107;}
        .dropdown-item.status-process {border-left-color: #17a2b8;}
        .dropdown-item.status-completed {border-left-color: #28a745;}
        .dropdown-item.status-cancelled {border-left-color: #dc3545;}
        
        .order-item {transition: all 0.3s;}
        .order-item:hover {background: #f8f9fa;}
        
        /* Detail Menu List */
        .menu-list {max-height: 100px;overflow-y: auto;font-size: 0.85rem;}
        .menu-item {border-bottom: 1px solid #eee;padding: 4px 0;}
        
        .payment-icon {font-size: 1.1rem;}
        
        /* Table Status Column */
        th:last-child, td:last-child {
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4"><i class="fas fa-utensils"></i> Admin Panel</h4>
                <nav class="nav flex-column nav-pills">
                    <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a class="nav-link" href="admin_menu.php"><i class="fas fa-hamburger me-2"></i> Kelola Menu</a>
                    <a class="nav-link" href="admin_stock.php"><i class="fas fa-boxes me-2"></i> Kelola Stok</a>
                    <a class="nav-link active" href="admin_orders.php"><i class="fas fa-list-alt me-2"></i> Data Pesanan</a>
                    <a class="nav-link mt-5 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- Content -->
            <div class="col-md-10 p-4 bg-light">
                <h2 class="mb-4">Data Pesanan <i class="fas fa-clipboard-list text-primary"></i></h2>

                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>Status pesanan berhasil diupdate!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif(isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>Gagal mengupdate status!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="80">ID</th>
                                        <th width="150">Pelanggan</th>
                                        <th width="250">Detail Pesanan</th>
                                        <th width="120">Total</th>
                                        <th width="100">Pembayaran</th>
                                        <th width="120">Tanggal</th>
                                        <th width="200" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $order): 
                                        // Status config
                                        $status_config = [
                                            'Pending' => ['class' => 'status-Pending', 'text' => 'Menunggu', 'icon' => 'fa-clock'],
                                            'Process' => ['class' => 'status-Process', 'text' => 'Disiapkan', 'icon' => 'fa-fire'],
                                            'Completed' => ['class' => 'status-Completed', 'text' => 'Selesai', 'icon' => 'fa-check-circle'],
                                            'Cancelled' => ['class' => 'status-Cancelled', 'text' => 'Dibatalkan', 'icon' => 'fa-times-circle']
                                        ];
                                        $current_status = $status_config[$order['status']];
                                    ?>
                                    <tr class="order-item">
                                        <td class="fw-bold text-primary fs-5">#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo $order['full_name']; ?></div>
                                            <small class="text-muted"><i class="fas fa-user-circle me-1"></i><?php echo $order['order_type']; ?></small>
                                        </td>
                                        <td>
                                            <div class="menu-list">
                                                <?php 
                                                $detail_stmt = $conn->prepare("SELECT m.name, d.quantity FROM order_details d 
                                                                              JOIN menu m ON d.menu_id = m.id 
                                                                              WHERE d.order_id = ?");
                                                $detail_stmt->execute([$order['id']]);
                                                while($item = $detail_stmt->fetch()):
                                                ?>
                                                <div class="menu-item">
                                                    <span class="badge bg-secondary badge-sm"><?php echo $item['quantity']; ?>x</span> 
                                                    <?php echo $item['name']; ?>
                                                </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-success">
                                            Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($order['payment_method'] == 'QRIS'): ?>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-qrcode me-1"></i>QRIS
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-money-bill-wave me-1"></i>Cash
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($order['order_date'])); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <!-- DROPDOWN STATUS -->
                                            <div class="dropdown status-dropdown d-inline-block">
                                                <button class="btn dropdown-toggle <?php echo $current_status['class']; ?>" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas <?php echo $current_status['icon']; ?> me-2"></i><?php echo $current_status['text']; ?>
                                                </button>
                                                <ul class="dropdown-menu shadow border-0">
                                                    <li>
                                                        <form method="POST" class="m-0">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="status" value="Pending">
                                                            <button type="submit" class="dropdown-item status-pending <?php echo $order['status']=='Pending'?'active':''; ?>">
                                                                <i class="fas fa-clock me-2 text-warning"></i>Menunggu
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="m-0">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="status" value="Process">
                                                            <button type="submit" class="dropdown-item status-process <?php echo $order['status']=='Process'?'active':''; ?>">
                                                                <i class="fas fa-fire me-2 text-info"></i>Disiapkan
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="m-0">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="status" value="Completed">
                                                            <button type="submit" class="dropdown-item status-completed <?php echo $order['status']=='Completed'?'active':''; ?>">
                                                                <i class="fas fa-check-circle me-2 text-success"></i>Selesai
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="m-0" onsubmit="return confirm('Yakin batalkan pesanan ini?')">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="status" value="Cancelled">
                                                            <button type="submit" class="dropdown-item status-cancelled <?php echo $order['status']=='Cancelled'?'active':''; ?>">
                                                                <i class="fas fa-times-circle me-2 text-danger"></i>Batalkan
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                            <p class="mb-0">Belum ada pesanan masuk</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Legend Status -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Keterangan Status:</h6>
                        <div class="row text-center g-3">
                            <div class="col-md-3">
                                <span class="badge status-Pending fs-6 px-4 py-3 d-block">
                                    <i class="fas fa-clock me-2"></i>Menunggu
                                </span>
                                <small class="text-muted d-block mt-2">Pesanan baru masuk</small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge status-Process fs-6 px-4 py-3 d-block">
                                    <i class="fas fa-fire me-2"></i>Disiapkan
                                </span>
                                <small class="text-muted d-block mt-2">Sedang dimasak</small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge status-Completed fs-6 px-4 py-3 d-block">
                                    <i class="fas fa-check-circle me-2"></i>Selesai
                                </span>
                                <small class="text-muted d-block mt-2">Siap diserahkan</small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge status-Cancelled fs-6 px-4 py-3 d-block">
                                    <i class="fas fa-times-circle me-2"></i>Dibatalkan
                                </span>
                                <small class="text-muted d-block mt-2">Pesanan dibatalkan</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
