<?php
require_once 'config.php';

$username = 'admin';
$password = 'admin123'; // Password yang Anda inginkan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$full_name = 'Administrator';
$role = 'admin';

try {
    // 1. Hapus admin lama jika ada (agar tidak error duplicate)
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);

    // 2. Masukkan admin baru dengan password yang BENAR
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $full_name, $role]);

    echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
    echo "<h1 style='color:green;'>BERHASIL! ✅</h1>";
    echo "<p>Akun Admin berhasil di-reset.</p>";
    echo "<p>Username: <b>admin</b></p>";
    echo "<p>Password: <b>admin123</b></p>";
    echo "<br><a href='index.php' style='background:blue; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Login Sekarang</a>";
    echo "</div>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
