<?php
session_start();

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
// Keamanan: Pastikan ada id_input yang valid di URL
if (!isset($_GET['id_input']) || !is_numeric($_GET['id_input'])) {
    header("Location: ../dashboard.php");
    exit;
}

require '../db/koneksi.php';

$id_input = $_GET['id_input'];
$user_id = $_SESSION['user_id'];
$result_data = null;
$answers_data = [];

// Query untuk mengambil data utama hasil tes
$query_main = "SELECT 
                    i.nama_lengkap, i.age, i.timestamp, i.id_user,
                    r.total_score, r.hasil_prediksi, r.keterangan, r.feature_contributions
               FROM tb_input i 
               JOIN tb_result r ON i.id_input = r.id_input 
               WHERE i.id_input = ?";

$stmt_main = $conn->prepare($query_main);
$stmt_main->bind_param("i", $id_input);
$stmt_main->execute();
$result = $stmt_main->get_result();
$result_data = $result->fetch_assoc();
$stmt_main->close();

// Keamanan: Jika data tidak ditemukan ATAU jika role bukan admin DAN id_user tidak cocok, akses ditolak
if (!$result_data || ($_SESSION['role'] !== 'admin' && $result_data['id_user'] != $user_id)) {
    header("Location: ../dashboard.php?error=access_denied");
    exit;
}

