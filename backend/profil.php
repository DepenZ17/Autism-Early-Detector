<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';
$user_id = $_SESSION['user_id'];
$sweet_alert_script = '';

// ... (Seluruh logika PHP di bagian atas tetap sama, tidak perlu diubah) ...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Jika yang disubmit adalah form update profil
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);
        $photo_path = $_POST['existing_photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $target_dir = "../uploads/profiles/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            $unique_filename = "user_" . $user_id . "_" . time() . '.' . $file_extension;
            $target_file = $target_dir . $unique_filename;
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    if ($photo_path && file_exists("../" . $photo_path)) {
                        unlink("../" . $photo_path);
                    }
                    $photo_path = "uploads/profiles/" . $unique_filename;
                }
            } else {
                 $sweet_alert_script = "Swal.fire('Error!', 'Hanya file gambar (JPG, JPEG, PNG, GIF) yang diizinkan.', 'error');";
            }
        }
        if (empty($sweet_alert_script)) {
            $stmt_check = $conn->prepare("SELECT id FROM tb_user WHERE (username = ? OR email = ?) AND id != ?");
            $stmt_check->bind_param("ssi", $new_username, $new_email, $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $sweet_alert_script = "Swal.fire('Error!', 'Username atau email sudah digunakan oleh akun lain.', 'error');";
            } else {
                $stmt_update = $conn->prepare("UPDATE tb_user SET username = ?, email = ?, photo = ? WHERE id = ?");
                $stmt_update->bind_param("sssi", $new_username, $new_email, $photo_path, $user_id);
                if ($stmt_update->execute()) {
                    $_SESSION['username'] = $new_username;
                    $_SESSION['user_photo'] = $photo_path;
                    $sweet_alert_script = "Swal.fire('Berhasil!', 'Data profil berhasil diperbarui.', 'success').then(() => { window.location.href = 'profil.php' });";
                } else {
                    $sweet_alert_script = "Swal.fire('Error!', 'Gagal memperbarui profil.', 'error');";
                }
                $stmt_update->close();
            }
            $stmt_check->close();
        }
    }
    // Jika yang disubmit adalah form ubah password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $sweet_alert_script = "Swal.fire('Error!', 'Semua field password harus diisi.', 'error');";
        } elseif (strlen($new_password) < 6) {
            $sweet_alert_script = "Swal.fire('Error!', 'Password baru minimal harus 6 karakter.', 'error');";
        } elseif ($new_password !== $confirm_password) {
            $sweet_alert_script = "Swal.fire('Error!', 'Konfirmasi password baru tidak cocok.', 'error');";
        } else {
            $stmt_pass = $conn->prepare("SELECT password FROM tb_user WHERE id = ?");
            $stmt_pass->bind_param("i", $user_id);
            $stmt_pass->execute();
            $result_pass = $stmt_pass->get_result()->fetch_assoc();
            $stmt_pass->close();
            if (password_verify($current_password, $result_pass['password'])) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_pass = $conn->prepare("UPDATE tb_user SET password = ? WHERE id = ?");
                $stmt_update_pass->bind_param("si", $new_hashed_password, $user_id);
                if ($stmt_update_pass->execute()) {
                    $sweet_alert_script = "Swal.fire('Berhasil!', 'Password berhasil diubah.', 'success');";
                } else {
                    $sweet_alert_script = "Swal.fire('Error!', 'Gagal mengubah password.', 'error');";
                }
                $stmt_update_pass->close();
            } else {
                $sweet_alert_script = "Swal.fire('Error!', 'Password saat ini yang Anda masukkan salah.', 'error');";
            }
        }
    }
}
$stmt_user = $conn->prepare("SELECT username, email, photo FROM tb_user WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$current_user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Autism Early Detector</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main>
        <div class="container my-5">
            <h1 class="main-header text-center mb-5">Pengaturan Akun Profil</h1>
            <div class="row g-5 justify-content-center">
                
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4"><i class="bi bi-person-fill me-2"></i>Ubah Data Profil</h4>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($current_user['photo'] ?? '') ?>">
                                <div class="mb-3 text-center">
                                    <img src="../<?= htmlspecialchars($current_user['photo'] ?? 'images/default-avatar.png') ?>" alt="Foto Profil" class="rounded-circle" width="120" height="120" style="object-fit: cover; border: 4px solid #e9ecef;">
                                </div>
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Ubah Foto Profil (Opsional)</label>
                                    <input class="form-control" type="file" id="photo" name="photo" accept="image/jpeg, image/png, image/gif">
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($current_user['username']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($current_user['email']) ?>" required>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary w-100">Simpan Perubahan Profil</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                         <div class="card-body p-4">
                            <h4 class="card-title mb-4"><i class="bi bi-key-fill me-2"></i>Ubah Password</h4>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <small class="form-text text-muted">Minimal 6 karakter.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-danger w-100">Ubah Password</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
    
    <footer class="footer text-center">
        <div class="container">
            <span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span>
        </div>
    </footer>

    <?php if (!empty($sweet_alert_script)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $sweet_alert_script; ?>
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>