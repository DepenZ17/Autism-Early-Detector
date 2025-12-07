<?php

// --- Masukkan password yang Anda inginkan untuk akun admin di sini ---
$password_untuk_admin = 'admin12345'; // Ganti dengan password yang kuat!

// --- Proses Hashing ---
$hash_yang_dihasilkan = password_hash($password_untuk_admin, PASSWORD_DEFAULT);

// --- Tampilkan hasilnya ---
echo "<h1>Password Hash Generator</h1>";
echo "<p><strong>Password Asli:</strong> " . htmlspecialchars($password_untuk_admin) . "</p>";
echo "<p><strong>Hash yang Dihasilkan (untuk disalin ke database):</strong></p>";
// Gunakan <textarea> agar mudah di-copy
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hash_yang_dihasilkan) . "</textarea>";
echo "<hr>";
echo "<p><em>Setiap kali halaman ini di-refresh, hash akan berbeda, namun semuanya valid untuk password yang sama.</em></p>";

?>