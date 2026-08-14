<?php
// Deteksi apakah sedang diakses di Localhost atau di Hosting InfinityFree
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    // === KONFIGURASI UNTUK LOCALHOST (XAMPP) ===
    $host     = "localhost";
    $user     = "root";
    $pass     = "";            // Default XAMPP biasanya kosong
    $db_name  = "db_ecommerce2"; // Nama database di phpMyAdmin lokal Anda
} else {
    // === KONFIGURASI UNTUK HOSTING (INFINITYFREE) ===
    $host     = "sqlXXX.infinityfree.com"; // Ganti dengan MySQL Hostname InfinityFree Anda
    $user     = "if0_38123456";           // Ganti dengan Username MySQL InfinityFree Anda
    $pass     = "PASSWORD_HOSTING_MU";     // Ganti dengan Password hosting Anda
    $db_name  = "if0_38123456_dbpenjualan"; // Ganti dengan Nama DB InfinityFree Anda
}

$koneksi = mysqli_connect($host, $user, $pass, $db_name);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>