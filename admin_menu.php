<?php
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if(!is_dir('uploads')) mkdir('uploads', 0777, true);

// Add menu
if(isset($_POST['add_menu'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $cat = $_POST['category_id'];
    $stock = $_POST['stock'] ?? 999;

    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_image_name = uniqid('menu_') . '.' . $image_ext;
        $upload_path = 'uploads/' . $new_image_name;
        
        $allowed_ext = ['jpg','jpeg','png','webp'];
        if(in_array($image_ext, $allowed_ext) && move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $stmt = $conn->prepare("INSERT INTO menu (name, description, price, category_id, image, stock) VALUES (?, ?, ?, ?, ?, ?)");
            if($stmt->execute([$name, $desc, $price, $cat, $new_image_name, $stock])) {
                header("Location: admin_menu.php?success=add");
                exit();
            }
        }
    }
}

// EDIT MENU (FITUR BARU)
if(isset($_POST['edit_menu'])) {
    $id = $_POST['menu_id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $cat = $_POST['category_id'];
    $stock = $_POST['stock'];
    
    // Cek apakah ada gambar baru diupload
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Hapus gambar lama
        $stmt = $conn->prepare("SELECT image FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        $old_menu = $stmt->fetch();
        if($old_menu && !empty($old_menu['image']) && file_exists('uploads/'.$old_menu['image'])) {
            unlink('uploads/'.$old_menu['image']);
        }
        
        // Upload gambar baru
        $image_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_image_name = uniqid('menu_') . '.' . $image_ext;
        $upload_path = 'uploads/' . $new_image_name;
        
        $allowed_ext = ['jpg','jpeg','png','webp'];
        if(in_array($image_ext, $allowed_ext) && move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $stmt = $conn->prepare("UPDATE menu SET name=?, description=?, price=?, category_id=?, stock=?, image=? WHERE id=?");
            $stmt->execute([$name, $desc, $price, $cat, $stock, $new_image_name, $id]);
        }
    } else {
        // Update tanpa ganti gambar
        $stmt = $conn->prepare("UPDATE menu SET name=?, description=?, price=?, category_id=?, stock=? WHERE id=?");
        $stmt->execute([$name, $desc, $price, $cat, $stock, $id]);
    }
    
    header("Location: admin_menu.php?success=edit");
    exit();
}

// Delete menu
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("SELECT image FROM menu WHERE id = ?");
    $stmt->execute([$id]); $menu = $stmt->fetch();
    if($menu && !empty($menu['image']) && file_exists('uploads/'.$menu['image'])) {
        unlink('uploads/'.$menu['image']);
    }
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_menu.php?success=delete");
    exit();
}

$menu_items = $conn->query("SELECT m.*, c.name as category_name FROM menu m 
                           LEFT JOIN categories c ON m.category_id = c.id 
                           ORDER BY m.id DESC")->fetchAll();
$categories = $conn->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {min-height: 100vh;background: #343a40;color: white;}
        .nav-link {color: rgba(255,255,255,.8);}
        .nav-link:hover, .nav-link.active {color: white;background: rgba(255,255,255,.1);}
        .img-thumbnail-custom {width:60px;height:60px;object-fit:cover;border-radius:10px;}
        .no-image {width:60px;height:60px;background:#eee;display:flex;align-items:center;justify-content:center;border-radius:10px;color:#aaa;font-size:0.7rem;}
        .stock-badge {font-size:0.85rem;}
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
                    <a class="nav-link active" href="admin_menu.php"><i class="fas fa-hamburger me-2"></i> Kelola Menu</a>
                    <a class="nav-link" href="admin_stock.php"><i class="fas fa-boxes me-2"></i> Kelola Stok</a>
                    <a class="nav-link" href="admin_orders.php"><i class="fas fa-list-alt me-2"></i> Data Pesanan</a>
                    <a class="nav-link mt-5 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <div class="col-md-10 p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Menu <i class="fas fa-utensils text-primary"></i></h2>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Menu
                    </button>
                </div>

                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo match($_GET['success']) {
                            'add' => 'Menu berhasil ditambahkan!',
                            'edit' => 'Menu berhasil diupdate!',
                            'delete' => 'Menu berhasil dihapus!',
                            default => 'Operasi berhasil!'
                        };
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Gambar</th>
                                        <th>Nama Menu</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($menu_items as $row): 
                                        $stock_class = $row['stock'] == 0 ? 'bg-danger' : ($row['stock'] < 10 ? 'bg-warning text-dark' : 'bg-success');
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php if(!empty($row['image']) && file_exists("uploads/{$row['image']}")): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" class="img-thumbnail-custom" alt="Menu">
                                            <?php else: ?>
                                                <div class="no-image">No Img</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $row['category_name'] ?? 'Uncategorized'; ?></span></td>
                                        <td class="text-success fw-bold">Rp <?php echo number_format($row['price'],0,',','.'); ?></td>
                                        <td><span class="badge <?php echo $stock_class; ?> stock-badge px-3"><?php echo $row['stock']; ?> pcs</span></td>
                                        <td class="small text-muted"><?php echo substr(htmlspecialchars($row['description']),0,50) . (strlen($row['description'])>50?'...':''); ?></td>
                                        <td class="text-end pe-4">
                                            <!-- TOMBOL EDIT BARU -->
                                            <button class="btn btn-sm btn-outline-warning me-1" onclick="editMenu(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH MENU -->
    <div class="modal fade" id="addMenuModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-utensils me-2"></i>Tambah Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label fw-bold">Nama Menu</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Gambar</label><input type="file" name="image" class="form-control" accept="image/*" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Harga</label>
                                <div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="price" class="form-control" required></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Stok</label><input type="number" name="stock" value="999" min="0" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label fw-bold">Deskripsi</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_menu" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT MENU (BARU) -->
    <div class="modal fade" id="editMenuModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="menu_id" id="edit_menu_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gambar Saat Ini</label>
                            <div><img id="edit_current_image" src="" class="img-thumbnail" style="max-width:150px;max-height:150px;"></div>
                            <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar</small>
                        </div>
                        <div class="mb-3"><label class="form-label fw-bold">Gambar Baru (Opsional)</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                        <div class="mb-3"><label class="form-label fw-bold">Nama Menu</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kategori</label>
                                <select name="category_id" id="edit_category" class="form-select" required>
                                    <?php foreach($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Harga</label>
                                <div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="price" id="edit_price" class="form-control" required></div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Stok</label><input type="number" name="stock" id="edit_stock" min="0" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label fw-bold">Deskripsi</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_menu" class="btn btn-warning">Update Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function untuk mengisi form edit
        function editMenu(menu) {
            document.getElementById('edit_menu_id').value = menu.id;
            document.getElementById('edit_name').value = menu.name;
            document.getElementById('edit_price').value = menu.price;
            document.getElementById('edit_stock').value = menu.stock;
            document.getElementById('edit_category').value = menu.category_id;
            document.getElementById('edit_description').value = menu.description || '';
            
            // Set gambar saat ini
            if(menu.image) {
                document.getElementById('edit_current_image').src = 'uploads/' + menu.image;
                document.getElementById('edit_current_image').style.display = 'block';
            } else {
                document.getElementById('edit_current_image').style.display = 'none';
            }
            
            // Buka modal
            var modal = new bootstrap.Modal(document.getElementById('editMenuModal'));
            modal.show();
        }
    </script>
</body>
</html>
