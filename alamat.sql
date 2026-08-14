-- Tabel Alamat Pengguna (Mendukung Multi-alamat)
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label_alamat VARCHAR(50) DEFAULT 'Rumah', -- Contoh: Rumah, Kantor, Kos
    alamat_lengkap TEXT NOT NULL,
    rt VARCHAR(10),
    rw VARCHAR(10),
    kelurahan VARCHAR(100) NOT NULL,
    kecamatan VARCHAR(100) NOT NULL,
    kode_pos VARCHAR(10) NOT NULL,
    patokan TEXT,
    latitude VARCHAR(50),
    longitude VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Transaksi Pesanan
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NULL,
    payment_method ENUM('qris', 'transfer', 'cod') NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Proses', 'Selesai', 'Batal') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);