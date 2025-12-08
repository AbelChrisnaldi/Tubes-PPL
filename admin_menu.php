<?php
require_once 'config.php';

// 1. Cek Hak Akses Admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// 2. Auto-create folder uploads jika belum ada (Mencegah Error "No such file")
if(!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

// 3. Handle Add Menu dengan Upload Gambar
if(isset($_POST['add_menu'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $cat = $_POST['category_id'];
    
    // Proses Upload Gambar
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        // Generate nama unik agar tidak bentrok
        $new_image_name = uniqid('menu_', true) . '.' . $image_ext;
        $upload_path = 'uploads/' . $new_image_name;
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if(in_array($image_ext, $allowed_ext)) {
            if(move_uploaded_file($image_tmp, $upload_path)) {
                // Simpan nama file ke database
                $stmt = $conn->prepare("INSERT INTO menu (name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)");
                if($stmt->execute([$name, $desc, $price, $cat, $new_image_name])) {
                    header("Location: admin_menu.php");
                    exit();
                } else {
                    echo "<script>alert('Gagal menyimpan ke database!');</script>";
                }
            } else {
                echo "<script>alert('Gagal mengupload gambar ke folder!');</script>";
            }
        } else {
            echo "<script>alert('Format file harus JPG, JPEG, PNG, atau WEBP!');</script>";
        }
    } else {
        echo "<script>alert('Pilih gambar terlebih dahulu!');</script>";
    }
}

// 4. Handle Delete Menu
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Ambil info gambar dulu sebelum dihapus
    $stmt = $conn->prepare("SELECT image FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    $menu = $stmt->fetch();
    
    // Hapus file fisik gambar jika ada
    if($menu && !empty($menu['image'])) {
        $file_path = 'uploads/' . $menu['image'];
        if(file_exists($file_path)) {
            unlink($file_path); // Delete file dari folder
        }
    }
    
    // Hapus data dari database
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: admin_menu.php");
    exit();
}

// 5. Ambil Data Menu untuk Ditampilkan
$menu_items = $conn->query("SELECT m.*, c.name as category_name FROM menu m JOIN categories c ON m.category_id = c.id ORDER BY m.id DESC");
$categories = $conn->query("SELECT * FROM categories");
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
        .img-thumbnail-custom { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 1px solid #ddd; }
        .no-image-placeholder { width: 80px; height: 80px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 10px; color: #aaa; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <a href="admin_dashboard.php" class="text-dark text-decoration-none me-2">
                    <i class="fas fa-arrow-left"></i>
                </a> 
                Kelola Menu
            </h2>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="fas fa-plus-circle"></i> Tambah Menu
            </button>
        </div>

        <!-- Tabel Menu -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Gambar</th>
                                <th>Nama Makanan</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Deskripsi</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($menu_items->rowCount() > 0): ?>
                                <?php while($row = $menu_items->fetch()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if(!empty($row['image']) && file_exists('uploads/'.$row['image'])): ?>
                                            <img src="uploads/<?php echo $row['image']; ?>" class="img-thumbnail-custom" alt="Menu Image">
                                        <?php else: ?>
                                            <div class="no-image-placeholder">No Img</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
                                    <td class="text-success fw-bold">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                                    <td><small class="text-muted"><?php echo substr(htmlspecialchars($row['description']), 0, 50) . (strlen($row['description']) > 50 ? '...' : ''); ?></small></td>
                                    <td class="text-end pe-4">
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus menu ini secara permanen?')">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3"></i><br>
                                        Belum ada menu yang ditambahkan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Menu -->
    <div class="modal fade" id="addMenuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-utensils me-2"></i>Tambah Menu Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data"> <!-- enctype WAJIB untuk upload file -->
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Menu</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Nasi Goreng Spesial" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gambar Menu</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg, image/png, image/webp" required>
                            <div class="form-text">Format: JPG, PNG, WEBP (Max 2MB). Disarankan rasio 1:1 (persegi).</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php 
                                    // Reset pointer query categories
                                    $categories->execute();
                                    while($cat = $categories->fetch()): 
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Harga (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="price" class="form-control" placeholder="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan detail menu..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_menu" class="btn btn-primary px-4">Simpan Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
