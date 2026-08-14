<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------
// 1. OTENTIKASI & PROTEKSI AKSES
// ----------------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_baru.php");
    exit();
}

$message = "";
$error   = "";

// Helper Function: Handle Upload Gambar
function handleUploadImage($file) {
    if (isset($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($file['type'], $allowedTypes)) {
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName   = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadDir = 'uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                return $newName;
            }
        }
    }
    return null;
}

// ----------------------------------------------------------------------
// 2. PEMROSESAN FORM (POST & GET)
// ----------------------------------------------------------------------

// A. Tambah Produk Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $nama     = mysqli_real_escape_string($conn, trim($_POST['nama_produk']));
    $kategori = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $harga    = (int)$_POST['harga'];
    $gambar   = handleUploadImage($_FILES['gambar']);

    if (!empty($nama) && $harga > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (nama_produk, kategori, harga, gambar) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssis", $nama, $kategori, $harga, $gambar);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Produk berhasil ditambahkan!";
        } else {
            $error = "Gagal menambah produk ke database.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Mohon isi nama dan harga produk dengan benar.";
    }
}
 
// B. Edit Produk (LAMA)
/* if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $id       = (int)$_POST['product_id'];
    $nama     = mysqli_real_escape_string($conn, trim($_POST['nama_produk']));
    $kategori = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $harga    = (int)$_POST['harga'];
    
    $newGambar = handleUploadImage($_FILES['gambar']);

    if ($id > 0 && !empty($nama) && $harga > 0) {
        if ($newGambar) {
            $stmt = mysqli_prepare($conn, "UPDATE products SET nama_produk = ?, kategori = ?, harga = ?, gambar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssisi", $nama, $kategori, $harga, $newGambar, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE products SET nama_produk = ?, kategori = ?, harga = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssii", $nama, $kategori, $harga, $id);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = "Data produk berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui data produk.";
        }
        mysqli_stmt_close($stmt);
    }
}
*/

// B. Edit Produk (BARU)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $id       = (int)$_POST['product_id'];
    $nama     = trim($_POST['nama_produk']);
    $kategori = trim($_POST['kategori']);
    $harga    = (int)$_POST['harga'];
    
    $newGambar = handleUploadImage($_FILES['gambar']);

    if ($id > 0 && !empty($nama) && $harga > 0) {
        if ($newGambar) {
            // Mengubah tabel 'products' dan kolom 'gambar'
            $sql = "UPDATE products SET nama_produk = ?, kategori = ?, harga = ?, gambar = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                die("Query Gagal (Dengan Gambar): " . mysqli_error($conn));
            }
            
            // Param: s (string), s (string), i (integer), s (string), i (integer)
            mysqli_stmt_bind_param($stmt, "ssisi", $nama, $kategori, $harga, $newGambar, $id);
        } else {
            $sql = "UPDATE products SET nama_produk = ?, kategori = ?, harga = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                die("Query Gagal (Tanpa Gambar): " . mysqli_error($conn));
            }
            
            // Param: s (string), s (string), i (integer), i (integer)
            mysqli_stmt_bind_param($stmt, "ssii", $nama, $kategori, $harga, $id);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = "Data produk berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui data: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}


// C. Hapus Produk
if (isset($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header("Location: admin.php");
    exit();
}

// D. Update Status Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $status   = mysqli_real_escape_string($conn, $_POST['status']);

    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Status pesanan berhasil diperbarui!";
    }
    mysqli_stmt_close($stmt);
}

// ----------------------------------------------------------------------
// 3. QUERY DATA & STATISTIK DASHBOARD
// ----------------------------------------------------------------------

// Filter Pencarian
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where_clause = $search != '' ? "WHERE nama_produk LIKE '%$search%' OR kategori LIKE '%$search%'" : '';

// Statistik
$total_produk_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
$total_produk     = mysqli_fetch_assoc($total_produk_res)['total'] ?? 0;

$total_order_res  = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(total_harga) as omset FROM orders");
$order_stat       = mysqli_fetch_assoc($total_order_res);
$total_orders     = $order_stat['total'] ?? 0;
$total_omset      = $order_stat['omset'] ?? 0;

// Fetch Data Tabel
$products = mysqli_query($conn, "SELECT * FROM products $where_clause ORDER BY id DESC");
$orders   = mysqli_query($conn, "SELECT orders.*, users.username FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StoreApp</title>
    
    <!-- Typography & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: rgba(23, 32, 51, 0.6);
            --border: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); min-height: 100vh; padding-bottom: 60px; }

        /* Navigation */
        .navbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;

