<?php
require_once 'config.php';

// Pastikan fungsi check_login() aktif
if (function_exists('check_login')) {
    check_login();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['user_id'])) {
        exit();
    }
}


$user_id = $_SESSION['user_id'];

// 1. Proses Tambah Alamat Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_address') {
    $label    = $_POST['label_alamat'] ?? 'Rumah';
    $alamat   = $_POST['alamat_lengkap'] ?? '';
    $rt       = $_POST['rt'] ?? '';
    $rw       = $_POST['rw'] ?? '';
    $kel      = $_POST['kelurahan'] ?? '';
    $kec      = $_POST['kecamatan'] ?? '';
    $kodepos  = $_POST['kodepos'] ?? '';
    $lat_lng  = $_POST['maps_lat_lng'] ?? '';
    $patokan  = $_POST['patokan'] ?? '';

    $stmt_addr = mysqli_prepare($conn, "INSERT INTO user_addresses (user_id, label_alamat, alamat_lengkap, rt, rw, kelurahan, kecamatan, kodepos, maps_lat_lng, patokan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_addr, "isssssssss", $user_id, $label, $alamat, $rt, $rw, $kel, $kec, $kodepos, $lat_lng, $patokan);
    mysqli_stmt_execute($stmt_addr);
    
    header("Location: index.php?status=address_added");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $total       = (int)$_POST['total_harga'];
    $cart_json   = $_POST['cart_data'] ?? '[]';
    $cart_items  = json_decode($cart_json, true);
    $metode_pem  = $_POST['metode_pembayaran'] ?? 'QRIS';
    $address_id  = (!empty($_POST['address_id'])) ? (int)$_POST['address_id'] : NULL;


    if ($total > 0 && !empty($cart_items)) {
        // Simpan ke tabel orders
        $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, kode_invoice, total_harga, metode_pembayaran, address_id, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmt, "isisi", $user_id, $invoice, $total, $metode_pem, $address_id);
        if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_execute($stmt)) {
            $order_id = mysqli_insert_id($conn);

            // Simpan detail produk ke order_items
            $stmt_item = mysqli_prepare($conn, "INSERT INTO order_items (order_id, nama_produk, harga, qty) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $nama  = $item['nama'];
                $harga = (int)$item['harga'];
                $qty   = (int)($item['qty'] ?? 1);
                mysqli_stmt_bind_param($stmt_item, "isii", $order_id, $nama, $harga, $qty);
                mysqli_stmt_execute($stmt_item);
            }

            exit();
        }
    }
}


