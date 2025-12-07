<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['delete_user'])) {
        $id_to_delete = $_POST['id'];
        if (isset($_SESSION['user_id']) && $id_to_delete == $_SESSION['user_id']) {
            $message = 'Error: Anda tidak dapat menghapus akun Anda sendiri.';
            $message_type = 'danger';
        } else {
            $stmt_get_photo = $conn->prepare("SELECT photo FROM tb_user WHERE id = ?");
            $stmt_get_photo->bind_param("i", $id_to_delete);
            $stmt_get_photo->execute();
            $result_photo = $stmt_get_photo->get_result()->fetch_assoc();
            if ($result_photo && !empty($result_photo['photo']) && file_exists("../" . $result_photo['photo'])) {
                unlink("../" . $result_photo['photo']);
            }
            $stmt_get_photo->close();
            $stmt = $conn->prepare("DELETE FROM tb_user WHERE id = ?");
            $stmt->bind_param("i", $id_to_delete);
            if ($stmt->execute()) {
                $message = 'Pengguna berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menghapus pengguna: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['save_user'])) {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        if (empty($username) || empty($email) || empty($role)) {
            $message = 'Username, email, dan role tidak boleh kosong.';
            $message_type = 'danger';
        } else {
            if (empty($id)) {
                if (empty($password) || strlen($password) < 6) {
                    $message = 'Password wajib diisi dan minimal 6 karakter.';
                    $message_type = 'danger';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO tb_user (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                    if ($stmt->execute()) {
                        $message = 'Pengguna baru berhasil ditambahkan!';
                        $message_type = 'success';
                    } else { $message = 'Gagal menambah pengguna: ' . $stmt->error; $message_type = 'danger'; }
                    $stmt->close();
                }
            } else {
                $photo_path = $_POST['existing_photo'];
                $upload_ok = true;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                    $upload_dir = "../uploads/profiles/";
                    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);
                    $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                    $unique_filename = "user_" . $id . "_" . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $unique_filename;
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($file_extension, $allowed_types)) {
                        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                            if ($photo_path && file_exists("../" . $photo_path)) { unlink("../" . $photo_path); }
                            $photo_path = "uploads/profiles/" . $unique_filename;
                        }
                    } else { $message = 'Hanya file gambar (JPG, PNG, GIF) yang diizinkan.'; $message_type = 'danger'; $upload_ok = false; }
                }
                if ($upload_ok) {
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE tb_user SET username = ?, email = ?, role = ?, password = ?, photo = ? WHERE id = ?");
                        $stmt->bind_param("sssssi", $username, $email, $role, $hashed_password, $photo_path, $id);
                    } else {
                        $stmt = $conn->prepare("UPDATE tb_user SET username = ?, email = ?, role = ?, photo = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $username, $email, $role, $photo_path, $id);
                    }
                    if ($stmt->execute()) {
                        $message = 'Data pengguna berhasil diperbarui!';
                        $message_type = 'success';
                        if ($id == $_SESSION['user_id']) {
                            $_SESSION['username'] = $username;
                            $_SESSION['user_photo'] = $photo_path;
                        }
                    } else { $message = 'Gagal memperbarui data: ' . $stmt->error; $message_type = 'danger'; }
                    $stmt->close();
                }
            }
        }
    }
}

$records_per_page = 10;
$total_records_result = $conn->query("SELECT COUNT(*) as total FROM tb_user");
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

$users = [];
$query = "SELECT id, username, email, role, photo FROM tb_user ORDER BY id ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
if ($result) { while ($row = $result->fetch_assoc()) { $users[] = $row; } }
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin Dashboard</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <main class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="main-header">Manajemen User</h1>
            <button class="btn btn-primary" onclick="openAddModal()"><i class="bi bi-plus-circle me-2"></i>Tambah User Baru</button>
        </div>
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#ID</th><th scope="col">Profil</th><th scope="col">Email</th>
                                <th scope="col">Role</th><th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Belum ada data pengguna.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <th scope="row"><?= $user['id'] ?></th>
                                    <td>
                                        <img src="../<?= htmlspecialchars($user['photo'] ?? 'images/default-avatar.png') ?>" alt="Avatar" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                        <?= htmlspecialchars($user['username']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><span class="badge bg-<?= ($user['role'] == 'admin') ? 'primary' : 'secondary' ?>"><?= ucfirst($user['role']) ?></span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning" onclick='openEditModal(<?= json_encode($user) ?>)'><i class="bi bi-pencil-square"></i> Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')"><i class="bi bi-trash-fill"></i> Hapus</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Navigasi Halaman" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?>
                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer class="footer text-center"><div class="container"><span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span></div></footer>
    <div class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="data_user.php" enctype="multipart/form-data">
            <div class="modal-header"><h5 class="modal-title" id="userModalLabel">Form User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" id="userId"><input type="hidden" name="save_user" value="1"><input type="hidden" name="existing_photo" id="existingPhoto">
                <div class="mb-3 text-center"><img src="" id="photoPreview" alt="Foto Profil" class="rounded-circle" width="100" height="100" style="object-fit: cover; border: 3px solid #ddd;"></div>
                <div class="mb-3"><label for="photo" class="form-label">Ubah Foto Profil</label><input class="form-control form-control-sm" type="file" id="photo" name="photo" accept="image/*"></div>
                <div class="mb-3"><label for="username" class="form-label">Username</label><input type="text" class="form-control" id="username" name="username" required></div>
                <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" id="email" name="email" required></div>
                <div class="mb-3"><label for="password" class="form-label">Password</label><input type="password" class="form-control" id="password" name="password"><small class="form-text text-muted" id="passwordHelp"></small></div>
                <div class="mb-3"><label for="role" class="form-label">Role</label><select class="form-select" id="role" name="role" required><option value="user">User</option><option value="admin">Admin</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div></div>
    <form method="POST" id="deleteForm" style="display: none;"><input type="hidden" name="id" id="deleteUserId"><input type="hidden" name="delete_user" value="1"></form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const userModal = new bootstrap.Modal(document.getElementById('userModal'));
        function openAddModal(){document.getElementById('userModalLabel').innerText='Tambah User Baru';document.querySelector('#userModal form').reset();document.getElementById('userId').value='';document.getElementById('password').setAttribute('required','required');document.getElementById('passwordHelp').innerText='Password wajib diisi untuk user baru.';document.getElementById('photoPreview').src='../images/default-avatar.png';userModal.show();}
        function openEditModal(user){document.getElementById('userModalLabel').innerText='Edit Data User';document.querySelector('#userModal form').reset();document.getElementById('userId').value=user.id;document.getElementById('username').value=user.username;document.getElementById('email').value=user.email;document.getElementById('password').removeAttribute('required');document.getElementById('passwordHelp').innerText='Kosongkan jika tidak ingin mengubah password.';document.getElementById('role').value=user.role;const photoSrc=user.photo?`../${user.photo}`:'../images/default-avatar.png';document.getElementById('photoPreview').src=photoSrc;document.getElementById('existingPhoto').value=user.photo||'';userModal.show();}
        function deleteUser(id,username){Swal.fire({title:'Anda yakin?',text:`Anda akan menghapus pengguna "${username}". Tindakan ini tidak dapat dibatalkan!`,icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',cancelButtonColor:'#3085d6',confirmButtonText:'Ya, hapus!',cancelButtonText:'Batal'}).then((result)=>{if(result.isConfirmed){document.getElementById('deleteUserId').value=id;document.getElementById('deleteForm').submit();}})}
    </script>
</body>
</html>