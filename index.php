<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GO RESTORAN System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('assets/login_bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-utensils fa-3x mb-3"></i>
                        <h2>GO RESTORAN System</h2>
                        <p>Silakan login untuk melanjutkan</p>
                    </div>
                    <div class="p-5">
                        <?php
                        if(isset($_POST['login'])) {
                            $username = $_POST['username'];
                            $password = $_POST['password'];
                            
                            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                            $stmt->execute([$username]);
                            $user = $stmt->fetch();
                            
                            // LOGIKA LOGIN (USER & ADMIN)
                            if($user && password_verify($password, $user['password'])) {
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['full_name'] = $user['full_name'];
                                
                                // Cek apakah kolom role ada (untuk handle error jika database belum diupdate)
                                $role = isset($user['role']) ? $user['role'] : 'customer';
                                $_SESSION['role'] = $role;
                                
                                if($role == 'admin') {
                                    header('Location: admin_dashboard.php');
                                } else {
                                    header('Location: dashboard.php');
                                }
                                exit();
                            } else {
                                echo '<div class="alert alert-danger">Username atau password salah!</div>';
                            }
                        }
                        ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-login btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="register.php">Belum punya akun? Daftar disini</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
