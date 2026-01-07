<?php
require_once 'config.php';  // Config sudah handle session

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Update stok massal
if(isset($_POST['update_stock'])) {
    foreach($_POST['stock'] as $menu_id => $new_stock) {
        $stmt = $conn->prepare("UPDATE menu SET stock = ? WHERE id = ?");
        $stmt->execute([$new_stock, $menu_id]);
    }
    header("Location: admin_stock.php?success=1");
    exit();
}

// Ambil semua menu
$menus = $conn->query("SELECT m.*, c.name as category_name FROM menu m 
                       LEFT JOIN categories c ON m.category_id = c.id 
                       ORDER BY m.stock ASC, m.name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {min-height: 100vh;background: #343a40;color: white;}
        .nav-link {color: rgba(255,255,255,.8);}
        .nav-link:hover, .nav-link.active {color: white;background: rgba(255,255,255,.1);}
        .stock-low {background-color: #fff3cd !important;color: #856404;}
        .stock-out {background-color: #f8d7da !important;color: #721c24;}
        .stock-good {background-color: #d4edda !important;color: #155724;}
        .table input[type="number"] {width: 100px;}
        .img-thumbnail-custom {width:60px;height:60px;object-fit:cover;border-radius:10px;}
        .no-image {width:60px;height:60px;background:#eee;display:flex;align-items:center;justify-content:center;border-radius:10px;color:#aaa;font-size:0.7rem;}
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR IDENTIK -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4"><i class="fas fa-utensils"></i> Admin Panel</h4>
                <nav class="nav flex-column nav-pills">
                    <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a class="nav-link" href="admin_menu.php"><i class="fas fa-hamburger me-2"></i> Kelola Menu</a>
                    <a class="nav-link active" href="admin_stock.php"><i class="fas fa-boxes me-2"></i> Kelola Stok</a>
                    <a class="nav-link" href="admin_orders.php"><i class="fas fa-list-alt me-2"></i> Data Pesanan</a>
                    <a class="nav-link mt-5 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- CONTENT IDENTIK LAYOUT -->
            <div class="col-md-10 p-4 bg-light">
                <h2 class="mb-4">Kelola Stok <i class="fas fa-boxes-stacked text-primary"></i></h2>

                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i> Stok berhasil diupdate!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Card Utama IDENTIK -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Update Stok Cepat (Semua Menu Sekaligus)</h5>
                    </div>
                    <form method="POST">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 w-10%">Gambar</th>
                                        <th class="w-25%">Nama Menu</th>
                                        <th class="w-15%">Kategori</th>
                                        <th class="w-10%">Harga</th>
                                        <th class="w-15%">Stok Saat Ini</th>
                                        <th class="w-25%">Update Stok Baru</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($menus)): ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-box fa-3x mb-3"></i><br>Belum ada menu</td></tr>
                                    <?php else: ?>
                                        <?php foreach($menus as $menu): 
                                            $stock_class = $menu['stock'] == 0 ? 'stock-out badge-danger' : 
                                                         ($menu['stock'] < 10 ? 'stock-low badge-warning text-dark' : 'stock-good badge-success');
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php if(!empty($menu['image']) && file_exists("uploads/{$menu['image']}")): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($menu['image']); ?>" class="img-thumbnail-custom" alt="Menu">
                                                <?php else: ?>
                                                    <div class="no-image">No Img</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($menu['name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo $menu['category_name'] ?? 'Uncategorized'; ?></span></td>
                                            <td class="text-success fw-bold">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge fs-6 px-3 py-2 <?php echo $stock_class; ?>">
                                                    <?php echo $menu['stock']; ?> pcs
                                                    <?php if($menu['stock'] == 0): ?><i class="fas fa-exclamation-triangle ms-1"></i><?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <input type="number" name="stock[<?php echo $menu['id']; ?>]" 
                                                       value="<?php echo $menu['stock']; ?>" min="0" class="form-control form-control-sm" required>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-light border-0">
                            <button type="submit" name="update_stock" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Update Semua Stok
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Legend Card (IDENTIK Layout) -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Status Stok:</h6>
                        <div class="row text-center g-3">
                            <div class="col-md-3">
                                <span class="badge stock-out fs-6 px-3 py-2"><i class="fas fa-times me-1"></i>0 pcs</span>
                                <div class="mt-1 fw-bold text-danger">HABIS</div>
                            </div>
                            <div class="col-md-3">
                                <span class="badge stock-low fs-6 px-3 py-2"><i class="fas fa-exclamation-triangle me-1"></i>1-9 pcs</span>
                                <div class="mt-1 fw-bold text-warning">LOW STOCK</div>
                            </div>
                            <div class="col-md-3">
                                <span class="badge stock-good fs-6 px-3 py-2"><i class="fas fa-check me-1"></i>10+ pcs</span>
                                <div class="mt-1 fw-bold text-success">TERSEDIA</div>
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
