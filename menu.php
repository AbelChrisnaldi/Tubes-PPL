<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$order_type = isset($_GET['type']) ? $_GET['type'] : 'Dine In';
$_SESSION['order_type'] = $order_type;

if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Add to cart dengan CEK STOK
if(isset($_POST['add_to_cart'])) {
    $menu_id = $_POST['menu_id'];
    $quantity = $_POST['quantity'];
    
    $stmt = $conn->prepare("SELECT name, price, stock FROM menu WHERE id = ?");
    $stmt->execute([$menu_id]);
    $menu_item = $stmt->fetch();
    
    if($menu_item) {
        $current_cart_qty = isset($_SESSION['cart'][$menu_id]) ? $_SESSION['cart'][$menu_id] : 0;
        $total_qty = $current_cart_qty + $quantity;
        
        if($total_qty <= $menu_item['stock']) {
            $_SESSION['cart'][$menu_id] = $total_qty;
            $_SESSION['success_msg'] = "✓ {$menu_item['name']} ditambahkan!\nStok tersisa: " . ($menu_item['stock'] - $total_qty) . " pcs";
        } else {
            $_SESSION['error_msg'] = "⚠ Stok {$menu_item['name']} tidak mencukupi!\nStok tersedia: {$menu_item['stock']} pcs\nDi keranjang: {$current_cart_qty} pcs";
        }
    }
    header("Location: menu.php?type=" . $order_type);
    exit();
}

// UPDATE QUANTITY DI KERANJANG
if(isset($_POST['update_cart'])) {
    $menu_id = $_POST['menu_id'];
    $new_quantity = (int)$_POST['new_quantity'];
    
    // Cek stok
    $stmt = $conn->prepare("SELECT name, stock FROM menu WHERE id = ?");
    $stmt->execute([$menu_id]);
    $menu_item = $stmt->fetch();
    
    if($new_quantity > 0 && $new_quantity <= $menu_item['stock']) {
        $_SESSION['cart'][$menu_id] = $new_quantity;
        $_SESSION['success_msg'] = "✓ Jumlah {$menu_item['name']} diubah menjadi {$new_quantity} pcs";
    } elseif($new_quantity > $menu_item['stock']) {
        $_SESSION['error_msg'] = "⚠ Stok {$menu_item['name']} tidak mencukupi!\nMaksimal: {$menu_item['stock']} pcs";
    } else {
        // Jika quantity 0, hapus dari cart
        unset($_SESSION['cart'][$menu_id]);
        $_SESSION['success_msg'] = "✓ {$menu_item['name']} dihapus dari keranjang";
    }
    header("Location: menu.php?type=" . $order_type);
    exit();
}

// Remove from cart
if(isset($_GET['remove'])) {
    $menu_id = $_GET['remove'];
    unset($_SESSION['cart'][$menu_id]);
    $_SESSION['success_msg'] = "✓ Item berhasil dihapus dari keranjang";
    header("Location: menu.php?type=" . $order_type);
    exit();
}

