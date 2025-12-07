<?php
// FILE: tambah.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php">Daftar Data</a></li>
            <li><a href="tambah.php" class="active">Tambah Data Baru</a></li>
        </ul>
    </nav>
    <div class="container">
        <h2>Form Input Data Mahasiswa</h2>
        <form action="simpan.php" method="POST">
            <div>
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="nim" required placeholder="Contoh: 220101001">
            </div>
            <div>
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap Anda">
            </div>
            <div>
                <label for="tanggal">Tanggal Lahir</label>
                <input type="date" id="tanggal" name="tanggal" required>
            </div>
            <button type="submit">Simpan Data</button>
        </form>
    </div>
</body>
</html>