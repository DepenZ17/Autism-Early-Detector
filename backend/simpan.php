<?php
// FILE: simpan.php

// Memanggil file koneksi agar variabel $koneksi tersedia
include 'koneksi.php';

// Memeriksa apakah data dikirim melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Mengambil data dari form menggunakan variabel $koneksi untuk pengamanan
    $nim = mysqli_real_escape_string($koneksi, $_POST['nim']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);

    $query = "INSERT INTO mahasiswa (nim, nama, tanggal_lahir) VALUES ('$nim', '$nama', '$tanggal')";

    // Menjalankan query menggunakan $koneksi
    if (mysqli_query($koneksi, $query)) {
        // Jika berhasil, alihkan pengguna kembali ke halaman utama
        header("Location: index.php");
        exit();
    } else {
        // Jika gagal, tampilkan pesan error
        echo "Error: " . $query . "<br>" . mysqli_error($koneksi);
    }
} else {
    // Jika file ini diakses langsung, alihkan ke halaman utama
    header("Location: index.php");
    exit();
}
?>