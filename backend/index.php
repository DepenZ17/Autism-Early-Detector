<?php
// FILE: index.php

// Memanggil file koneksi agar variabel $koneksi tersedia
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php" class="active">Daftar Data</a></li>
            <li><a href="tambah.php">Tambah Data Baru</a></li>
        </ul>
    </nav>
    <div class="container">
        <h2>Data Mahasiswa yang Tersimpan</h2>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Tanggal Lahir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM mahasiswa ORDER BY id DESC";
                // Menggunakan variabel $koneksi dari file koneksi.php
                $hasil = mysqli_query($koneksi, $query);

                if ($hasil && mysqli_num_rows($hasil) > 0) {
                    $no = 1;
                    while ($data = mysqli_fetch_assoc($hasil)) {
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        echo "<td>" . htmlspecialchars($data['nim']) . "</td>";
                        echo "<td>" . htmlspecialchars($data['nama']) . "</td>";
                        echo "<td>" . date('d F Y', strtotime($data['tanggal_lahir'])) . "</td>";
                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;'>Belum ada data. Silakan tambah data baru.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>