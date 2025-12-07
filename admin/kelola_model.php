<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$message = '';
$message_type = '';
$upload_dir = "../backend/flask/uploads/";

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// LOGIKA
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Proses Upload Model Baru
    if (isset($_POST['upload_model'])) {
        $nama_model = trim($_POST['nama_model']);
        $versi = trim($_POST['versi']);
        $deskripsi = trim($_POST['deskripsi']);
        $uploaded_by = $_SESSION['user_id'];

        if (!empty($nama_model) && !empty($versi) && isset($_FILES['file_model']) && $_FILES['file_model']['error'] == 0 && isset($_FILES['file_encoder']) && $_FILES['file_encoder']['error'] == 0) {
            $filename_model = time() . '_' . basename($_FILES['file_model']['name']);
            $filename_encoder = time() . '_' . basename($_FILES['file_encoder']['name']);

            if (move_uploaded_file($_FILES['file_model']['tmp_name'], $upload_dir . $filename_model) && move_uploaded_file($_FILES['file_encoder']['tmp_name'], $upload_dir . $filename_encoder)) {
                $stmt = $conn->prepare("INSERT INTO tb_model (nama_model, versi, deskripsi, filename_model, filename_encoder, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $nama_model, $versi, $deskripsi, $filename_model, $filename_encoder, $uploaded_by);
                if ($stmt->execute()) {
                    $message = 'Model baru berhasil diunggah!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menyimpan data model: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            } else {
                $message = 'Gagal memindahkan file. Periksa izin folder `backend/flask/uploads/`.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Semua field dan kedua file (.pkl) wajib diisi.';
            $message_type = 'danger';
        }
    }

    // Proses Aktivasi Model
    if (isset($_POST['activate_model'])) {
        $id_model_to_activate = $_POST['id_model'];
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE tb_model SET aktif = 0");
            $stmt = $conn->prepare("UPDATE tb_model SET aktif = 1 WHERE id_model = ?");
            $stmt->bind_param("i", $id_model_to_activate);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            $message = 'Model berhasil diaktifkan! API akan memuat ulang model pada permintaan berikutnya.';
            $message_type = 'success';

            // [PERBAIKAN] Beri tahu API Flask untuk memuat ulang modelnya
            // Fungsi ini akan mencoba menghubungi API, tidak masalah jika gagal (misal: API sedang tidak jalan)
            @file_get_contents('http://127.0.0.1:5000/reload-model', false, stream_context_create(['http'=>['method'=>'POST', 'timeout' => 2]]));
            
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = 'Gagal mengaktifkan model: ' . $exception->getMessage();
            $message_type = 'danger';
        }
    }
     
    // Proses Hapus Model
    if (isset($_POST['delete_model'])) {
        $id_to_delete = $_POST['id_model'];
        
        $stmt_get = $conn->prepare("SELECT filename_model, filename_encoder FROM tb_model WHERE id_model = ?");
        $stmt_get->bind_param("i", $id_to_delete);
        $stmt_get->execute();
        $result_files = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if ($result_files) {
            // Hapus file dari server
            if (!empty($result_files['filename_model']) && file_exists($upload_dir . $result_files['filename_model'])) {
                unlink($upload_dir . $result_files['filename_model']);
            }
            if (!empty($result_files['filename_encoder']) && file_exists($upload_dir . $result_files['filename_encoder'])) {
                unlink($upload_dir . $result_files['filename_encoder']);
            }

            // Hapus record dari database
            $stmt_del = $conn->prepare("DELETE FROM tb_model WHERE id_model = ?");
            $stmt_del->bind_param("i", $id_to_delete);
            if ($stmt_del->execute()) {
                $message = 'Model berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menghapus model dari database: ' . $stmt_del->error;
                $message_type = 'danger';
            }
            $stmt_del->close();
        }
    }
}

// Ambil semua model dari database untuk ditampilkan
$models = [];
$result = $conn->query("SELECT m.*, u.username as uploader FROM tb_model m LEFT JOIN tb_user u ON m.uploaded_by = u.id ORDER BY m.uploaded_at DESC");
if($result) {
    while($row = $result->fetch_assoc()) {
        $models[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Model - Admin</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="container my-5">
        <h1 class="main-header">Manajemen Model Prediksi</h1>
        <p class="text-muted">Di halaman ini Anda dapat mengunggah dan mengelola versi model machine learning yang digunakan oleh sistem.</p>
        
        <p class="text-info-emphasis bg-info-subtle border border-info-subtle rounded-3 p-2">
            <i class="bi bi-info-circle-fill"></i> <strong>Tips:</strong> Untuk mengganti model yang sedang aktif, cukup klik tombol "Aktifkan" pada model lain yang Anda inginkan.
        </p>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mt-4" role="alert">
            <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm my-5">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-cloud-arrow-up-fill me-2" style="color: #20c997;"></i>Unggah Model Baru</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="kelola_model.php" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nama Model</label><input type="text" name="nama_model" class="form-control" placeholder="Contoh: RF Klasik V1" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Versi</label><input type="text" name="versi" class="form-control" placeholder="Contoh: 1.0" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Deskripsi Singkat</label><textarea name="deskripsi" class="form-control" rows="2" placeholder="Contoh: Model dasar dengan 5-fold cross-validation"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">File Model (.pkl)</label><input type="file" name="file_model" class="form-control" accept=".pkl,.joblib" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">File Encoder (.pkl)</label><input type="file" name="file_encoder" class="form-control" accept=".pkl,.joblib" required></div>
                    </div>
                    <button type="submit" name="upload_model" class="btn btn-primary">Unggah Model</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light"><h5 class="mb-0"><i class="bi bi-list-task me-2" style="color: #20c997;"></i>Daftar Model Tersimpan</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Model</th>
                                <th>Deskripsi</th>
                                <th>Pengunggah</th>
                                <th>Tgl Unggah</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($models)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Belum ada model yang diunggah.</td></tr>
                            <?php else: ?>
                                <?php foreach($models as $model): ?>
                                <tr class="<?= $model['aktif'] ? 'table-success' : '' ?>">
                                    <td><strong><?= htmlspecialchars($model['nama_model']) ?></strong><br><small class="text-muted">v<?= htmlspecialchars($model['versi']) ?></small></td>
                                    <td><p class="mb-0 small"><?= nl2br(htmlspecialchars($model['deskripsi'])) ?></p></td>
                                    <td><?= htmlspecialchars($model['uploader']) ?></td>
                                    <td><?= date('d M Y', strtotime($model['uploaded_at'])) ?></td>
                                    <td class="text-center">
                                        <?php if($model['aktif']): ?>
                                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                                            <button class="btn btn-sm btn-outline-danger ms-2" disabled title="Model aktif tidak bisa dihapus"><i class="bi bi-trash"></i></button>
                                        <?php else: ?>
                                            <form method="POST" action="kelola_model.php" class="d-inline-block me-1">
                                                <input type="hidden" name="id_model" value="<?= $model['id_model'] ?>">
                                                <button type="submit" name="activate_model" class="btn btn-sm btn-success">Aktifkan</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteModel(<?= $model['id_model'] ?>, '<?= htmlspecialchars(addslashes($model['nama_model'])) ?>')"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer text-center">
        <div class="container"><span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span></div>
    </footer>
    
    <form method="POST" action="kelola_model.php" id="deleteForm">
        <input type="hidden" name="id_model" id="deleteModelId">
        <input type="hidden" name="delete_model" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function deleteModel(id, modelName) {
            Swal.fire({
                title: 'Anda yakin?',
                text: `Anda akan menghapus model "${modelName}" secara permanen. Tindakan ini tidak dapat dibatalkan!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteModelId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            })
        }
    </script>
</body>
</html>