<?php
$host = 'localhost';       // atau 127.0.0.1
$user = 'root';            // ganti sesuai user MySQL kamu
$pass = '';                // ganti jika ada password
$dbname = 'db_aed';        // nama database kamu

$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