$stmt = $conn->query("SELECT * FROM menu WHERE stock > 0 ORDER BY category_id");
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
        body {background: #f8f9fa;}
        .navbar-custom {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);}
        .menu-card {border:none;border-radius:15px;box-shadow:0 3px 15px rgba(0,0,0,0.1);transition:transform 0.3s;margin-bottom:20px;overflow:hidden;}
        .menu-card:hover {transform:translateY(-5px);box-shadow:0 5px 25px rgba(0,0,0,0.15);}
        .menu-img-container {height:200px;width:100%;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);display:flex;align-items:center;justify-content:center;overflow:hidden;}
        .menu-photo {width:100%;height:100%;object-fit:cover;}
        .cart-sidebar {position:fixed;right:0;top:56px;width:400px;height:calc(100vh - 56px);background:white;box-shadow:-2px 0 10px rgba(0,0,0,0.1);padding:20px;overflow-y:auto;z-index:1000;}
        
        /* Quantity Controls - IMPROVED */
        .qty-control {display:flex;align-items:center;gap:8px;justify-content:center;}
        .qty-btn {
            width:40px;height:40px;border:none;
            background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color:white;border-radius:10px;font-weight:bold;font-size:1.2rem;
            cursor:pointer;transition:all 0.3s;
            display:flex;align-items:center;justify-content:center;
        }
        .qty-btn:hover {
            transform:scale(1.1);
            box-shadow:0 5px 15px rgba(102,126,234,0.4);
        }
        .qty-btn:active {transform:scale(0.95);}
        .qty-btn:disabled {
            background:#ccc;cursor:not-allowed;opacity:0.5;
        }
        .qty-input {
            width:70px;text-align:center;
            border:2px solid #667eea;border-radius:10px;
            font-weight:bold;padding:8px 5px;font-size:1.1rem;
        }
        .qty-input:focus {
            outline:none;border-color:#764ba2;
            box-shadow:0 0 0 3px rgba(102,126,234,0.2);
        }
        
        .cart-item {
            background:#f8f9fa;border-radius:15px;padding:15px;
            margin-bottom:15px;border:2px solid #e9ecef;transition:0.3s;
        }
        .cart-item:hover {border-color:#667eea;background:#fff;}
        
        @media (max-width: 768px) {
            .cart-sidebar {position:static;width:100%;height:auto;margin-top:20px;}
        }
        
        /* Toast Notification */
        .toast-container {position:fixed;top:80px;right:20px;z-index:9999;}
    </style>
</head>
<body>
    <nav class="navbar navbar-custom navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
            <span class="text-white fw-bold fs-5">
                <i class="fas fa-<?php echo $order_type == 'Dine In' ? 'chair' : 'shopping-bag'; ?> me-2"></i>
                <?php echo $order_type; ?>
            </span>
        </div>
    </nav>

    <!-- Toast Notification -->
    <div class="toast-container">
        <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="toast show align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo nl2br($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="toast show align-items-center text-white bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo nl2br($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Menu List -->
            <div class="col-lg-8">
                <h3 class="mt-4 mb-4"><i class="fas fa-utensils me-2 text-primary"></i>Daftar Menu</h3>
                <div class="row">
                    <?php foreach($menus as $menu): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card menu-card h-100">
                            <div class="menu-img-container">
                                <?php if(!empty($menu['image']) && file_exists("uploads/{$menu['image']}")): ?>
                                    <img src="uploads/<?php echo $menu['image']; ?>" class="menu-photo" alt="<?php echo $menu['name']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-utensils fa-4x text-white"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $menu['name']; ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?php echo $menu['description']; ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-success mb-0 fw-bold">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></h5>
                                    <span class="badge <?php echo $menu['stock'] < 10 ? 'bg-warning text-dark' : 'bg-success'; ?> fs-6 px-3 py-2">
                                        <i class="fas fa-box me-1"></i><?php echo $menu['stock']; ?>
                                    </span>
                                </div>
                                <form method="POST" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $menu['stock']; ?>" 
                                           class="form-control text-center fw-bold" style="width:70px;border:2px solid #667eea;border-radius:10px;">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary flex-grow-1 fw-bold">
                                        <i class="fas fa-cart-plus me-2"></i>Tambah
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Sidebar dengan KONTROL QUANTITY IMPROVED -->
            <div class="col-lg-4">
                <div class="cart-sidebar">
                    <h4 class="mb-4 border-bottom pb-3">
                        <i class="fas fa-shopping-cart text-primary"></i> Keranjang Belanja
                        <?php if(!empty($_SESSION['cart'])): ?>
                            <span class="badge bg-primary float-end"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </h4>
                    
                    <?php 
                    $total = 0;
                    if(empty($_SESSION['cart'])): 
                    ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-basket-shopping fa-4x mb-3"></i>
                            <p class="fw-bold">Keranjang masih kosong</p>
                            <small>Pilih menu untuk memulai pesanan</small>
                        </div>
                    <?php else: ?>
                        <?php foreach($_SESSION['cart'] as $menu_id => $qty): 
                            $stmt = $conn->prepare("SELECT * FROM menu WHERE id = ?");
                            $stmt->execute([$menu_id]);
                            $item = $stmt->fetch();
                            
                            if(!$item) continue;
                            
                            $subtotal = $item['price'] * $qty;
                            $total += $subtotal;
                        ?>
                        <div class="cart-item">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold"><?php echo $item['name']; ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-1"></i>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> / pcs
                                    </small>
                                </div>
                                <a href="?type=<?php echo $order_type; ?>&remove=<?php echo $menu_id; ?>" 
                                   class="text-danger text-decoration-none ms-2" 
                                   onclick="return confirm('Hapus <?php echo $item['name']; ?> dari keranjang?')">
                                    <i class="fas fa-trash-alt fa-lg"></i>
                                </a>
                            </div>
                            
                            <!-- FORM UPDATE QUANTITY - IMPROVED -->
                            <form method="POST" id="form-<?php echo $menu_id; ?>">
                                <input type="hidden" name="menu_id" value="<?php echo $menu_id; ?>">
                                <input type="hidden" name="update_cart" value="1">
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="qty-control">
                                        <button type="button" 
                                                class="qty-btn" 
                                                onclick="updateQty(<?php echo $menu_id; ?>, <?php echo $qty; ?> - 1, <?php echo $item['stock']; ?>)"
                                                <?php echo $qty <= 1 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <input type="number" 
                                               id="qty-<?php echo $menu_id; ?>" 
                                               name="new_quantity" 
                                               value="<?php echo $qty; ?>" 
                                               min="1" 
                                               max="<?php echo $item['stock']; ?>" 
                                               class="qty-input"
                                               readonly>
                                        
                                        <button type="button" 
                                                class="qty-btn" 
                                                onclick="updateQty(<?php echo $menu_id; ?>, <?php echo $qty; ?> + 1, <?php echo $item['stock']; ?>)"
                                                <?php echo $qty >= $item['stock'] ? 'disabled' : ''; ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="text-end">
                                        <div class="fw-bold text-success fs-5">
                                            Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                        </div>
                                        <small class="text-muted">Stok: <?php echo $item['stock']; ?></small>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="card bg-primary text-white border-0 mb-3 mt-4">
                            <div class="card-body py-3">
                                <h4 class="d-flex justify-content-between mb-0">
                                    <span><i class="fas fa-receipt me-2"></i>Total</span>
                                    <span class="fw-bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                                </h4>
                            </div>
                        </div>
                        
                        <a href="payment.php" class="btn btn-success w-100 py-3 fw-bold shadow-lg">
                            <i class="fas fa-credit-card me-2"></i>Lanjut Pembayaran
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function untuk update quantity - IMPROVED
        function updateQty(menuId, newQty, maxStock) {
            // Validasi
            if(newQty < 1) {
                if(confirm('Hapus item ini dari keranjang?')) {
                    newQty = 0;
                } else {
                    return;
                }
            }
            
            if(newQty > maxStock) {
                alert('⚠ Stok maksimal: ' + maxStock + ' pcs');
                return;
            }
            
            // Update input value
            document.getElementById('qty-' + menuId).value = newQty;
            
            // Submit form
            document.getElementById('form-' + menuId).submit();
        }
        
        // Auto hide toast after 3 seconds
        setTimeout(function() {
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                var bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            });
        }, 3000);
    </script>
</body>
</html>
