<?php
session_start();
include 'db/koneksi.php';

// Sertakan autoloader Composer untuk PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$sweet_alert_script = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    if (empty($email)) {
        $sweet_alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Input Tidak Lengkap!',
                text: 'Alamat Email harus diisi.',
                showConfirmButton: true
            });
        ";
    } else {
        $stmt = $conn->prepare("SELECT id FROM tb_user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Hapus token lama untuk user ini (jika ada)
            $stmt_del = $conn->prepare("DELETE FROM tb_password_resets WHERE user_id = ?");
            $stmt_del->bind_param("i", $user_id);
            $stmt_del->execute();

            // Simpan token baru
            $stmt_insert = $conn->prepare("INSERT INTO tb_password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iss", $user_id, $token, $expires);
            $stmt_insert->execute();

            // --- Bagian PHPMailer dengan SendGrid ---
            $mail = new PHPMailer(true);

            try {
                // Konfigurasi Server SMTP SendGrid
                $mail->isSMTP();
                $mail->Host       = 'smtp.sendgrid.net'; // Host SMTP SendGrid
                $mail->SMTPAuth   = true;
                $mail->Username   = 'apikey'; // Username untuk SendGrid adalah 'apikey'
                $mail->Password   = 'YOUR_SENDGRID_API_KEY_HERE'; // Ganti dengan API Key SendGrid Anda
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Untuk port 465 (SMTPS)
                // ATAU
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Untuk port 587 (STARTTLS) // Port 587 sering menggunakan TLS
                $mail->Port       = 587; // Port standar untuk TLS

                // Penerima
                $mail->setFrom('autismearly@gmail.com', 'Autism Early Detector'); // Ganti dengan email yang Anda verifikasi di SendGrid
                $mail->addAddress($email);

                // Konten Email
                $mail->isHTML(true);
                $mail->Subject = 'Reset Password Anda - Autism Early Detector';
                $reset_link = "http://localhost/aed5/reset_password.php?token=" . $token; // Sesuaikan URL Anda

                $mail->Body    = "
                    <p>Halo,</p>
                    <p>Anda telah meminta reset password untuk akun Anda. Silakan klik link berikut untuk mereset password Anda:</p>
                    <p><a href='" . $reset_link . "'>Reset Password Anda</a></p>
                    <p>Link ini akan kadaluarsa dalam 1 jam.</p>
                    <p>Jika Anda tidak meminta reset password ini, abaikan email ini.</p>
                    <p>Terima kasih,</p>
                    <p>Tim Autism Early Detector</p>
                ";
                $mail->AltBody = "Halo,\n\nAnda telah meminta reset password untuk akun Anda. Silakan klik link berikut untuk mereset password Anda:\n" . $reset_link . "\n\nLink ini akan kadaluarsa dalam 1 jam.\nJika Anda tidak meminta reset password ini, abaikan email ini.\n\nTerima kasih,\nTim Autism Early Detector";

                $mail->send();

                $sweet_alert_script = "
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Terkirim!',
                        text: 'Link reset password telah dikirim ke email Anda. Silakan cek inbox (juga folder Spam/Junk).',
                        showConfirmButton: true
                    });
                ";
            } catch (Exception $e) {
                $sweet_alert_script = "
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim Email!',
                        text: 'Terjadi kesalahan saat mengirim email. Silakan coba lagi nanti.',
                        showConfirmButton: true
                    });
                ";
                 error_log("PHPMailer Error (Forgot Pass): " . $e->getMessage()); // Catat error ke log server
            }

        } else {
            $sweet_alert_script = "
                Swal.fire({
                    icon: 'error',
                    title: 'Email Tidak Ditemukan!',
                    text: 'Alamat email tidak terdaftar di sistem kami.',
                    showConfirmButton: true
                });
            ";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="icon" href="images/aed.png" type="image/png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/stylelog.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php if (!empty($sweet_alert_script)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php echo $sweet_alert_script; ?>
            });
        </script>
    <?php endif; ?>

    <div class="login-container full-height">
        <div class="row w-100 no-gutters">
            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center login-form-section">
                <h2 class="mb-4">Lupa Kata Sandi?</h2>
                <p class="text-secondary mb-4 text-center">Masukkan email Anda untuk menerima link reset kata sandi.</p>
                <form action="forgot_pass.php" method="POST" class="login-form">
                    <div class="mb-4">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan Email Anda" required>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-purple">Kirim Link Reset</button>
                    </div>
                    <p class="text-center mt-3">
                        <a href="login.php" class="forgot-password text-decoration-none">Kembali ke Login</a>
                    </p>
                </form>
            </div>
            <div class="col-md-6 d-flex justify-content-center align-items-center login-illustration-section">
                <div class="illustration-wrapper">
                    <img src="images/forget.png" class="img-fluid" alt="Forgot Password Illustration">
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>