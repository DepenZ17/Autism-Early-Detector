<?php
session_start();
include 'db/koneksi.php'; 

$sweet_alert_script = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userInput = $_POST['username'];
    $password = $_POST['password'];

    if (empty($userInput) || empty($password)) {
        $sweet_alert_script = "
            Swal.fire({
                icon: 'error',
                title: 'Input Tidak Lengkap!',
                text: 'Username/Email dan Password harus diisi.',
                showConfirmButton: true
            });
        ";
    } else {
        // [PERBAIKAN 1] Tambahkan 'photo' ke dalam daftar kolom yang diambil
        $stmt = $conn->prepare("SELECT id, username, password, role, photo FROM tb_user WHERE username = ? OR email = ?");
        
        $stmt->bind_param("ss", $userInput, $userInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login berhasil, set semua session yang dibutuhkan
                $_SESSION['user_id'] = $user['id']; 
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // [PERBAIKAN 2] Simpan path foto dari tb_user ke session
                $_SESSION['user_photo'] = $user['photo'];

                // Kode SweetAlert untuk sukses dan redirect
                $sweet_alert_script = "
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Berhasil!',
                        text: 'Selamat datang, " . htmlspecialchars($user['username']) . "!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                ";
            } else {
                // Password salah
                $sweet_alert_script = "
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal!',
                        text: 'Username/Email atau password salah.',
                        showConfirmButton: true
                    });
                ";
            }
        } else {
            // User tidak ditemukan
            $sweet_alert_script = "
                Swal.fire({
                    icon: 'error',
                    title: 'Login Gagal!',
                    text: 'Username/Email atau password salah.',
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
    <title>Login - Autism Early Detector</title>
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
                <h2 class="mb-5 text-dark">Welcome Back!</h2>
                <form action="login.php" method="POST" class="login-form">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username atau Email" required>
                    </div>
                    <div class="mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                    <a href="forgot_pass.php" class="forgot-password text-decoration-none mb-4 d-block text-end">Forgot Password?</a>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-purple">Sign In</button>
                        <a href="register.php" class="btn btn-purple">Sign Up</a>
                    </div>
                    <p class="text-center mt-3">
                    <a href="landing.php" class="text-decoration-none">← Kembali ke Halaman Utama</a>
                    </p>
                </form>
            </div>

            <div class="col-md-6 d-flex justify-content-center align-items-center login-illustration-section">
                <div class="illustration-wrapper">
                    <img src="images/Group.png" class="img-fluid" alt="Login Illustration">
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>