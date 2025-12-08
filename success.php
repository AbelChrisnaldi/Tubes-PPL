<?php 
require_once 'config.php';
if(!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit();
}

$order_id = $_GET['order_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            padding: 60px 40px;
        }
        .success-icon {
            font-size: 100px;
            color: #28a745;
            animation: scaleIn 0.5s ease-in-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="success-card">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="mb-3">Pesanan Berhasil!</h2>
                    <p class="lead">Terima kasih atas pesanan Anda</p>
                    
                    <div class="card my-4">
                        <div class="card-body">
                            <div class="row text-start">
                                <div class="col-6"><strong>No. Pesanan:</strong></div>
                                <div class="col-6">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                
                                <div class="col-6 mt-2"><strong>Tipe:</strong></div>
                                <div class="col-6 mt-2"><?php echo $order['order_type']; ?></div>
                                
                                <div class="col-6 mt-2"><strong>Pembayaran:</strong></div>
                                <div class="col-6 mt-2"><?php echo $order['payment_method']; ?></div>
                                
                                <div class="col-6 mt-2"><strong>Total:</strong></div>
                                <div class="col-6 mt-2 text-success"><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="dashboard.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