// Data Pendukung
$products    = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$orders      = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet OpenStreetMap (Bebas Biaya / Gratis tanpa API Key) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding-bottom: 60px; }
        /* Navbar */
        /* Navbar */
        .navbar { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 16px 32px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .brand { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .brand i { color: #6366f1; }
        .nav-links { display: flex; align-items: center; gap: 20px; font-size: 0.9rem; }
        .nav-links a.admin-link { color: #818cf8; font-weight: 600; text-decoration: none; padding: 6px 12px; background: rgba(99, 102, 241, 0.15); border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.3); }
        .btn-logout { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; }

        /* Container & Grid */
        .container { max-width: 1200px; margin: 32px auto; padding: 0 20px; }
        .section-title { font-size: 1.35rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: #f1f5f9; }
        .section-title i { color: #818cf8; }
        .grid-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; }
        @media (max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }

        /* Product & Cart Cards */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .product-card { background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .product-cat { font-size: 0.75rem; text-transform: uppercase; color: #818cf8; font-weight: 600; background: rgba(99, 102, 241, 0.1); padding: 3px 8px; border-radius: 6px; display: inline-block; margin-bottom: 10px; }
        .product-title { font-size: 1.05rem; font-weight: 600; margin-bottom: 8px; }
        .product-price { font-size: 1.15rem; font-weight: 700; color: #38bdf8; margin-bottom: 16px; }
        .btn-add-cart { width: 100%; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; color: white; padding: 10px; border-radius: 10px; font-weight: 600; font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .cart-card { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 24px; position: sticky; top: 90px; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-checkout { width: 100%; padding: 12px; background: #10b981; border: none; border-radius: 10px; color: white; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-checkout:disabled { background: #334155; color: #64748b; cursor: not-allowed; }

        /* Modal Popup */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-card { background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.1); width: 100%; max-width: 600px; border-radius: 16px; padding: 24px; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px; }
        .form-control { width: 100%; background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; padding: 10px 14px; border-radius: 8px; font-size: 0.9rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* Payment Option Cards */
        .payment-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .payment-card { background: #0f172a; border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 12px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .payment-card.active { border-color: #6366f1; background: rgba(99, 102, 241, 0.1); }
        .payment-card i { font-size: 1.5rem; margin-bottom: 6px; color: #818cf8; }

        /* Table */
        .table-card { background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; overflow: hidden; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th { background: rgba(15, 23, 42, 0.6); padding: 14px 20px; color: #94a3b8; }
        td { padding: 14px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>

    <nav class="navbar">
    <nav class="navbar">
        <div class="nav-links">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="admin-link"><i class="fa-solid fa-gauge"></i> Panel Admin</a>
            <?php endif; ?>
            <span>Halo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong></span>
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">
    <div class="container">
        <!-- Banner Success Notification -->
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between;">
                <div><strong>Pesanan Berhasil Dibuat!</strong><br><small>Nomor Invoice: <strong><?= htmlspecialchars($_GET['invoice'] ?? '') ?></strong></small></div>
                <a href="index.php" style="color: #34d399; text-decoration: none; font-size: 1.2rem;">&times;</a>
            </div>
        <?php endif; ?>
        <div class="grid-layout">
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
                                </div>
                                    <i class="fa-solid fa-cart-plus"></i> + Keranjang
                                </button>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>


            <div>
                <div class="cart-card">
                <div class="cart-card">
                <div class="cart-card">
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: 16px; padding-top: 16px;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 16px;">
                        </div>
                        </div>
                        <button class="btn-checkout" id="btn-checkout" onclick="openCheckoutModal()" disabled>
                    </div>
                </div>
            </div>
        </div>



        <div class="table-card">
            <table>
                <thead>
                    <tr>
                    <tr>
                    <tr>
                        <th>Invoice</th>
                        <th>Detail Item</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
                        <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><strong style="color: #818cf8;"><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
                                <td><strong style="color: #818cf8;"><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
                                <td><strong style="color: #818cf8;"><?= htmlspecialchars($o['kode_invoice']) ?></strong></td>
                                <td>
                                    <?php 
                                    $o_id = $o['id'];
                                    $items_query = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = '$o_id'");
                                    while ($it = mysqli_fetch_assoc($items_query)) {
                                        echo '<small style="color:#94a3b8;">• ' . htmlspecialchars($it['nama_produk']) . ' (x' . $it['qty'] . ')</small><br>';
                                    }
                                    ?>
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
    </div>
    </div>

    <!-- MODAL POPUP CHECKOUT & PEMBAYARAN -->
    <div class="modal-overlay" id="checkout-modal">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Pilihan Pembayaran & Pengiriman</h3>
                <span onclick="closeCheckoutModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
            </div>

            <form method="POST" id="form-checkout">
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="total_harga" id="modal-input-total">
                <input type="hidden" name="cart_data" id="modal-input-cart">
                <input type="hidden" name="metode_pembayaran" id="input-metode" value="QRIS">

                <label style="font-size: 0.85rem; color: #94a3b8;">Pilih Metode Pembayaran:</label>
                <div class="payment-options" style="margin-top: 8px;">
                    <div class="payment-card active" onclick="selectPayment('QRIS', this)">
                        <i class="fa-solid fa-qrcode"></i>
                        <div style="font-size: 0.85rem; font-weight: 600;">QRIS</div>
                    </div>
                    <div class="payment-card" onclick="selectPayment('Transfer', this)">
                        <i class="fa-solid fa-building-columns"></i>
                        <div style="font-size: 0.85rem; font-weight: 600;">Transfer</div>
                    </div>
                    <div class="payment-card" onclick="selectPayment('Dikirim', this)">
                        <i class="fa-solid fa-truck-fast"></i>
                        <div style="font-size: 0.85rem; font-weight: 600;">Dikirim</div>
                    </div>
                </div>

                <!-- Bagian Opsi Alamat (Hanya tampil jika memilih 'Dikirim') -->
                <div id="section-alamat" style="display: none; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 16px; margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-size: 0.9rem; font-weight: 600; color: #f8fafc;">Pilih Alamat Pengiriman:</label>
                        <button type="button" onclick="openAddressModal()" style="background: rgba(99,102,241,0.2); color: #818cf8; border: 1px solid #6366f1; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">+ Tambah Alamat Baru</button>
                    </div>

                    <select name="address_id" id="address-select" class="form-control" style="margin-bottom: 12px;">
                        <?php if (mysqli_num_rows($addresses) > 0): ?>
                            <?php while ($addr = mysqli_fetch_assoc($addresses)): ?>
                                <option value="<?= $addr['id'] ?>">
                                    [<?= htmlspecialchars($addr['label_alamat']) ?>] <?= htmlspecialchars($addr['alamat_lengkap']) ?>, RT <?= htmlspecialchars($addr['rt']) ?>/RW <?= htmlspecialchars($addr['rw']) ?>, Kel. <?= htmlspecialchars($addr['kelurahan']) ?>, Kec. <?= htmlspecialchars($addr['kecamatan']) ?> (<?= htmlspecialchars($addr['kodepos']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">-- Belum ada alamat tersimpan --</option>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn-checkout" style="margin-top: 20px;">Konfirmasi & Buat Pesanan</button>
            </form>
        </div>
    </div>

    <!-- MODAL POPUP TAMBAH ALAMAT BARU -->
    <div class="modal-overlay" id="address-modal" style="z-index: 1001;">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3>Tambah Alamat Pengiriman Baru</h3>
                <span onclick="closeAddressModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="add_address">
                <div class="form-group">
                    <label>Label Alamat (Contoh: Rumah, Kantor, Kos)</label>
                    <input type="text" name="label_alamat" class="form-control" placeholder="Rumah" required>
                </div>
                <div class="form-group">
                    <label>Alamat Lengkap (Jalan, No. Rumah)</label>
                    <textarea name="alamat_lengkap" class="form-control" rows="2" placeholder="Jl. Mawar No. 12" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>RT</label>
                        <input type="text" name="rt" class="form-control" placeholder="001">
                    </div>
                    <div class="form-group">
                        <label>RW</label>
                        <input type="text" name="rw" class="form-control" placeholder="002">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kelurahan</label>
                        <input type="text" name="kelurahan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kecamatan</label>
                        <input type="text" name="kecamatan" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kodepos</label>
                    <input type="text" name="kodepos" class="form-control" required>
                </div>
                
                <!-- Peta Interaktif Maps untuk Titik Koordinat -->
                <div class="form-group">
                    <label>Titik Koordinat Lokasi (Klik lokasi Anda di peta):</label>
                    <div id="map" style="height: 200px; border-radius: 8px; margin-bottom: 8px;"></div>
                    <input type="text" name="maps_lat_lng" id="maps_lat_lng" class="form-control" placeholder="-6.200000, 106.816666" readonly>
                </div>

                <div class="form-group">
                    <label>Patokan Lokasi</label>
                    <input type="text" name="patokan" class="form-control" placeholder="Samping toko kelontong pagar hitam">
                </div>

                <button type="submit" class="btn-checkout">Simpan Alamat Ini</button>
            </form>

    <script>
        let cart = [];
        let cart = [];

        function addToCart(nama, harga) {
        function addToCart(nama, harga) {
        function addToCart(nama, harga) {
            const existing = cart.find(item => item.nama === nama);
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
            let total = 0;
            let total = 0;
            let total = 0;
            cart.forEach((item, index) => {
            cart.forEach((item, index) => {
                total += (item.harga * item.qty);
            });

            listEl.innerHTML = html;
            totalEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            inputTotal.value = total;
            btnCheckout.disabled = false;
        }





        /* Modal Handlers */
        function openCheckoutModal() {
            let total = cart.reduce((acc, item) => acc + (item.harga * item.qty), 0);
            document.getElementById('modal-input-total').value = total;
            document.getElementById('modal-input-cart').value = JSON.stringify(cart);
            document.getElementById('checkout-modal').style.display = 'flex';
        }

        function closeCheckoutModal() {
            document.getElementById('checkout-modal').style.display = 'none';
        }

        function selectPayment(metode, element) {
            document.querySelectorAll('.payment-card').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('input-metode').value = metode;

            const sectionAlamat = document.getElementById('section-alamat');
            if (metode === 'Dikirim') {
                sectionAlamat.style.display = 'block';
            } else {
                sectionAlamat.style.display = 'none';
            }
        }

        function openAddressModal() {
            document.getElementById('address-modal').style.display = 'flex';
            setTimeout(initMap, 300); // Inisialisasi peta saat modal terbuka
        }

        function closeAddressModal() {
            document.getElementById('address-modal').style.display = 'none';
        }

        /* Inisialisasi OpenStreetMap gratis */
        function initMap() {
            if (map) return;
            const defaultLat = -6.200000, defaultLng = 106.816666; // Jakarta Center
            map = L.map('map').setView([defaultLat, defaultLng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            map.on('click', function(e) {
                const lat = e.latlng.lat.toFixed(6);
                const lng = e.latlng.lng.toFixed(6);
        }
    </script>
    <!-- Container Pop-up Toast -->
    <div id="toast-notification" class="toast">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toast-message">Produk berhasil ditambahkan!</span>
    </div>
</body>
</html>