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
    $host     = "sql106.infinityfree.com"; // Ganti dengan MySQL Hostname InfinityFree Anda
    $user     = "if0_42652939";           // Ganti dengan Username MySQL InfinityFree Anda
    $pass     = "Cobac0ba26";     // Ganti dengan Password hosting Anda
    $db_name  = "if0_42652939_db_ecommerce2"; // Ganti dengan Nama DB InfinityFree Anda
}

$koneksi = mysqli_connect($host, $user, $pass, $db_name);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>