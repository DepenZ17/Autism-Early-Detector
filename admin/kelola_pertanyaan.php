<?php
// Navbar akan menangani session_start(), jadi tidak perlu di sini.
// Namun, kita tetap butuh akses ke session untuk memeriksa role admin.
session_start();

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$message = '';
$message_type = '';

// LOGIKA UPDATE & CREATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_question'])) {
    $id_question = $_POST['id_question'];
    $kode_soal = trim($_POST['kode_soal']);
    $isi_pertanyaan = trim($_POST['isi_pertanyaan']);

    if (empty($kode_soal) || empty($isi_pertanyaan)) {
        $message = 'Kode Soal dan Isi Pertanyaan tidak boleh kosong.';
        $message_type = 'danger';
    } else {
        // Jika ID kosong -> proses Tambah Baru
        if (empty($id_question)) {
            // Cek dulu jumlah pertanyaan saat ini sebelum menambah
            $count_res = $conn->query("SELECT COUNT(*) as total FROM tb_question");
            $current_count = $count_res->fetch_assoc()['total'];

            if ($current_count >= 10) {
                $message = 'Gagal: Batas maksimal 10 pertanyaan telah tercapai.';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO tb_question (kode_soal, isi_pertanyaan) VALUES (?, ?)");
                $stmt->bind_param("ss", $kode_soal, $isi_pertanyaan);
                if ($stmt->execute()) {
                    $message = 'Pertanyaan baru berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambah pertanyaan: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } 
        // Jika ID ada -> proses Edit
        else {
            $stmt = $conn->prepare("UPDATE tb_question SET kode_soal = ?, isi_pertanyaan = ? WHERE id_question = ?");
            $stmt->bind_param("ssi", $kode_soal, $isi_pertanyaan, $id_question);
            if ($stmt->execute()) {
                $message = 'Pertanyaan berhasil diperbarui!';
                $message_type = 'success';
            } else {
                $message = 'Gagal memperbarui pertanyaan: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// LOGIKA READ
$questions = [];
$result = $conn->query("SELECT id_question, kode_soal, isi_pertanyaan FROM tb_question ORDER BY id_question ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
}
$question_count = count($questions);
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pertanyaan - Admin</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main>
        <div class="container my-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="main-header">Manajemen Pertanyaan Kuesioner</h1>
                
                <?php if ($question_count < 10): ?>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Pertanyaan
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary" disabled data-bs-toggle="tooltip" title="Batas maksimal 10 pertanyaan telah tercapai.">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Pertanyaan
                    </button>
                <?php endif; ?>

            </div>

            <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#ID</th>
                                    <th style="width: 15%;">Kode Soal</th>
                                    <th style="width: 65%;">Isi Pertanyaan</th>
                                    <th style="width: 15%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($questions)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">Belum ada pertanyaan. Silakan tambahkan.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <th><?= $q['id_question'] ?></th>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($q['kode_soal']) ?></span></td>
                                        <td><?= htmlspecialchars($q['isi_pertanyaan']) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?= json_encode($q) ?>)'>
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer text-center">
        <div class="container"><span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span></div>
    </footer>

    <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="kelola_pertanyaan.php">
                    <div class="modal-header"><h5 class="modal-title" id="questionModalLabel">Form Pertanyaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_question" id="id_question">
                        <input type="hidden" name="save_question" value="1">
                        <div class="mb-3">
                            <label for="kode_soal" class="form-label">Kode Soal</label>
                            <input type="text" class="form-control" id="kode_soal" name="kode_soal" placeholder="Contoh: A1_Score" required>
                        </div>
                        <div class="mb-3">
                            <label for="isi_pertanyaan" class="form-label">Isi Pertanyaan</label>
                            <textarea class="form-control" id="isi_pertanyaan" name="isi_pertanyaan" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const questionModal = new bootstrap.Modal(document.getElementById('questionModal'));
        
        // Inisialisasi tooltip Bootstrap untuk tombol yang nonaktif
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        function openAddModal() {
            document.getElementById('questionModalLabel').innerText = 'Tambah Pertanyaan Baru';
            document.querySelector('#questionModal form').reset();
            document.getElementById('id_question').value = '';
            questionModal.show();
        }

        function openEditModal(question) {
            document.getElementById('questionModalLabel').innerText = 'Edit Pertanyaan';
            document.querySelector('#questionModal form').reset();
            document.getElementById('id_question').value = question.id_question;
            document.getElementById('kode_soal').value = question.kode_soal;
            document.getElementById('isi_pertanyaan').value = question.isi_pertanyaan;
            questionModal.show();
        }
    </script>
</body>
</html>