<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error   = "";

// 1. Simpan Alamat Baru jika Form Alamat Dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_address') {
    $label          = trim($_POST['label_alamat']);
    $alamat_lengkap = trim($_POST['alamat_lengkap']);
    $rt             = trim($_POST['rt']);
    $rw             = trim($_POST['rw']);
    $kelurahan      = trim($_POST['kelurahan']);
    $kecamatan      = trim($_POST['kecamatan']);
    $kode_pos       = trim($_POST['kode_pos']);
    $patokan        = trim($_POST['patokan']);
    $latitude       = trim($_POST['latitude']);
    $longitude      = trim($_POST['longitude']);

    if (!empty($alamat_lengkap) && !empty($kelurahan) && !empty($kecamatan) && !empty($kode_pos)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO user_addresses (user_id, label_alamat, alamat_lengkap, rt, rw, kelurahan, kecamatan, kode_pos, patokan, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issssssssss", $user_id, $label, $alamat_lengkap, $rt, $rw, $kelurahan, $kecamatan, $kode_pos, $patokan, $latitude, $longitude);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Alamat baru berhasil disimpan!";
        } else {
            $error = "Gagal menyimpan alamat baru.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Mohon lengkapi data alamat utama (Alamat, Kelurahan, Kecamatan, Kode Pos).";
    }
}

// 2. Ambil List Alamat Milik User
$addr_query = mysqli_query($conn, "SELECT * FROM user_addresses WHERE user_id = '$user_id' ORDER BY id DESC");
$addresses  = mysqli_fetch_all($addr_query, MYSQLI_ASSOC);

