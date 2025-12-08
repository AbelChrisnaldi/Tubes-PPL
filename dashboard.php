<?php 
require_once 'config.php';
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Food Ordering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .order-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            height: 300px;
        }
        .order-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .dine-in {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .take-away {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .order-icon {
            font-size: 80px;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-utensils"></i> Food Ordering System</a>
            <div class="text-white">
                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['full_name']; ?> 
                <a href="logout.php" class="btn btn-sm btn-light ms-2">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <h2 class="text-center mb-5">Selamat Datang <?php echo $_SESSION['full_name']; ?>!</h2>
        <h4 class="text-center mb-4">Pilih Tipe Pesanan Anda</h4>
        
        <div class="row justify-content-center">
            <div class="col-md-5 mb-4">
                <div class="card order-card dine-in" onclick="location.href='menu.php?type=Dine In'">
                    <div class="card-body text-center">
                        <i class="fas fa-chair order-icon"></i>
                        <h3>Dine In</h3>
                        <p class="mb-0">Makan di tempat</p>
                        <p class="mt-2">Nikmati makanan Anda di restoran kami</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5 mb-4">
                <div class="card order-card take-away" onclick="location.href='menu.php?type=Take Away'">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-bag order-icon"></i>
                        <h3>Take Away</h3>
                        <p class="mb-0">Bawa pulang</p>
                        <p class="mt-2">Pesan dan bawa pulang makanan Anda</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
