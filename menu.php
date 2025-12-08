<?php 
require_once 'config.php';
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$order_type = isset($_GET['type']) ? $_GET['type'] : 'Dine In';
$_SESSION['order_type'] = $order_type;

if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Add to cart
if(isset($_POST['add_to_cart'])) {
    $menu_id = $_POST['menu_id'];
    $quantity = $_POST['quantity'];
    
    if(isset($_SESSION['cart'][$menu_id])) {
        $_SESSION['cart'][$menu_id] += $quantity;
    } else {
        $_SESSION['cart'][$menu_id] = $quantity;
    }
    echo '<script>alert("Berhasil ditambahkan ke keranjang!");</script>';
}

// Remove from cart
if(isset($_GET['remove'])) {
    $menu_id = $_GET['remove'];
    unset($_SESSION['cart'][$menu_id]);
    header('Location: menu.php?type=' . $order_type);
    exit();
}

$stmt = $conn->query("SELECT * FROM menu ORDER BY category_id");
$menus = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - <?php echo $order_type; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .navbar-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .menu-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
            overflow: hidden; /* Penting agar gambar tidak keluar border */
        }
        .menu-card:hover { transform: translateY(-5px); }
        
        /* Style Khusus Gambar Menu */
        .menu-img-container {
            height: 200px;
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Background default jika loading/error */
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .menu-photo {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Agar gambar full memenuhi kotak tanpa gepeng */
        }

        .cart-sidebar {
            position: fixed;
            right: 0;
            top: 56px;
            width: 350px;
            height: calc(100vh - 56px);
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .cart-sidebar {
                position: static;
                width: 100%;
                height: auto;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-arrow-left"></i> Kembali</a>
            <span class="text-white fw-bold">
                <i class="fas fa-<?php echo $order_type == 'Dine In' ? 'chair' : 'shopping-bag'; ?>"></i> 
                <?php echo $order_type; ?>
            </span>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <h3 class="mt-4 mb-4">Daftar Menu</h3>
                <div class="row">
                    <?php foreach($menus as $menu): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card menu-card h-100">
                            <!-- BAGIAN GAMBAR YANG DIPERBAIKI -->
                            <div class="menu-img-container">
                                <?php if(!empty($menu['image']) && file_exists('uploads/'.$menu['image'])): ?>
                                    <!-- Jika ada gambar, tampilkan foto -->
                                    <img src="uploads/<?php echo $menu['image']; ?>" class="menu-photo" alt="<?php echo $menu['name']; ?>">
                                <?php else: ?>
                                    <!-- Jika TIDAK ada gambar, tampilkan ikon garpu pisau -->
                                    <i class="fas fa-utensils fa-4x text-white"></i>
                                <?php endif; ?>
                            </div>
                            <!-- END BAGIAN GAMBAR -->

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $menu['name']; ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?php echo $menu['description']; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <h5 class="text-success mb-0 fw-bold">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></h5>
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm me-2 text-center" style="width: 60px;">
                                        <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sidebar Cart -->
            <div class="col-md-4">
                <div class="cart-sidebar">
                    <h4 class="mb-4 border-bottom pb-2"><i class="fas fa-shopping-cart"></i> Detail Pesanan</h4>
                    <?php 
                    $total = 0;
                    if(empty($_SESSION['cart'])): 
                    ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-basket-shopping fa-3x mb-3"></i>
                            <p>Keranjang masih kosong</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group mb-3">
                            <?php foreach($_SESSION['cart'] as $menu_id => $qty): 
                                $stmt = $conn->prepare("SELECT * FROM menu WHERE id = ?");
                                $stmt->execute([$menu_id]);
                                $item = $stmt->fetch();
                                $subtotal = $item['price'] * $qty;
                                $total += $subtotal;
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo $item['name']; ?></h6>
                                        <small class="text-muted"><?php echo $qty; ?> x Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                                        <a href="?type=<?php echo $order_type; ?>&remove=<?php echo $menu_id; ?>" class="text-danger small text-decoration-none">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body">
                                <h5 class="d-flex justify-content-between mb-0">
                                    <span>Total:</span>
                                    <span class="text-success fw-bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                                </h5>
                            </div>
                        </div>
                        
                        <a href="payment.php" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                            <i class="fas fa-credit-card me-2"></i> Lanjut Pembayaran
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
