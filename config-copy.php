<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "sql106.infinityfree.com";
$user = "if0_42652939";
$pass = "Cobac0ba26";
$db   = "if0_42652939_db_ecommerce2";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login_baru.php");
        exit();
    }
}

function check_admin() {
    check_login();
    if ($_SESSION['role'] !== 'admin') {
        echo "<script>alert('Akses khusus Admin!'); window.location='index.php';</script>";
        exit();
    }
}
?>