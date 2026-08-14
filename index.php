<?php
require_once 'config.php';

// Pastikan fungsi check_login() aktif
if (function_exists('check_login')) {
    check_login();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login_baru.php");
        exit();
    }
}

// Proses Tambah Order/Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $user_id = $_SESSION['user_id'];
    $total   = (int)$_POST['total_harga'];
    $invoice = "INV-" . date("Ymd") . "-" . rand(1000, 9999);

    if ($total > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, kode_invoice, total_harga, status) VALUES (?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $invoice, $total);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Pesanan berhasil dibuat! Invoice: $invoice'); window.location='index.php';</script>";
            exit();
        }
    }
}

// Ambil Produk dari Database
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");

// Ambil Riwayat Transaksi User (Aman dengan Prepared Statement)
$user_id = $_SESSION['user_id'];
$stmt_orders = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt_orders, "i", $user_id);
mysqli_stmt_execute($stmt_orders);
$orders = mysqli_stmt_get_result($stmt_orders);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon SVG berbentuk Toko/Bag tanpa perlu simpan file gambar -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛍️</text></svg>">
    <title>WarungKode - Katalog Produk Modern</title>
    <!-- Google Font & FontAwesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* Navbar */
        .navbar {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand i {
            color: #6366f1;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.9rem;
        }

        .nav-links a.admin-link {
            color: #818cf8;
            font-weight: 600;
            text-decoration: none;
            padding: 6px 12px;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            transition: all 0.3s;
        }

        .nav-links a.admin-link:hover {
            background: rgba(99, 102, 241, 0.3);
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.4);
            color: #fff;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f1f5f9;
        }

        .section-title i {
            color: #818cf8;
        }

        /* Grid Layout */
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
        }

        @media (max-width: 900px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Product Grid & Card Fixes */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        /* Style Tambahan untuk Gambar Produk */
        .product-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 12px;
            background: #1e293b;
        }

        .product-cat {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #818cf8;
            font-weight: 600;
            background: rgba(99, 102, 241, 0.1);
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #f8fafc;
        }

        .product-price {
            font-size: 1.15rem;
            font-weight: 700;
            color: #38bdf8;
            margin-bottom: 16px;
        }

        .btn-add-cart {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-add-cart:hover {
            opacity: 0.9;
            transform: scale(0.98);
        }

        /* Cart Card */
        .cart-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            position: sticky;
            top: 90px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .btn-checkout {
            width: 100%;
            padding: 12px;
            background: #10b981;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-checkout:disabled {
            background: #334155;
            color: #64748b;
            cursor: not-allowed;
        }

        .btn-checkout:not(:disabled):hover {
            background: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* Table Area */
        .table-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            background: rgba(15, 23, 42, 0.6);
            padding: 14px 20px;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-Pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-Selesai, .badge-Paid {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fa-solid fa-store"></i> Warung Kode
        </a>
        <div class="nav-links">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="admin-link"><i class="fa-solid fa-gauge"></i> Panel Admin</a>
            <?php endif; ?>
            <span>Halo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong></span>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
        <div class="grid-layout">
            <!-- Kolom Produk -->
            <div>
                <h2 class="section-title"><i class="fa-solid fa-boxes-stacked"></i> Katalog Produk</h2>
                <div class="product-grid">
                    <?php if ($products && mysqli_num_rows($products) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($products)): ?>
                            <?php 
                                // PERBAIKAN: Menentukan path lokasi file gambar
                                // Silakan sesuaikan nama kolom gambar di DB ('gambar' / 'image')
                                $nama_file_gambar = $p['gambar'] ?? $p['image'] ?? '';
                                
                                // Jika ada gambarnya, arahkan ke folder 'uploads/'
                                if (!empty($nama_file_gambar)) {
                                    $img_src = 'uploads/' . $nama_file_gambar;
                                } else {
                                    $img_src = 'https://via.placeholder.com/300x200/1e293b/818cf8?text=No+Image';
                                }
                            ?>
                            <div class="product-card">
                                <div>
                                    <!-- TAG GAMBAR DITAMBAHKAN DI SINI -->
                                    <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" class="product-img">
                                    
                                    <span class="product-cat"><?= htmlspecialchars($p['kategori'] ?? 'Umum') ?></span>
                                    <h3 class="product-title"><?= htmlspecialchars($p['nama_produk']) ?></h3>
                                    <div class="product-price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
                                </div>
                                <button type="button" class="btn-add-cart" onclick="addToCart('<?= addslashes(htmlspecialchars($p['nama_produk'])) ?>', <?= (int)preg_replace('/[^0-9]/', '', $p['harga']) ?>)">
                                    <i class="fa-solid fa-cart-plus"></i> + Keranjang
                                </button>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #94a3b8; grid-column: 1/-1;">Belum ada produk tersedia.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Keranjang Belanja -->
            <div>
                <div class="cart-card">
                    <h3 style="font-size: 1.1rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-cart-shopping" style="color: #818cf8;"></i> Keranjang Belanja
                    </h3>
                    <div id="cart-list">
                        <p style="color: #64748b; font-size: 0.85rem; text-align: center; padding: 12px 0;">Keranjang kosong.</p>
                    </div>
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: 16px; padding-top: 16px;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 16px;">
                            <span>Total:</span>
                            <span id="cart-total" style="color: #38bdf8;">Rp 0</span>
                        </div>
                        <form method="POST" id="checkout-form">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="total_harga" id="input-total" value="0">
                            <button type="submit" class="btn-checkout" id="btn-checkout" disabled>
                                <i class="fa-solid fa-credit-card"></i> Checkout Pesanan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Pembelian -->
        <h2 class="section-title" style="margin-top: 48px;"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Transaksi Saya</h2>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Kode Invoice</th>
                        <th>Tanggal</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
                        <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><strong style="color: #818cf8;"><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
                                <td><?= !empty($o['tanggal_order']) ? date("d/m/Y H:i", strtotime($o['tanggal_order'])) : '-' ?></td>
                                <td>Rp <?= number_format($o['total_harga'], 0, ',', '.') ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 24px;">Belum ada riwayat transaksi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart(nama, harga) {
            // Konversi harga ke angka bulat (number)
            const parsedHarga = Number(harga) || 0;

            // Cek apakah produk sudah ada di keranjang (jika ada, cukup update/tambah)
            cart.push({ nama: nama, harga: parsedHarga });
            
            updateCartUI();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function updateCartUI() {
            const listEl = document.getElementById('cart-list');
            const totalEl = document.getElementById('cart-total');
            const inputTotal = document.getElementById('input-total');
            const btnCheckout = document.getElementById('btn-checkout');

            if (!listEl || !totalEl || !inputTotal || !btnCheckout) return;

            if (cart.length === 0) {
                listEl.innerHTML = `<p style="color: #64748b; font-size: 0.85rem; text-align: center; padding: 12px 0;">Keranjang kosong.</p>`;
                totalEl.innerText = 'Rp 0';
                inputTotal.value = 0;
                btnCheckout.disabled = true;
                return;
            }

            let html = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                total += item.harga;
                html += `
                    <div class="cart-item">
                        <div>
                            <strong style="font-size: 0.9rem;">${escapeHtml(item.nama)}</strong><br>
                            <small style="color: #818cf8;">Rp ${item.harga.toLocaleString('id-ID')}</small>
                        </div>
                        <button type="button" onclick="removeFromCart(${index})" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size: 1.1rem; padding: 4px;">&times;</button>
                    </div>
                `;
            });

            listEl.innerHTML = html;
            totalEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            inputTotal.value = total;
            btnCheckout.disabled = false;
        }

        // Helper untuk mengamankan teks di JS
        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>