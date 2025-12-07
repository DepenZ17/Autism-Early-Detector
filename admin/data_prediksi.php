<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$message = '';
$message_type = '';

// LOGIKA CRUD (UPDATE & DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Proses Delete
    if (isset($_POST['delete_prediksi'])) {
        $id_input_to_delete = $_POST['id_input'];
        $conn->begin_transaction();
        try {
            $stmt_get_photo = $conn->prepare("SELECT photo FROM tb_input WHERE id_input = ?");
            $stmt_get_photo->bind_param("i", $id_input_to_delete);
            $stmt_get_photo->execute();
            $result_photo = $stmt_get_photo->get_result()->fetch_assoc();
            if ($result_photo && !empty($result_photo['photo']) && file_exists("../" . $result_photo['photo'])) {
                unlink("../" . $result_photo['photo']);
            }
            $stmt_get_photo->close();

            // Menggunakan prepared statement untuk keamanan
            $stmt_del_ans = $conn->prepare("DELETE FROM tb_answer WHERE id_input = ?");
            $stmt_del_ans->bind_param("i", $id_input_to_delete);
            $stmt_del_ans->execute();
            $stmt_del_ans->close();

            $stmt_del_res = $conn->prepare("DELETE FROM tb_result WHERE id_input = ?");
            $stmt_del_res->bind_param("i", $id_input_to_delete);
            $stmt_del_res->execute();
            $stmt_del_res->close();

            $stmt_del_inp = $conn->prepare("DELETE FROM tb_input WHERE id_input = ?");
            $stmt_del_inp->bind_param("i", $id_input_to_delete);
            $stmt_del_inp->execute();
            $stmt_del_inp->close();

            $conn->commit();
            $message = 'Data prediksi dan semua data terkait berhasil dihapus!';
            $message_type = 'success';
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = 'Gagal menghapus data: ' . $exception->getMessage();
            $message_type = 'danger';
        }
    }

    // Proses Update
    if (isset($_POST['save_prediksi'])) {
        $id_result = $_POST['id_result'];
        $hasil_prediksi = $_POST['hasil_prediksi'];
        $keterangan = trim($_POST['keterangan']);

        $stmt = $conn->prepare("UPDATE tb_result SET hasil_prediksi = ?, keterangan = ? WHERE id_result = ?");
        $stmt->bind_param("ssi", $hasil_prediksi, $keterangan, $id_result);

        if ($stmt->execute()) {
            $message = 'Hasil prediksi berhasil diperbarui!';
            $message_type = 'success';
        } else {
            $message = 'Gagal memperbarui hasil: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// --- LOGIKA FILTER TANGGAL & PAGINASI ---
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$base_query = "FROM tb_result r JOIN tb_input i ON r.id_input = i.id_input JOIN tb_user u ON i.id_user = u.id";
$where_conditions = [];
$params = [];
$types = '';

if (!empty($start_date)) {
    $where_conditions[] = "DATE(i.timestamp) >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $where_conditions[] = "DATE(i.timestamp) <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

$total_query = "SELECT COUNT(r.id_result) as total " . $base_query . $where_clause;
$stmt_total = $conn->prepare($total_query);
if (!empty($types)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$stmt_total->close();

$records_per_page = 10;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

$predictions = [];
$data_query = "SELECT r.id_result, i.id_input, i.nama_lengkap, i.timestamp, u.username, r.total_score, r.hasil_prediksi, r.keterangan " . $base_query . $where_clause . " ORDER BY i.timestamp DESC LIMIT ? OFFSET ?";
$stmt_data = $conn->prepare($data_query);

$current_types = $types . 'ii';
$current_params = array_merge($params, [$records_per_page, $offset]);
$stmt_data->bind_param($current_types, ...$current_params);
$stmt_data->execute();
$result_predictions = $stmt_data->get_result();
if ($result_predictions) { while ($row = $result_predictions->fetch_assoc()) { $predictions[] = $row; } }
$stmt_data->close();

$details = [];
if (!empty($predictions)) {
    $prediction_input_ids = array_map(function($p) { return $p['id_input']; }, $predictions);
    $id_list = implode(',', $prediction_input_ids);
    if (!empty($id_list)) {
        $query_details = "SELECT i.*, q.isi_pertanyaan, a.jawaban FROM tb_input i LEFT JOIN tb_answer a ON i.id_input = a.id_input LEFT JOIN tb_question q ON a.id_question = q.id_question WHERE i.id_input IN ($id_list) ORDER BY i.id_input, q.id_question ASC";
        $result_details = $conn->query($query_details);
        if($result_details) {
            while($row = $result_details->fetch_assoc()){
                $details[$row['id_input']]['demographics'] = $row;
                $details[$row['id_input']]['answers'][] = ['question' => $row['isi_pertanyaan'], 'answer' => $row['jawaban']];
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil Prediksi - Admin</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="container my-5">
        <h1 class="main-header">Laporan Hasil Prediksi</h1>
        <p class="text-muted">Gunakan filter di bawah untuk memilih rentang tanggal, lalu cetak laporan.</p>
        
        <div class="card shadow-sm mb-4">
    <div class="card-body">
        <form id="filterForm" method="GET" action="data_prediksi.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="col-md-5">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2" title="Terapkan Filter"><i class="bi bi-filter"></i></button>
                <a href="data_prediksi.php" class="btn btn-secondary w-100" title="Hapus Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Hasil Prediksi</h5>
                <button class="btn btn-success" onclick="printReport()">
                    <i class="bi bi-printer-fill me-2"></i>Cetak Laporan (Sesuai Filter)
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID Hasil</th>
                                <th>Nama Peserta</th>
                                <th class="text-center">Skor</th>
                                <th class="text-center">Hasil Prediksi</th>
                                <th>Diinput Oleh</th>
                                <th>Tanggal Tes</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($predictions)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-4">Tidak ada data untuk filter yang dipilih. <a href="data_prediksi.php">Reset filter</a>.</td></tr>
                            <?php else: ?>
                                <?php foreach ($predictions as $pred): ?>
                                <tr>
                                    <th><?= $pred['id_result'] ?></th>
                                    <td>
                                        <img src="../<?= htmlspecialchars($details[$pred['id_input']]['demographics']['photo'] ?? 'images/default-avatar.png') ?>" alt="Avatar" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                        <?= htmlspecialchars($pred['nama_lengkap']) ?>
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary fs-6"><?= $pred['total_score'] ?? 'N/A' ?></span></td>
                                    <td class="text-center"><span class="badge <?= $pred['hasil_prediksi'] == 'ASD' ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $pred['hasil_prediksi'] == 'ASD' ? 'Terindikasi ASD' : 'Tidak Terindikasi' ?></span></td>
                                    <td><?= htmlspecialchars($pred['username']) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($pred['timestamp'])) ?></td>
                                    <td class="text-center">
                                        <a href="../backend/hasil_prediksi.php?id_input=<?= $pred['id_input'] ?>" class="btn btn-sm btn-info text-white" title="Lihat Rincian Detail"><i class="bi bi-search"></i> Rincian</a>
                                        <button class="btn btn-sm btn-warning" onclick='openEditModal(<?= htmlspecialchars(json_encode($pred), ENT_QUOTES, 'UTF-8') ?>)' title="Edit Hasil"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-danger" onclick="deletePrediction(<?= $pred['id_input'] ?>, '<?= htmlspecialchars(addslashes($pred['nama_lengkap']), ENT_QUOTES, 'UTF-8') ?>')" title="Hapus Data"><i class="bi bi-trash-fill"></i></button>
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
                        <?php $filter_params = http_build_query(['start_date' => $start_date, 'end_date' => $end_date]); ?>
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $current_page - 1 ?>&<?= $filter_params ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&<?= $filter_params ?>"><?= $i ?></a></li><?php endfor; ?>
                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $current_page + 1 ?>&<?= $filter_params ?>">Next</a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer class="footer text-center"><div class="container"><span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span></div></footer>

    <div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="data_prediksi.php"><div class="modal-header"><h5 class="modal-title">Edit Hasil Prediksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="id_result" id="editResultId"><div class="mb-3"><label class="form-label">Hasil Prediksi</label><select name="hasil_prediksi" id="editHasilPrediksi" class="form-select"><option value="ASD">ASD (Terindikasi)</option><option value="Non-ASD">Non-ASD (Tidak Terindikasi)</option></select></div><div class="mb-3"><label class="form-label">Keterangan / Catatan Admin</label><textarea name="keterangan" id="editKeterangan" class="form-control" rows="4"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="save_prediksi" class="btn btn-primary">Simpan Perubahan</button></div></form></div></div></div>
    <div class="modal fade" id="detailsModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Rincian Data Tes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><h6>Data Demografis Peserta</h6><table class="table table-bordered table-sm" id="demographicsTable"></table><h6 class="mt-4">Rincian Jawaban Kuesioner</h6><ul class="list-group" id="answersList"></ul></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
    <form method="POST" action="data_prediksi.php" id="deleteForm" style="display: none;"><input type="hidden" name="id_input" id="deleteInputId"><input type="hidden" name="delete_prediksi" value="1"></form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        function printReport() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            window.open(`template_cetak_laporan.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
        }
        function openEditModal(pred) { document.getElementById('editResultId').value = pred.id_result; document.getElementById('editHasilPrediksi').value = pred.hasil_prediksi; document.getElementById('editKeterangan').value = pred.keterangan; editModal.show(); }
        function viewDetails(details) { if (!details || !details.demographics) { Swal.fire('Error', 'Data rincian tidak lengkap atau tidak ditemukan.', 'error'); return; } const demo = details.demographics; const demoTable = document.getElementById('demographicsTable'); demoTable.innerHTML = `<tbody><tr><th>Nama Lengkap</th><td>${demo.nama_lengkap || ''}</td><th>Umur</th><td>${demo.age || ''} tahun</td></tr><tr><th>Jenis Kelamin</th><td>${demo.gender || ''}</td><th>Etnis</th><td>${demo.ethnicity || ''}</td></tr><tr><th>Negara</th><td>${demo.contry_of_res || ''}</td><th>Hubungan</th><td>${demo.relation || ''}</td></tr><tr><th>Riwayat Sakit Kuning</th><td>${demo.jaundice || ''}</td><th>Riwayat Autisme Keluarga</th><td>${demo.austim || ''}</td></tr></tbody>`; const answersList = document.getElementById('answersList'); answersList.innerHTML = ''; if (details.answers && details.answers.length > 0 && details.answers[0].question) { details.answers.forEach(item => { const li = document.createElement('li'); li.className = 'list-group-item d-flex justify-content-between align-items-center'; li.innerHTML = `<span>${item.question}</span> <span class="badge bg-primary rounded-pill">${item.answer}</span>`; answersList.appendChild(li); }); } else { answersList.innerHTML = '<li class="list-group-item text-muted">Tidak ada rincian jawaban yang tersimpan.</li>'; } detailsModal.show(); }
        function deletePrediction(id_input, name) { Swal.fire({ title: 'Anda yakin?', text: `Anda akan menghapus seluruh data tes untuk "${name}". Tindakan ini tidak dapat dibatalkan!`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal' }).then((result) => { if (result.isConfirmed) { document.getElementById('deleteInputId').value = id_input; document.getElementById('deleteForm').submit(); } }); }
    </script>
</body>
</html>