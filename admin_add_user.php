<?php
session_start();
require_once 'config.php';

// Proteksi Halaman: Hanya Admin terautentikasi yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_baru.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role     = mysqli_real_escape_string($conn, trim($_POST['role']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username atau Email sudah terdaftar.";
    } else {
        $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')";
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('Akun baru berhasil dibuat!'); window.location='admin_dashboard.php';</script>";
            exit();
        } else { 
            $error = "Gagal menambah akun."; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Akun Pengguna / Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <h2 style="margin-bottom: 6px; text-align: center;">Tambah Akun Baru</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem; text-align: center; margin-bottom: 24px;">Menu Panel Kontrol Admin</p>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Username baru">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="email@contoh.com">
            </div>
            <div class="form-group">
                <label>Role / Peran</label>
                <select name="role" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="admin">Admin</option>
                    <option value="user">User Biasa</option>
                </select>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Buat Akun</button>
        </form>
        <p style="font-size: 0.85rem; text-align: center; margin-top: 16px;">
            <a href="admin_dashboard.php" style="color: var(--primary); font-weight: 600;">Kembali ke Dashboard</a>
        </p>
    </div>
</body>
</html>