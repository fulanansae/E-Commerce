<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika user/admin sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "admin.php" : "index.php"));
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password          = $_POST['password'];

    if (!empty($username_or_email) && !empty($password)) {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                header("Location: " . ($user['role'] === 'admin' ? "admin.php" : "index.php"));
                exit();
            } else {
                $error = "Password yang Anda masukkan salah.";
            }
        } else {
            $error = "Username atau Email tidak ditemukan.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Mohon isi semua bidang.";
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
    <title>Masuk - Warung Kode</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
    :root {
        --bg-body: #0b0f19;
        --bg-card: rgba(23, 32, 51, 0.75);
        --border: rgba(255, 255, 255, 0.08);
        --primary: #6366f1;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
    }
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box !important; 
        font-family: 'Plus Jakarta Sans', sans-serif; 
    }
    body.auth-wrapper {
        background: var(--bg-body);
        color: var(--text-main);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .auth-card {
        background: var(--bg-card);
        backdrop-filter: blur(16px);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 36px 32px;
        width: 100%;
        max-width: 420px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
    }
    .auth-header { text-align: center; margin-bottom: 28px; }
    .brand-logo { font-size: 2rem; color: #818cf8; margin-bottom: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #cbd5e1;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* FIX UTAMA ICON BERTUMPUK & TOGGLE PASSWORD */
    .input-wrapper { 
        position: relative !important; 
        display: flex !important; 
        align-items: center !important; 
        width: 100% !important;
    }
    .input-wrapper i.input-icon { 
        position: absolute !important; 
        left: 16px !important; 
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: var(--text-muted) !important; 
        font-size: 0.95rem !important; 
        pointer-events: none !important;
        z-index: 10 !important;
    }
    .toggle-password {
        position: absolute !important;
        right: 16px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: var(--text-muted) !important;
        font-size: 0.95rem !important;
        cursor: pointer !important;
        z-index: 10 !important;
        transition: color 0.2s ease;
    }
    .toggle-password:hover {
        color: var(--text-main) !important;
    }
    .form-control {
        width: 100% !important;
        /* Padding kiri 48px untuk icon depan, kanan 48px untuk icon mata */
        padding: 12px 48px 12px 48px !important; 
        background: rgba(15, 23, 42, 0.6) !important;
        border: 1px solid var(--border) !important;
        border-radius: 10px !important;
        color: #fff !important;
        font-size: 0.875rem !important;
        outline: none !important;
    }
    .form-control:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
    }

    .btn-primary {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        border: none;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .alert-danger {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    </style>
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-logo"><i class="fa-solid fa-shield-halved"></i></div>
            <h2 style="font-weight: 700; margin-bottom: 4px;">Selamat Datang</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Masukkan kredensial akun Anda untuk masuk</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username / Email</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="username" class="form-control" required placeholder="Username atau email" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
                    <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 12px;">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
            </button>
        </form>

        <p style="font-size: 0.85rem; text-align: center; margin-top: 24px; color: var(--text-muted);">
            Belum punya akun? <a href="register_baru.php" style="color: #818cf8; font-weight: 600; text-decoration: none;">Daftar di sini</a>
        </p>
    </div>

    <!-- Script Toggle Password -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            // Ubah tipe input antara password dan text
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Ubah icon mata (fa-eye / fa-eye-slash)
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>