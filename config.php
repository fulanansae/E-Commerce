<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi Otomatis Environment (Lokal vs Hosting)
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Settingan untuk XAMPP Laptop Kamu
    $host = "localhost";
    $user = "root";
    $pass = "";                  // Default XAMPP biasanya kosong
    $db   = "db_ecommerce2";     // Nama database di phpMyAdmin lokal kamu
} else {
    // Settingan untuk InfinityFree
    $host = "sql106.infinityfree.com";
    $user = "if0_42652939";
    $pass = "Cobac0ba26";
    $db   = "if0_42652939_db_ecommerce2";
}

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        // Pastikan nama file login kamu di folder BENAR-BENAR bernama login_baru.php
        header("Location: login_baru.php");
        exit();
    }
}

function check_admin() {
    check_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo "<script>alert('Akses khusus Admin!'); window.location='index.php';</script>";
        exit();
    }
}
?>