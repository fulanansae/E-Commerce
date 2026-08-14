<?php
require_once 'config.php';

$error = "";
$success = "";

// Set kode rahasia admin di sini
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
                echo "<script>alert('Pendaftaran Admin Berhasil! Silakan login.'); window.location='login_baru.php';</script>";
                exit();
            } else { 
                $error = "Gagal mendaftar akun admin."; 
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
     <!-- Favicon SVG berbentuk Toko/Bag tanpa perlu simpan file gambar -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛍️</text></svg>">
    <title>Registrasi Admin Modern</title>
    <!-- Font Inter & FontAwesome Icon -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body.auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(-45deg, #0f172a, #1e1b4b, #311042, #0f172a);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            padding: 20px;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            color: #ffffff;
            transition: transform 0.3s ease;
        }

        .auth-card:hover {
            transform: translateY(-2px);
        }

        .badge-admin {
            display: inline-block;
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.4);
            color: #818cf8;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .auth-card h2 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-card p.subtitle {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 28px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box i.input-icon {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .input-box input {
            width: 100%;
            padding: 12px 42px 12px 42px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-box input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            background: rgba(15, 23, 42, 0.8);
        }

        .input-box input:focus ~ i.input-icon {
            color: #818cf8;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            cursor: pointer;
            color: #64748b;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #fff;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(79, 70, 229, 0.4);
        }

        .footer-text {
            font-size: 0.85rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 24px;
        }

        .footer-text a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .footer-text a:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }
    </style>
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div style="text-align: center;">
            <span class="badge-admin"><i class="fa-solid fa-shield-halved"></i> Security Portal</span>
            <h2>Registrasi Admin</h2>
            <p class="subtitle">Buat akses pengelola sistem baru</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Secret Key -->
            <div class="form-group">
                <label>Kode Akses Admin</label>
                <div class="input-box">
                    <i class="fa-solid fa-key input-icon"></i>
                    <input type="password" name="secret_key" id="secret_key" required placeholder="Kode Rahasia Admin">
                    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('secret_key', this)"></i>
                </div>
            </div>

            <!-- Username -->
            <div class="form-group">
                <label>Username Admin</label>
                <div class="input-box">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="username" required placeholder="Username baru">
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label>Email Admin</label>
                <div class="input-box">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" required placeholder="admin@domain.com">
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label>Password</label>
                <div class="input-box">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" required placeholder="••••••••">
                    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('password', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i> Daftar Sebagai Admin
            </button>
        </form>

        <p class="footer-text">
            Kembali ke <a href="login_baru.php">Halaman Login</a>
        </p>
    </div>

    <!-- JavaScript Toggle Show/Hide Password -->
    <script>
        function toggleVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>