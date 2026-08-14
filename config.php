<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_ecommerce2";

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