// Simulasi Total Keranjang (Sesuaikan dengan $_SESSION['cart'] Anda jika ada)
$total_bayar = 150000; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - StoreApp</title>
    
    <!-- CSS Leaflet untuk Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: rgba(79, 70, 229, 0.08);
            --secondary: #10b981;
            --secondary-hover: #059669;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius: 12px;
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.08);
            --shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.1);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); line-height: 1.5; padding-bottom: 50px; }

        .navbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky; top: 0; z-index: 100;
        }
        .brand { font-size: 1.25rem; font-weight: 700; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 16px; }
        .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 500; font-size: 0.9rem; }

        .container { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
        .section-title { font-size: 1.35rem; font-weight: 700; margin-bottom: 16px; color: var(--text-main); }
        .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }

        .checkout-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }

        /* Method Selection Cards */
        .payment-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 12px; }
        .payment-option {
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .payment-option:hover { border-color: var(--primary); background: var(--primary-light); }
        .payment-option input { display: none; }
        .payment-option.active { border-color: var(--primary); background: var(--primary-light); }

        /* Form Inputs */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border-color);
            border-radius: var(--radius); font-size: 0.9rem; outline: none; transition: var(--transition);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* Address Selector */
        .address-box {
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            position: relative;
        }
        .address-box.selected { border-color: var(--primary); background: var(--primary-light); }

        #map { height: 250px; width: 100%; border-radius: var(--radius); margin-top: 8px; border: 1px solid var(--border-color); }
        
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 16px; font-size: 0.875rem; font-weight: 600;
            border-radius: var(--radius); border: none; cursor: pointer; transition: var(--transition);
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; width: 100%; }
        .btn-primary:hover { background: var(--primary-hover); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
        .btn-secondary { background: var(--secondary); color: white; }
        .alert-danger { background: #fef2f2; color: #ef4444; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; }
        .alert-success { background: #ecfdf5; color: #047857; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand"><i class="fa-solid fa-store"></i> StoreApp</a>
        <div class="nav-links">
            <a href="index.php">Beranda</a>
            <a href="logout.php" style="color: var(--danger);">Keluar</a>
        </div>
    </nav>

    <div class="container">
        <h2 class="section-title">Checkout Pesanan</h2>

        <?php if ($message): ?><div class="alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form action="process_checkout.php" method="POST">
            <div class="grid-layout">
                <div>
                    <!-- METODE PEMBAYARAN -->
                    <div class="checkout-card">
                        <h3 style="font-size: 1.05rem; margin-bottom: 12px;"><i class="fa-solid fa-wallet"></i> Pilih Metode Pembayaran</h3>
                        <div class="payment-methods">
                            <label class="payment-option active" onclick="selectPayment(this, 'qris')">
                                <input type="radio" name="payment_method" value="qris" checked>
                                <i class="fa-solid fa-qrcode" style="font-size: 1.5rem; color: var(--primary);"></i>
                                <div style="font-weight: 600; margin-top: 4px; font-size: 0.85rem;">QRIS</div>
                            </label>

                            <label class="payment-option" onclick="selectPayment(this, 'transfer')">
                                <input type="radio" name="payment_method" value="transfer">
                                <i class="fa-solid fa-building-columns" style="font-size: 1.5rem; color: var(--primary);"></i>
                                <div style="font-weight: 600; margin-top: 4px; font-size: 0.85rem;">Transfer Bank</div>
                            </label>

                            <label class="payment-option" onclick="selectPayment(this, 'cod')">
                                <input type="radio" name="payment_method" value="cod">
                                <i class="fa-solid fa-truck" style="font-size: 1.5rem; color: var(--primary);"></i>
                                <div style="font-weight: 600; margin-top: 4px; font-size: 0.85rem;">Dikirim (COD)</div>
                            </label>
                        </div>
                    </div>

                    <!-- PILIH ALAMAT PENGIRIMAN -->
                    <div class="checkout-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3 style="font-size: 1.05rem;"><i class="fa-solid fa-location-dot"></i> Alamat Pengiriman</h3>
                            <button type="button" class="btn btn-secondary" onclick="toggleAddressForm()" style="font-size: 0.75rem; padding: 6px 12px;">
                                <i class="fa-solid fa-plus" style="margin-right: 4px;"></i> Tambah Alamat Baru
                            </button>
                        </div>

                        <!-- Daftar Alamat Tersimpan -->
                        <?php if (!empty($addresses)): ?>
                            <?php foreach ($addresses as $index => $addr): ?>
                                <label class="address-box <?= $index === 0 ? 'selected' : '' ?>">
                                    <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> style="margin-right: 8px;">
                                    <b>[<?= htmlspecialchars($addr['label_alamat']) ?>]</b> <?= htmlspecialchars($addr['alamat_lengkap']) ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        RT <?= htmlspecialchars($addr['rt']) ?> / RW <?= htmlspecialchars($addr['rw']) ?>, Kel. <?= htmlspecialchars($addr['kelurahan']) ?>, Kec. <?= htmlspecialchars($addr['kecamatan']) ?>, <?= htmlspecialchars($addr['kode_pos']) ?>
                                    </div>
                                    <?php if(!empty($addr['patokan'])): ?>
                                        <div style="font-size: 0.78rem; color: var(--primary); margin-top: 2px;">Patokan: <?= htmlspecialchars($addr['patokan']) ?></div>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size: 0.85rem; color: var(--text-muted);">Belum ada alamat tersimpan. Silakan tambah alamat baru di bawah.</p>
                        <?php endif; ?>
                    </div>

                    <!-- FORM TAMBAH ALAMAT BARU (TOGGLE) -->
                    <div class="checkout-card" id="form-address-box" style="display: <?= empty($addresses) ? 'block' : 'none'; ?>;">
                        <h3 style="font-size: 1rem; margin-bottom: 14px; color: var(--primary);">Formulir Alamat Baru</h3>
                        
                        <div class="form-group">
                            <label>Label Alamat (Contoh: Rumah / Kantor)</label>
                            <input type="text" form="form-add-addr" name="label_alamat" placeholder="Rumah" required>
                        </div>

                        <div class="form-group">
                            <label>Alamat Lengkap (Jalan, No. Rumah)</label>
                            <textarea form="form-add-addr" name="alamat_lengkap" rows="2" placeholder="Jl. Raya Kebon Jeruk No. 12" required></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>RT</label>
                                <input type="text" form="form-add-addr" name="rt" placeholder="001">
                            </div>
                            <div class="form-group">
                                <label>RW</label>
                                <input type="text" form="form-add-addr" name="rw" placeholder="005">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Kelurahan</label>
                                <input type="text" form="form-add-addr" name="kelurahan" placeholder="Kelurahan" required>
                            </div>
                            <div class="form-group">
                                <label>Kecamatan</label>
                                <input type="text" form="form-add-addr" name="kecamatan" placeholder="Kecamatan" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Kode Pos</label>
                            <input type="text" form="form-add-addr" name="kode_pos" placeholder="14110" required>
                        </div>

                        <div class="form-group">
                            <label>Patokan Lokasi</label>
                            <input type="text" form="form-add-addr" name="patokan" placeholder="Dekat masjid / Sebelah toko kelontong">
                        </div>

                        <div class="form-group">
                            <label>Titik Koordinat (Pilih pada Peta)</label>
                            <div id="map"></div>
                            <div class="form-row" style="margin-top: 8px;">
                                <input type="text" form="form-add-addr" id="lat" name="latitude" placeholder="Latitude" readonly>
                                <input type="text" form="form-add-addr" id="lng" name="longitude" placeholder="Longitude" readonly>
                            </div>
                        </div>

                        <button type="submit" form="form-add-addr" class="btn btn-secondary" style="width: 100%;">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Alamat Ini
                        </button>
                    </div>
                </div>

                <!-- RINGKASAN PESANAN -->
                <div>
                    <div class="checkout-card" style="position: sticky; top: 90px;">
                        <h3 style="font-size: 1.05rem; margin-bottom: 16px;">Ringkasan Belanja</h3>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px;">
                            <span>Total Harga</span>
                            <b>Rp <?= number_format($total_bayar, 0, ',', '.') ?></b>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 16px;">
                            <span>Biaya Pengiriman</span>
                            <span style="color: var(--secondary); font-weight: 600;">GRATIS</span>
                        </div>
                        <hr style="border: none; border-top: 1px dashed var(--border-color); margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 700; margin-bottom: 20px;">
                            <span>Total Bayar</span>
                            <span style="color: var(--primary);">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Buat Pesanan Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form terpisah untuk submit Alamat Baru -->
        <form id="form-add-addr" method="POST">
            <input type="hidden" name="action" value="add_address">
        </form>
    </div>

    <!-- Scripts Leaflet Maps & Form Interactivity -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function selectPayment(element, method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
            element.classList.add('active');
        }

        function toggleAddressForm() {
            const formBox = document.getElementById('form-address-box');
            if (formBox.style.display === 'none' || formBox.style.display === '') {
                formBox.style.display = 'block';
                setTimeout(() => { map.invalidateSize(); }, 300); // Reload Leaflet Map Rendering
            } else {
                formBox.style.display = 'none';
            }
        }

        // Inisialisasi Peta (Default Lokasi: Jakarta)
        const defaultLat = -6.175392;
        const defaultLng = 106.827153;
        const map = L.map('map').setView([defaultLat, defaultLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

        function updateCoords(lat, lng) {
            document.getElementById('lat').value = lat.toFixed(6);
            document.getElementById('lng').value = lng.toFixed(6);
        }

        updateCoords(defaultLat, defaultLng);

        // Update koordinat saat marker digeser
        marker.on('dragend', function (e) {
            const latLng = marker.getLatLng();
            updateCoords(latLng.lat, latLng.lng);
        });

        // Update marker saat peta diklik
        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });
    </script>
</body>
</html>