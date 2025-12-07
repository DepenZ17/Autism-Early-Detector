<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Anda harus login sebagai admin.");
}
require '../db/koneksi.php';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$query = "SELECT r.id_result, i.nama_lengkap, i.timestamp, u.username, r.total_score, r.hasil_prediksi 
          FROM tb_result r
          JOIN tb_input i ON r.id_input = i.id_input
          JOIN tb_user u ON i.id_user = u.id";

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

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}
$query .= " ORDER BY i.timestamp DESC";

$stmt = $conn->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$all_predictions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$periode = "Semua Data";
if (!empty($start_date) || !empty($end_date)) {
    $tgl_mulai = !empty($start_date) ? date('d F Y', strtotime($start_date)) : '...';
    $tgl_selesai = !empty($end_date) ? date('d F Y', strtotime($end_date)) : '...';
    $periode = "Periode: $tgl_mulai - $tgl_selesai";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Prediksi ASD - <?= $periode ?></title>
    <link rel="icon" href="../images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; }
        .header-laporan { text-align: center; border-bottom: 3px double black; padding-bottom: 15px; margin-bottom: 25px;}
        .table { font-size: 11pt; color: #000; }
        .table th, .table td { border: 1px solid #000 !important; vertical-align: middle; }
        @page { size: A4 landscape; margin: 2cm; }
    </style>
</head>
<body onload="window.print(); window.onafterprint = window.close;">
    <div class="container-fluid">
        <div class="header-laporan">
            <h4>LAPORAN HASIL PREDIKSI AWAL INDIKASI AUTISME</h4>
            <h5>Sistem Deteksi Dini Menggunakan Metode Random Forest</h5>
            <h6><?= $periode ?></h6>
        </div>
        
        <table class="table table-bordered">
            <thead class="table-light text-center">
                <tr>
                    <th style="width:5%;">No.</th>
                    <th style="width:10%;">ID Hasil</th>
                    <th>Nama Peserta</th>
                    <th>Tanggal Tes</th>
                    <th style="width:8%;">Skor</th>
                    <th style="width:20%;">Hasil Prediksi Model</th>
                    <th>Diinput Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_predictions)): ?>
                    <tr><td colspan="7" class="text-center">Tidak ada data untuk periode yang dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($all_predictions as $index => $pred): ?>
                    <tr>
                        <td class="text-center"><?= $index + 1 ?></td>
                        <td class="text-center"><?= $pred['id_result'] ?></td>
                        <td><?= htmlspecialchars($pred['nama_lengkap']) ?></td>
                        <td><?= date('d-m-Y H:i', strtotime($pred['timestamp'])) ?></td>
                        <td class="text-center"><?= $pred['total_score'] ?></td>
                        <td><?= $pred['hasil_prediksi'] == 'ASD' ? 'Terindikasi Spektrum Autisme' : 'Tidak Terindikasi' ?></td>
                        <td><?= htmlspecialchars($pred['username']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>