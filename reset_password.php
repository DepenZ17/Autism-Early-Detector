<?php
session_start();
include 'db/koneksi.php'; // Pastikan path ini benar

$sweet_alert_script = '';
$token_valid = false;
$token = '';
$user_id_from_token = null;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Cek token di database
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM tb_password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id_from_token = $row['user_id'];
        $expires_at = strtotime($row['expires_at']); // Konversi ke timestamp

        // Periksa apakah token sudah kadaluarsa
        if (time() < $expires_at) {
            $token_valid = true;
        } else {
            $sweet_alert_script = "
                Swal.fire({
                    icon: 'error',
                    title: 'Token Kadaluarsa!',
                    text: 'Link reset password sudah tidak berlaku. Silakan minta link baru.',
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'forgot_pass.php'; // Arahkan kembali ke forgot_pass
                });
            ";
            // Hapus token kadaluarsa dari database
            $stmt_del_expired = $conn->prepare("DELETE FROM tb_password_resets WHERE token = ?");
            $stmt_del_expired->bind_param("s", $token);
            $stmt_del_expired->execute();
            $stmt_del_expired->close();
        }
    } else {
        $sweet_alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Token Tidak Valid!',
                text: 'Link reset password tidak valid atau sudah digunakan.',
                showConfirmButton: true
            }).then(() => {
                window.location.href = 'forgot_pass.php'; // Arahkan kembali ke forgot_pass
            });
        ";
    }
    $stmt->close();
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $sweet_alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Input Tidak Lengkap!',
                text: 'Semua kolom harus diisi.',
                showConfirmButton: true
            });
        ";
    } else if ($new_password !== $confirm_password) {
        $sweet_alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Kata Sandi Tidak Cocok!',
                text: 'Kata sandi baru dan konfirmasi kata sandi tidak cocok.',
                showConfirmButton: true
            });
        ";
    } else {
        // Cek token di database lagi (untuk keamanan)
        $stmt_check = $conn->prepare("SELECT user_id, expires_at FROM tb_password_resets WHERE token = ?");
        $stmt_check->bind_param("s", $token);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $row_check = $result_check->fetch_assoc();
            $user_id_from_token = $row_check['user_id'];
            $expires_at_check = strtotime($row_check['expires_at']);

            if (time() < $expires_at_check) {
                // Token valid, hash password baru dan update user
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $conn->begin_transaction(); // Mulai transaksi

                try {
                    // Update password user
                    $stmt_update_pass = $conn->prepare("UPDATE tb_user SET password = ? WHERE id = ?");
                    $stmt_update_pass->bind_param("si", $hashed_password, $user_id_from_token);
                    $stmt_update_pass->execute();
                    $stmt_update_pass->close();

                    // Hapus token setelah digunakan
                    $stmt_delete_token = $conn->prepare("DELETE FROM tb_password_resets WHERE token = ?");
                    $stmt_delete_token->bind_param("s", $token);
                    $stmt_delete_token->execute();
                    $stmt_delete_token->close();

                    $conn->commit(); // Commit transaksi jika semua berhasil

                    $sweet_alert_script = "
                        Swal.fire({
                            icon: 'success',
                            title: 'Kata Sandi Berhasil Direset!',
                            text: 'Kata sandi Anda telah berhasil diubah. Silakan login dengan kata sandi baru Anda.',
                            showConfirmButton: true
                        }).then(() => {
                            window.location.href = 'login.php'; // Arahkan ke halaman login
                        });
                    ";
                } catch (mysqli_sql_exception $e) {
                    $conn->rollback(); // Rollback jika ada error
                    $sweet_alert_script = "
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mereset Kata Sandi!',
                            text: 'Terjadi kesalahan database. Silakan coba lagi nanti.',
                            showConfirmButton: true
                        });
                    ";
                    error_log("Database error during password reset: " . $e->getMessage());
                }

            } else {
                $sweet_alert_script = "
                    Swal.fire({
                        icon: 'error',
                        title: 'Token Kadaluarsa!',
                        text: 'Link reset password sudah tidak berlaku. Silakan minta link baru.',
                        showConfirmButton: true
                    }).then(() => {
                        window.location.href = 'forgot_pass.php';
                    });
                ";
                 // Hapus token kadaluarsa dari database
                 $stmt_del_expired = $conn->prepare("DELETE FROM tb_password_resets WHERE token = ?");
                 $stmt_del_expired->bind_param("s", $token);
                 $stmt_del_expired->execute();
                 $stmt_del_expired->close();
            }
        } else {
            $sweet_alert_script = "
                Swal.fire({
                    icon: 'error',
                    title: 'Token Tidak Valid!',
                    text: 'Link reset password tidak valid atau sudah digunakan.',
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'forgot_pass.php';
                });
            ";
        }
        $stmt_check->close();
    }
} else {
    // Jika tidak ada token di URL atau tidak ada POST request
    $sweet_alert_script = "
        Swal.fire({
            icon: 'warning',
            title: 'Akses Tidak Sah!',
            text: 'Silakan gunakan link reset password yang valid dari email Anda.',
            showConfirmButton: true
        }).then(() => {
            window.location.href = 'forgot_pass.php';
        });
    ";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
                <?php if ($token_valid): ?>
                    <h2 class="mb-4">Atur Ulang Kata Sandi</h2>
                    <p class="text-secondary mb-4 text-center">Masukkan kata sandi baru Anda.</p>
                    <form action="reset_password.php" method="POST" class="login-form">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Kata Sandi Baru" required>
                        </div>
                        <div class="mb-4">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Kata Sandi Baru" required>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-purple">Ubah Kata Sandi</button>
                        </div>
                        <p class="text-center mt-3">
                            <a href="login.php" class="forgot-password text-decoration-none">Kembali ke Login</a>
                        </p>
                    </form>
                <?php else: ?>
                    <?php endif; ?>
            </div>
            <div class="col-md-6 d-flex justify-content-center align-items-center login-illustration-section">
                <div class="illustration-wrapper">
                    <img src="images/write.png" class="img-fluid" alt="Reset Password Illustration">
                    </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>