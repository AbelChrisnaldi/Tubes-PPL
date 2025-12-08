<?php 
require_once 'config.php';
if(!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header('Location: dashboard.php');
    exit();
}

$total = 0;
foreach($_SESSION['cart'] as $menu_id => $qty) {
    $stmt = $conn->prepare("SELECT price FROM menu WHERE id = ?");
    $stmt->execute([$menu_id]);
    $item = $stmt->fetch();
    $total += $item['price'] * $qty;
}

if(isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'];
    $order_type = $_SESSION['order_type'];
    
    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_type, payment_method, total_amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $order_type, $payment_method, $total]);
    $order_id = $conn->lastInsertId();
    
    // Insert order details
    foreach($_SESSION['cart'] as $menu_id => $qty) {
        $stmt = $conn->prepare("SELECT price FROM menu WHERE id = ?");
        $stmt->execute([$menu_id]);
        $item = $stmt->fetch();
        
        $stmt = $conn->prepare("INSERT INTO order_details (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $menu_id, $qty, $item['price']]);
    }
    
    // Clear cart
    unset($_SESSION['cart']);
    header('Location: success.php?order_id=' . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 0; }
        .payment-card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .payment-option:hover { transform: scale(1.05); border-color: #667eea; }
        .payment-option input[type="radio"] { display: none; }
        .payment-option input[type="radio"]:checked + label { border-color: #667eea; background: #f0f3ff; }
        .payment-icon { font-size: 60px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="payment-card p-5">
                    <h2 class="text-center mb-4"><i class="fas fa-credit-card"></i> Pilih Metode Pembayaran</h2>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Detail Pesanan</h5>
                            <?php foreach($_SESSION['cart'] as $menu_id => $qty): 
                                $stmt = $conn->prepare("SELECT * FROM menu WHERE id = ?");
                                $stmt->execute([$menu_id]);
                                $item = $stmt->fetch();
                            ?>
                            <div class="d-flex justify-content-between">
                                <span><?php echo $item['name']; ?> (<?php echo $qty; ?>x)</span>
                                <span>Rp <?php echo number_format($item['price'] * $qty, 0, ',', '.'); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <hr>
                            <h5 class="d-flex justify-content-between">
                                <span>Total Pembayaran:</span>
                                <span class="text-success">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                            </h5>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="Cash" required>
                                    <div>
                                        <div class="payment-icon text-success"><i class="fas fa-money-bill-wave"></i></div>
                                        <h4>Cash</h4>
                                        <p class="text-muted">Bayar tunai</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="QRIS" required>
                                    <div>
                                        <div class="payment-icon text-primary"><i class="fas fa-qrcode"></i></div>
                                        <h4>QRIS</h4>
                                        <p class="text-muted">Scan QR Code</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="process_payment" class="btn btn-success btn-lg w-100 mt-4">
                            <i class="fas fa-check-circle"></i> Proses Pembayaran
                        </button>
                        <a href="menu.php?type=<?php echo $_SESSION['order_type']; ?>" class="btn btn-secondary btn-lg w-100 mt-2">
                            <i class="fas fa-arrow-left"></i> Kembali ke Menu
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                this.querySelector('input[type="radio"]').checked = true;
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.style.borderColor = '#e0e0e0';
                    opt.style.background = 'white';
                });
                this.style.borderColor = '#667eea';
                this.style.background = '#f0f3ff';
            });
        });
    </script>
</body>
</html>
