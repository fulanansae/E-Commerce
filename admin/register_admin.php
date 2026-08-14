<?php
require_once 'config.php';

$error = "";
$success = "";

// Set kode rahasia admin di sini (ubah sesuai keinginan Anda)
define('ADMIN_SECRET_KEY', 'RAHASIA_ADMIN_2026');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $secret_key = trim($_POST['secret_key']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // 1. Verifikasi Kode Akses Admin
    if ($secret_key !== ADMIN_SECRET_KEY) {
        $error = "Kode Akses Admin salah! Anda tidak memiliki otorisasi.";
    } else {
        // 2. Cek apakah Username atau Email sudah terpakai
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username atau Email sudah terdaftar.";
        } else {
            // 3. Simpan akun ke database dengan role 'admin'
            $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'admin')";
            if (mysqli_query($conn, $query)) {
                echo "<script>alert('Pendaftaran Admin Berhasil! Silakan login.'); window.location='login.php';</script>";
                exit();
            } else { 
                $error = "Gagal mendaftarkan akun admin."; 
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <h2 style="margin-bottom: 6px; text-align: center;">Registrasi Admin</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem; text-align: center; margin-bottom: 24px;">Area Khusus Pengelola Sistem</p>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Kode Akses Admin (Secret Key)</label>
                <input type="password" name="secret_key" required placeholder="Masukkan kode rahasia admin">
            </div>
            <div class="form-group">
                <label>Username Admin</label>
                <input type="text" name="username" required placeholder="Username admin">
            </div>
            <div class="form-group">
                <label>Email Admin</label>
                <input type="email" name="email" required placeholder="admin@contoh.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Daftar Sebagai Admin</button>
        </form>
        <p style="font-size: 0.85rem; text-align: center; margin-top: 16px;">
            Kembali ke <a href="login.php" style="color: var(--primary); font-weight: 600;">Login</a>
        </p>
    </div>
</body>
</html>