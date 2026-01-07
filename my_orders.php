<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil pesanan milik user ini
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pesanan - <?php echo $_SESSION['full_name'] ?? 'Pelanggan'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        /* NAVBAR EXACTLY SAMA dashboard.php */
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .navbar-brand {
            color: white !important;
            font-weight: 600;
        }
        .user-info {
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-info .fa-user-circle {
            font-size: 1.5rem;
        }
        .order-card {
            border: none;border-radius: 20px;box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s;overflow: hidden;background: white;
        }
        .order-card:hover {transform: translateY(-5px);box-shadow: 0 20px 60px rgba(0,0,0,0.15);}
        
        /* Status Progress Bar */
        .status-progress {position: relative;height: 8px;background: #e9ecef;border-radius: 10px;overflow: hidden;}
        .status-fill {
            height: 100%;transition: width 0.5s ease;border-radius: 10px;
        }
        .status-Pending .status-fill {width: 25%;background: #ffc107;}
        .status-Process .status-fill {width: 50%;background: #17a2b8;}
        .status-Completed .status-fill {width: 75%;background: #28a745;}
        .status-Cancelled .status-fill {width: 100%;background: #dc3545;}
        
        .status-icon {font-size: 2.5rem;margin-bottom: 15px;}
        .status-Pending .status-icon {color: #ffc107;}
        .status-Process .status-icon {color: #17a2b8;}
        .status-Completed .status-icon {color: #28a745;}
        .status-Cancelled .status-icon {color: #dc3545;}
        
        .menu-list {max-height: 120px;overflow-y: auto;}
        .menu-item {
            background: #f8f9fa;border-radius: 10px;padding: 10px;margin-bottom: 8px;
            font-size: 0.95rem;border-left: 4px solid #667eea;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;color: white;
        }
        .refresh-btn:hover {transform: scale(1.05);color: white;}
        
        .page-title {
            color: #495057;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="min-vh-100">
    <!-- Navbar IDENTIK dashboard.php - UKURAN KECIL -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-utensils me-1"></i>Food Ordering System
            </a>
            
            <div class="navbar-nav ms-auto user-info">
                <i class="fas fa-user-circle"></i>
                <span class="fw-bold"><?php echo $_SESSION['full_name']; ?></span>
                <a href="logout.php" class="btn btn-sm btn-light ms-2">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="text-center mb-5 pt-5">
                    <h1 class="display-4 fw-bold mb-3 page-title">
                        <i class="fas fa-clipboard-list-check me-3 text-primary"></i>
                        Tracking Pesanan
                    </h1>
                    <h4 class="text-muted mb-4">Lacak status pesanan Anda secara real-time</h4>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="dashboard.php" class="btn btn-outline-primary px-5 py-3">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <button class="btn btn-primary px-5 py-3 refresh-btn shadow-lg" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh Status
                        </button>
                    </div>
                </div>

                <?php if(empty($orders)): ?>
                    <!-- Empty State -->
                    <div class="card order-card text-center py-5 mb-5 shadow-lg">
                        <div class="card-body">
                            <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted mb-3">Belum ada pesanan</h3>
                            <p class="lead text-muted mb-4">Mulai pesan makanan favorit Anda sekarang!</p>
                            <a href="dashboard.php" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-shopping-bag me-2"></i>Pesan Sekarang
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Orders Grid -->
                    <div class="row g-4 mb-5">
                        <?php foreach($orders as $order): 
                            $status_config = [
                                'Pending' => ['icon' => 'fa-clock', 'title' => 'Menunggu Diproses', 'desc' => 'Pesanan sedang diverifikasi'],
                                'Process' => ['icon' => 'fa-fire', 'title' => 'Sedang Disiapkan', 'desc' => 'Makanan sedang dimasak'],
                                'Completed' => ['icon' => 'fa-check-circle', 'title' => 'Selesai', 'desc' => 'Pesanan siap diambil/dikirim'],
                                'Cancelled' => ['icon' => 'fa-times-circle', 'title' => 'Dibatalkan', 'desc' => 'Pesanan dibatalkan']
                            ];
                            $status = $status_config[$order['status']] ?? $status_config['Pending'];
                        ?>
                        <div class="col-lg-6">
                            <div class="card order-card h-100 status-<?php echo $order['status']; ?>">
                                <div class="card-body p-4">
                                    <!-- Header Pesanan -->
                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                        <div>
                                            <h3 class="fw-bold mb-1 text-dark">#<?php echo $order['id']; ?></h3>
                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                <span class="badge bg-secondary"><?php echo $order['order_type']; ?></span>
                                                <?php if($order['payment_method'] == 'QRIS'): ?>
                                                    <span class="badge bg-primary"><i class="fas fa-qrcode me-1"></i>QRIS</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i>Cash</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('d M Y H:i', strtotime($order['order_date'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="text-success fw-bold mb-0">
                                                Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                            </h4>
                                        </div>
                                    </div>

                                    <!-- Status Progress -->
                                    <div class="status-progress mb-4">
                                        <div class="status-fill"></div>
                                    </div>

                                    <!-- Status Icon & Info -->
                                    <div class="text-center mb-4">
                                        <i class="fas <?php echo $status['icon']; ?> status-icon"></i>
                                        <h4 class="fw-bold mb-2"><?php echo $status['title']; ?></h4>
                                        <p class="text-muted mb-0"><?php echo $status['desc']; ?></p>
                                    </div>

                                    <!-- Detail Menu -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3 text-muted">
                                            <i class="fas fa-list me-2"></i>Menu yang Dipesan
                                        </h6>
                                        <div class="menu-list">
                                            <?php 
                                            $detail_stmt = $conn->prepare("SELECT m.name, d.quantity, d.price FROM order_details d 
                                                                          JOIN menu m ON d.menu_id = m.id 
                                                                          WHERE d.order_id = ?");
                                            $detail_stmt->execute([$order['id']]);
                                            while($item = $detail_stmt->fetch()):
                                            ?>
                                            <div class="menu-item">
                                                <div class="d-flex justify-content-between">
                                                    <span><?php echo $item['quantity']; ?>x <?php echo $item['name']; ?></span>
                                                    <span class="fw-bold text-success">
                                                        Rp <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-flex gap-2">
                                        <?php if($order['status'] == 'Completed'): ?>
                                            <button class="btn btn-success w-100" disabled>
                                                <i class="fas fa-check-circle me-2"></i>Selesai
                                            </button>
                                        <?php elseif($order['status'] == 'Cancelled'): ?>
                                            <button class="btn btn-danger w-100" disabled>
                                                <i class="fas fa-times-circle me-2"></i>Dibatalkan
                                            </button>
                                        <?php else: ?>
                                            <a href="dashboard.php" class="btn btn-outline-primary flex-grow-1">
                                                <i class="fas fa-shopping-bag me-2"></i>Pesan Lagi
                                            </a>
                                            <button class="btn btn-primary flex-grow-1 refresh-btn" onclick="location.reload()">
                                                <i class="fas fa-sync-alt me-2"></i>Refresh
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh setiap 10 detik untuk status real-time
        setTimeout(() => location.reload(), 10000);
        
        // Smooth scroll ke pesanan terbaru
        window.onload = function() {
            const firstOrder = document.querySelector('.order-card');
            if(firstOrder) firstOrder.scrollIntoView({behavior: 'smooth'});
        }
    </script>
</body>
</html>
