<?php
session_start();
// Pastikan path ini benar. Jika koneksi.php ada di aed/db/koneksi.php
// dan register.php ada di aed/register.php, maka path relatifnya adalah 'db/koneksi.php'
include 'db/koneksi.php'; 

// Inisialisasi variabel untuk menghindari PHP Warning: Undefined variable
$username = '';
$email = '';
$message_text = ''; // Untuk menyimpan teks pesan SweetAlert
$message_type = ''; // Untuk menyimpan tipe pesan SweetAlert (success, error)

// Cek apakah koneksi database berhasil dari koneksi.php
if ($conn->connect_error) {
    $message_text = "Gagal terhubung ke database. Mohon coba lagi nanti.";
    $message_type = 'error';
    // Hentikan eksekusi script jika koneksi gagal total
    // SweetAlert akan menampilkan pesan ini saat halaman dimuat
} else {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validasi input
        if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
            $message_text = 'Semua bidang harus diisi.';
            $message_type = 'error';
        } elseif ($password !== $confirm_password) {
            $message_text = 'Konfirmasi password tidak cocok.';
            $message_type = 'error';
        } elseif (strlen($password) < 6) {
            $message_text = 'Password harus minimal 6 karakter.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message_text = 'Format email tidak valid.';
            $message_type = 'error';
        } else {
            // Cek apakah username sudah ada
            $stmt_check_username = $conn->prepare("SELECT id FROM tb_user WHERE username = ?");
            $stmt_check_username->bind_param("s", $username);
            $stmt_check_username->execute();
            $stmt_check_username->store_result();

            // Cek apakah email sudah ada
            $stmt_check_email = $conn->prepare("SELECT id FROM tb_user WHERE email = ?");
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();

            if ($stmt_check_username->num_rows > 0) {
                $message_text = 'Username sudah terdaftar. Silakan pilih username lain.';
                $message_type = 'error';
            } elseif ($stmt_check_email->num_rows > 0) { // Tambahkan cek duplikasi email
                $message_text = 'Email sudah terdaftar. Silakan gunakan email lain.';
                $message_type = 'error';
            } else {
                // Hash password sebelum menyimpan
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user'; // Default role untuk pendaftaran

                // **INI BAGIAN PENTING YANG DIUBAH:**
                // Masukkan data pengguna baru ke database, SERTAKAN kolom 'email'
                $stmt = $conn->prepare("INSERT INTO tb_user (username, email, password, role) VALUES (?, ?, ?, ?)");
                //                                            ^^^^^ Pastikan email di sini
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role); 
                //                                 ^^^^^ Dan bind email di sini ('ssss' karena 4 string)

                if ($stmt->execute()) {
                    $message_text = 'Pendaftaran berhasil! Anda dapat login sekarang.';
                    $message_type = 'success';
                    // Kosongkan form setelah sukses pendaftaran
                    $username = '';
                    $email = '';
                    // Karena SweetAlert akan muncul, pengguna bisa refresh halaman untuk mengosongkan form
                    // atau Anda bisa redirect mereka ke halaman login.
                } else {
                    $message_text = 'Terjadi kesalahan saat pendaftaran: ' . $stmt->error;
                    $message_type = 'error';
                }
            }
            // Tutup statement
            if (isset($stmt_check_username)) $stmt_check_username->close();
            if (isset($stmt_check_email)) $stmt_check_email->close();
            if (isset($stmt)) $stmt->close();
        }
    }
}
// Tutup koneksi database di akhir script
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru</title>
    <link rel="icon" href="images/aed.png" type="image/png">
    <link rel="stylesheet" href="css/stylereg.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <div class="main-container">
        <div class="form-section">
            <h1>Sign Up</h1>

            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" id="username" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
                </div>
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit">Sign Up</button>
            </form>
            <div class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>

        <div class="illustration-section">
    <div class="image-container">
        <img src="images/hello.png" alt="Ilustrasi User">
    </div>
    <h2>SIGN UP &<br>Join US</h2>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Ambil pesan dan tipe pesan dari PHP
        // Gunakan json_encode untuk memastikan string PHP di-escape dengan benar untuk JavaScript
        const messageText = <?php echo json_encode($message_text); ?>;
        const messageType = <?php echo json_encode($message_type); ?>;

        // Tampilkan SweetAlert jika ada pesan
        if (messageText !== "") {
            Swal.fire({
                icon: messageType, // 'success' atau 'error'
                title: messageType === 'success' ? 'Berhasil!' : 'Error!',
                text: messageText,
                showConfirmButton: true,
                confirmButtonColor: '#6A3FD3' // Warna tombol sesuai tema
            });
        }
    </script>
</body>
</html>