// Ambil rincian jawaban dari tb_answer
$stmt_answers = $conn->prepare("
    SELECT q.isi_pertanyaan, a.jawaban, a.skor 
    FROM tb_answer a 
    JOIN tb_question q ON a.id_question = q.id_question 
    WHERE a.id_input = ? ORDER BY q.id_question ASC
");
$stmt_answers->bind_param("i", $id_input);
$stmt_answers->execute();
$result_answers = $stmt_answers->get_result();
while ($row = $result_answers->fetch_assoc()) {
    $answers_data[] = $row;
}
$stmt_answers->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Skrining - Autism Early Detector</title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styledash.css">
    <style>
        @media print {
            .navbar, .footer, .action-buttons, .accordion-button { display: none !important; }
            body, .card { box-shadow: none !important; border: none !important; background: #fff !important; }
            .accordion-collapse { display: block !important; visibility: visible !important; }
            .accordion-item, .card-header { border: none !important; }
            .card-body { padding: 0 !important; } .main-header { padding-top: 1rem; }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <main>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="text-center mb-4">
                        <h1 class="main-header">Hasil Skrining Awal Autisme</h1>
                        <p class="text-muted">Hasil untuk tes yang dilakukan pada <?= date('d F Y, H:i', strtotime($result_data['timestamp'])) ?>.</p>
                    </div>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4 text-center">
                            <h5 class="text-muted">Hasil Prediksi untuk:</h5>
                            <h3 class="mb-3"><?= htmlspecialchars($result_data['nama_lengkap']) ?> (<?= htmlspecialchars($result_data['age']) ?> tahun)</h3>
                            
                            <?php if ($result_data['hasil_prediksi'] == 'ASD'): ?>
                                <div class="alert alert-warning d-flex align-items-center" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill fs-2 me-3"></i>
                                    <div class="flex-grow-1">
                                        <h4 class="alert-heading text-center">Terindikasi Spektrum Autisme (ASD)</h4>
                                        <p class="mb-0">Hasil skrining menunjukkan adanya beberapa ciri yang mengarah pada spektrum autisme.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                                    <div class="flex-grow-1">
                                        <h4 class="alert-heading text-center">Tidak Terindikasi Spektrum Autisme (Non-ASD)</h4>
                                        <p class="mb-0">Hasil skrining menunjukkan sedikit atau tidak ada ciri yang mengarah pada spektrum autisme.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <p class="fs-5">Total Skor AQ-10: <span class="fw-bold" style="color: #20c997;"><?= htmlspecialchars($result_data['total_score']) ?> / 10</span></p>
                        </div>
                    </div>
                    <div class="card bg-light-subtle border-light-subtle mb-4">
                        <div class="card-body text-center p-3 py-3"><p class="fw-bold mb-1"><i class="bi bi-shield-fill-exclamation"></i> PENTING: Ini Bukan Diagnosis Medis</p><p class="small text-muted mb-0">Hasil ini adalah skrining awal dan tidak dapat menggantikan diagnosis dari profesional kesehatan. Jika Anda memiliki kekhawatiran, sangat disarankan untuk berkonsultasi dengan ahli.</p></div>
                    </div>
                    
                    <?php 
                    if (!empty($result_data['feature_contributions'])):
                        $contributions_raw = json_decode($result_data['feature_contributions'], true);
                        $contributions_sorted = [];
                        if (is_array($contributions_raw)) {
                            foreach ($contributions_raw as $feature => $value) {
                                if (is_numeric($value)) {
                                    $contributions_sorted[] = ['feature' => $feature, 'value' => $value];
                                }
                            }
                            usort($contributions_sorted, function ($a, $b) {
                                return abs($b['value']) <=> abs($a['value']);
                            });
                        }
                    ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-bar-chart-line-fill me-2"></i>Faktor-faktor yang Mempengaruhi Hasil</h5></div>
                        <div class="card-body">
                            <p class="small text-muted p-3 pb-0">Tabel ini menunjukkan 7 faktor teratas dan tingkat pengaruhnya terhadap hasil. Nilai positif mendorong ke arah 'ASD', nilai negatif mendorong ke arah 'Non-ASD'.</p>
                            <div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Faktor (Fitur)</th><th class="text-end">Tingkat Pengaruh (Bobot)</th></tr></thead>
                                <tbody>
                                    <?php $counter = 0; ?>
                                    <?php if (!empty($contributions_sorted)) foreach($contributions_sorted as $item): ?>
                                        <?php if ($counter >= 7) break; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['feature']) ?></td>
                                            <td class="text-end"><span class="badge <?= $item['value'] > 0.001 ? 'bg-warning text-dark' : 'bg-success' ?>"><?= number_format($item['value'], 4) ?></span></td>
                                        </tr>
                                        <?php $counter++; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="accordion" id="accordionAnswers">
                        <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">Lihat Rincian Jawaban & Skor</button></h2>
                            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionAnswers"><div class="accordion-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex fw-bold bg-light">
                                        <div class="col-8">Pertanyaan</div>
                                        <div class="col-2 text-center">Jawaban</div>
                                        <div class="col-2 text-center">Skor</div>
                                    </li>
                                    <?php foreach ($answers_data as $answer): ?>
                                        <li class="list-group-item d-flex align-items-center">
                                            <div class="col-8">
                                                <?= htmlspecialchars($answer['isi_pertanyaan']) ?>
                                            </div>
                                            <div class="col-2 text-center">
                                                <span class="badge bg-primary rounded-pill">
                                                    <?= htmlspecialchars($answer['jawaban']) ?>
                                                </span>
                                            </div>
                                            <div class="col-2 text-center">
                                                <span class="badge <?= $answer['skor'] == 1 ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                                                    +<?= htmlspecialchars($answer['skor']) ?>
                                                </span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div></div>
                        </div>
                    </div>

                    <div class="text-center mt-5 action-buttons">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="../admin/data_prediksi.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-2"></i>Kembali ke Laporan</a>
                            <button class="btn btn-info text-white" onclick="window.print()"><i class="bi bi-printer-fill me-2"></i>Cetak Halaman Ini</button>
                        <?php else: ?>
                            <a href="../dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-2"></i>Kembali ke Dashboard</a>
                            <button class="btn btn-info text-white" onclick="window.print()"><i class="bi bi-printer-fill me-2"></i>Cetak Hasil</button>
                            <a href="form_identitas.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Lakukan Tes Baru</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="footer text-center">
        <div class="container"><span>&copy; <?= date('Y') ?> - Duha Nur Pambudi (2021230031) - Universitas Darma Persada</span></div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>