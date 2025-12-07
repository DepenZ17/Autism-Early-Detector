<?php
session_start();

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$user_id = $_SESSION['user_id'];

// --- LOGIKA PAGINASI ---
// 1. Tentukan jumlah record per halaman
$records_per_page = 10;

// 2. Hitung total record yang dimiliki user
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM tb_input WHERE id_user = ?");
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$total_records = $stmt_total->get_result()->fetch_assoc()['total'];
$stmt_total->close();

// 3. Hitung total halaman
$total_pages = ceil($total_records / $records_per_page);

// 4. Tentukan halaman saat ini
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}
if ($current_page < 1) {
    $current_page = 1;
}

// 5. Hitung OFFSET untuk query SQL
$offset = ($current_page - 1) * $records_per_page;

// --- LOGIKA READ DENGAN PAGINASI ---
$riwayat_list = [];
$query = "SELECT 
            i.id_input, i.nama_lengkap, i.timestamp, 
            r.total_score, r.hasil_prediksi 
          FROM tb_input i
          LEFT JOIN tb_result r ON i.id_input = r.id_input
          WHERE i.id_user = ?
          ORDER BY i.timestamp DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $riwayat_list[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Tes Anda - Autism Early Detector</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="container my-5">
        <h1 class="main-header">Riwayat Tes Anda</h1>
        <p class="text-muted">Berikut adalah daftar semua tes skrining yang pernah Anda lakukan.</p>

        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#ID Tes</th>
                                <th>Nama Peserta</th>
                                <th>Tanggal Tes</th>
                                <th class="text-center">Skor</th>
                                <th class="text-center">Hasil Prediksi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($riwayat_list)): ?>
                                <tr><td colspan="6" class="text-center text-muted p-4">Anda belum memiliki riwayat tes. <a href="form_identitas.php">Mulai tes sekarang</a>.</td></tr>
                            <?php else: ?>
                                <?php foreach ($riwayat_list as $tes): ?>
                                <tr>
                                    <th><?= $tes['id_input'] ?></th>
                                    <td><?= htmlspecialchars($tes['nama_lengkap']) ?></td>
                                    <td><?= date('d F Y, H:i', strtotime($tes['timestamp'])) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary fs-6"><?= $tes['total_score'] ?? 'N/A' ?></span></td>
                                    <td class="text-center">
                                        <?php if (isset($tes['hasil_prediksi'])): ?>
                                            <?php if ($tes['hasil_prediksi'] == 'ASD'): ?>
                                                <span class="badge bg-warning text-dark">Terindikasi ASD</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Tidak Terindikasi</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">Belum Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (isset($tes['hasil_prediksi'])): ?>
                                        <a href="hasil_prediksi.php?id_input=<?= $tes['id_input'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye-fill"></i> Lihat Hasil & Cetak
                                        </a>
                                        <?php endif; ?>
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
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <footer class="footer text-center">
        <div class="container">
            <span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>