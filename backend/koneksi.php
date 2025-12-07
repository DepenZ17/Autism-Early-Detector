<?php
// FILE: koneksi.php

// Konfigurasi Database
$host = 'localhost';    // Nama host database
$user = 'root';         // Username database (default XAMPP adalah 'root')
$pass = '';             // Password database (default XAMPP kosong)
$db   = 'db_aed';       // NAMA DATABASE SESUAI PERMINTAAN ANDA

// Membuat koneksi dengan nama variabel $koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$koneksi) {
    // Jika koneksi gagal, hentikan program dan tampilkan pesan error
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>