<!-- Tambahkan di .nav-actions pada admin.php -->
<a href="export_excel.php" class="btn-link" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
    <i class="fa-solid fa-file-excel"></i> Ekspor Excel
</a>

<a href="export_csv.php" class="btn-link">
    <i class="fa-solid fa-file-csv"></i> Ekspor CSV
</a>
        }
        .brand { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .brand-badge { background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); color: #818cf8; font-size: 0.75rem; padding: 3px 8px; border-radius: 6px; }

        .nav-actions { display: flex; gap: 12px; align-items: center; }
        .btn-link { color: #cbd5e1; text-decoration: none; font-size: 0.875rem; padding: 8px 16px; border-radius: 8px; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; background: transparent; border: none; cursor: pointer; }
        .btn-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .btn-danger { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.3); color: #fff; }

        /* Layout Structure */
        .container { max-width: 1280px; margin: 32px auto; padding: 0 24px; }
        .grid-main { display: grid; grid-template-columns: 360px 1fr; gap: 28px; }
        @media (max-width: 960px) { .grid-main { grid-template-columns: 1fr; } }

        /* Metrics Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .stat-icon.blue { background: rgba(56, 189, 248, 0.12); color: #38bdf8; }
        .stat-icon.indigo { background: rgba(99, 102, 241, 0.12); color: #818cf8; }
        .stat-icon.emerald { background: rgba(16, 185, 129, 0.12); color: #34d399; }
        .stat-label { font-size: 0.75rem; color: var(--text-sub); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #f8fafc; margin-top: 4px; }

        /* UI Cards & Forms */
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 10px; color: #f1f5f9; }
        .section-title i { color: #818cf8; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 11px 14px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.875rem; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }

        .btn-primary { width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-primary:hover { opacity: 0.95; transform: translateY(-1px); }

        /* Data Tables */
        .table-responsive { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; }
        th { background: rgba(15, 23, 42, 0.8); padding: 14px 18px; color: var(--text-sub); font-weight: 600; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 14px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.04); color: #e2e8f0; vertical-align: middle; }

        /* Item Components */
        .thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: rgba(255,255,255,0.05); border: 1px solid var(--border); }
        .thumb-empty { width: 44px; height: 44px; border-radius: 8px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; color: var(--text-sub); }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .status-Pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-Proses  { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .status-Selesai { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-Batal   { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }

        .btn-action { padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn-action.edit { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border-color: rgba(245, 158, 11, 0.25); }
        .btn-action.delete { background: rgba(239, 68, 68, 0.12); color: #fca5a5; border-color: rgba(239, 68, 68, 0.25); }
        .btn-action.edit:hover { background: rgba(245, 158, 11, 0.25); }
        .btn-action.delete:hover { background: rgba(239, 68, 68, 0.25); }

        .alert { background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 0.875rem; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }

        /* Modal Overlay */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); align-items: center; justify-content: center; z-index: 1000; }
        .modal-body { background: #131c2e; border: 1px solid var(--border); border-radius: 20px; padding: 28px; width: 100%; max-width: 480px; position: relative; }
        .modal-close { position: absolute; top: 20px; right: 24px; color: var(--text-sub); font-size: 1.25rem; cursor: pointer; }

        @media print {
            .navbar, .stats-grid, .grid-main, .no-print { display: none !important; }
            body { background: #fff; color: #000; }
            .table-responsive { border: none; background: transparent; }
            th, td { color: #000 !important; border-bottom: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>

    <!-- Navigation Header -->
    <nav class="navbar">
        <a href="admin.php" class="brand">
            <i class="fa-solid fa-shield-halved" style="color: #818cf8;"></i> StoreApp <span class="brand-badge">Executive</span>
        </a>
        <div class="nav-actions">
            <a href="index.php" class="btn-link"><i class="fa-solid fa-store"></i> Katalog Toko</a>
            <button onclick="window.print()" class="btn-link no-print"><i class="fa-solid fa-print"></i> Cetak Laporan</button>
            <a href="logout.php" class="btn-link btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
        
        <!-- Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-box-archive"></i></div>
                <div>
                    <div class="stat-label">Total Produk</div>
                    <div class="stat-value"><?= number_format($total_produk) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon indigo"><i class="fa-solid fa-receipt"></i></div>
                <div>
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-value"><?= number_format($total_orders) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon emerald"><i class="fa-solid fa-vault"></i></div>
                <div>
                    <div class="stat-label">Total Omset</div>
                    <div class="stat-value">Rp <?= number_format($total_omset, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
        <?php if ($message): ?>
            <div class="alert"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Main Workspace -->
        <div class="grid-main">
            
            <!-- Left Panel: Form Tambah Produk -->
            <div>
                <div class="card">
                    <h3 class="section-title"><i class="fa-solid fa-square-plus"></i> Tambah Produk</h3>
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="form-group">
                            <label>Nama Produk</label>
                            <input type="text" name="nama_produk" class="form-control" required placeholder="Kemeja Custom">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <input type="text" name="kategori" class="form-control" required placeholder="Pakaian">
                        </div>
                        <div class="form-group">
                            <label>Harga (Rp)</label>
                            <input type="number" name="harga" class="form-control" required placeholder="150000">
                        </div>
                        <div class="form-group">
                            <label>Gambar Produk</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                        </div>
                        
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Produk</button>
                    </form>
                </div>
            </div>

            <!-- Right Panel: Data Produk -->
            <div>
                <div class="section-header">
                    <h3 class="section-title"><i class="fa-solid fa-boxes-stacked"></i> Kelola Inventaris</h3>
                    <form method="GET" style="display: flex; gap: 8px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>" style="padding: 6px 12px;">
                        <button type="submit" class="btn-action edit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Detail Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products && mysqli_num_rows($products) > 0): ?>
                                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($p['gambar']) && file_exists('uploads/' . $p['gambar'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($p['gambar']) ?>" class="thumb" alt="Preview">
                                            <?php else: ?>
                                                <div class="thumb-empty"><i class="fa-solid fa-image"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['nama_produk']) ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-sub);">ID: #<?= $p['id'] ?></div>
                                        </td>
                                        <td><span style="background: rgba(255,255,255,0.06); padding: 3px 8px; border-radius: 6px; font-size: 0.75rem;"><?= htmlspecialchars($p['kategori']) ?></span></td>
                                        <td><strong>Rp <?= number_format($p['harga'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <button class="btn-action edit" onclick="openEditModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_produk'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['kategori'], ENT_QUOTES) ?>', <?= $p['harga'] ?>)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <a href="admin.php?delete_product=<?= $p['id'] ?>" class="btn-action delete" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; color: var(--text-sub); padding: 24px;">Tidak ada data produk ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Full Width Panel: Monitoring Pesanan -->
        <div style="margin-top: 48px;">
            <div class="section-header">
                <h3 class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Monitoring Pesanan Masuk</h3>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Pelanggan</th>
                            <th>Waktu Transaksi</th>
                            <th>Total Belanja</th>
                            <th>Status Saat Ini</th>
                            <th>Pembaruan Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
                            <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                                <tr>
                                    <td><strong style="color: #818cf8;"><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
                                    <td><?= htmlspecialchars($o['username']) ?></td>
                                    <td><?= !empty($o['tanggal_order']) ? date("d M Y - H:i", strtotime($o['tanggal_order'])) : '-' ?></td>
                                    <td><strong>Rp <?= number_format($o['total_harga'], 0, ',', '.') ?></strong></td>
                                    <td>
                                        <span class="badge status-<?= htmlspecialchars($o['status']) ?>">
                                            <?= htmlspecialchars($o['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 8px; align-items: center;" class="no-print">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="status" class="form-control" style="padding: 5px 8px; font-size: 0.8rem; width: auto;">
                                                <option value="Pending" <?= $o['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Proses" <?= $o['status'] === 'Proses' ? 'selected' : '' ?>>Proses</option>
                                                <option value="Selesai" <?= $o['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                <option value="Batal" <?= $o['status'] === 'Batal' ? 'selected' : '' ?>>Batal</option>
                                            </select>
                                            <button type="submit" class="btn-action edit"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; color: var(--text-sub); padding: 24px;">Belum ada pesanan yang masuk.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Edit Produk -->
    <div id="editModal" class="modal">
        <div class="modal-body">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h3 class="section-title"><i class="fa-solid fa-pen-to-square"></i> Edit Data Produk</h3>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="product_id" id="edit_id">
                
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="nama_produk" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="kategori" id="edit_kategori" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ganti Gambar (Opsional)</label>
                    <input type="file" name="gambar" class="form-control" accept="image/*">
                </div>
                
                <button type="submit" class="btn-primary"><i class="fa-solid fa-arrows-rotate"></i> Perbarui Data</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, nama, kategori, harga) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kategori').value = kategori;
            document.getElementById('edit_harga').value = harga;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>