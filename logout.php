<?php
// Mulai session (jika belum dimulai)
session_start();

// Hapus semua session
session_unset();

// Hancurkan session
session_destroy();

// Arahkan kembali ke halaman login atau landing
header("Location: landing.php");
exit;
?>
