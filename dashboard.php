<?php
session_start();
// Jika tidak ada sesi login, kembalikan ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil role dari sesi untuk menentukan menu yang ditampilkan
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Menentukan judul halaman berdasarkan peran
$pageTitle = ($role == 'admin') ? "Admin Dashboard" : "User Dashboard";

// Inisialisasi variabel agar tidak error jika query gagal
$total_users = 0;
$total_tests = 0;
$total_asd = 0;
$active_model = "N/A";
$recent_tests = [];

// Hanya jalankan query jika rolenya adalah admin
if ($role == 'admin') {
    require 'db/koneksi.php'; 
    if ($conn && !$conn->connect_error) {
        $result_users = $conn->query('SELECT count(id) FROM tb_user');
        if ($result_users) $total_users = $result_users->fetch_row()[0];

        $result_tests = $conn->query('SELECT count(id_input) FROM tb_input');
        if ($result_tests) $total_tests = $result_tests->fetch_row()[0];

        $result_asd = $conn->query("SELECT count(id_result) FROM tb_result WHERE hasil_prediksi = 'ASD'");
        if ($result_asd) $total_asd = $result_asd->fetch_row()[0];
        
        $result_model = $conn->query("SELECT nama_model FROM tb_model WHERE aktif = 1 LIMIT 1");
        if ($result_model && $result_model->num_rows > 0) {
            $active_model = $result_model->fetch_assoc()['nama_model'];
        }

        $result_recent = $conn->query("SELECT i.nama_lengkap, r.hasil_prediksi, i.timestamp FROM tb_input i JOIN tb_result r ON i.id_input = r.id_input ORDER BY i.timestamp DESC LIMIT 5");
        if ($result_recent) {
            while ($row = $result_recent->fetch_assoc()) {
                $recent_tests[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Autism Early Detector</title>
    <link rel="icon" href="images/aed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styledash.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main>
        <div class="container my-5">
            <?php if ($role == 'admin'): ?>
                <div class="text-center mb-5"><h1 class="main-header display-5">Admin Dashboard</h1><p class="lead text-muted">Ringkasan aktivitas dan data dari sistem.</p></div>
                <div class="row g-4 mb-5 text-center"><div class="col-md-6 col-lg-3"><div class="card p-3 shadow-sm"><div class="card-body"><h3 class="display-6 fw-bold"><?= $total_users ?></h3><p class="text-muted mb-0">Total Pengguna</p></div></div></div><div class="col-md-6 col-lg-3"><div class="card p-3 shadow-sm"><div class="card-body"><h3 class="display-6 fw-bold"><?= $total_tests ?></h3><p class="text-muted mb-0">Total Tes Dilakukan</p></div></div></div><div class="col-md-6 col-lg-3"><div class="card p-3 shadow-sm"><div class="card-body"><h3 class="display-6 fw-bold" style="color: #20c997;"><?= $total_asd ?></h3><p class="text-muted mb-0">Terindikasi ASD</p></div></div></div><div class="col-md-6 col-lg-3"><div class="card p-3 shadow-sm"><div class="card-body"><h3 class="display-6 fw-bold fs-4"><?= htmlspecialchars($active_model) ?></h3><p class="text-muted mb-0">Model Aktif</p></div></div></div></div>
                <h4 class="mb-4">Aktivitas Tes Terbaru</h4>
                <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Nama Peserta</th><th>Waktu Tes</th><th>Hasil Prediksi</th></tr></thead><tbody><?php if (empty($recent_tests)): ?><tr><td colspan="3" class="text-center text-muted">Belum ada aktivitas tes.</td></tr><?php else: ?><?php foreach ($recent_tests as $test): ?><tr><td><?= htmlspecialchars($test['nama_lengkap']) ?></td><td><?= date('d M Y, H:i', strtotime($test['timestamp'])) ?></td><td><?php if ($test['hasil_prediksi'] == 'ASD'): ?><span class="badge bg-warning text-dark">ASD</span><?php else: ?><span class="badge bg-success">Non-ASD</span><?php endif; ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
                <hr class="my-5"><h4 class="text-center mb-4">Akses Cepat</h4>
                <div class="row g-4 justify-content-center"><div class="col-md-6 col-lg-3"><a href="admin/data_user.php" class="menu-card d-block p-4 text-center"><i class="bi bi-people-fill card-icon"></i><h5 class="mt-3 card-title">Kelola User</h5></a></div><div class="col-md-6 col-lg-3"><a href="admin/data_prediksi.php" class="menu-card d-block p-4 text-center"><i class="bi bi-person-lines-fill card-icon"></i><h5 class="mt-3 card-title">Data prediksi</h5></a></div><div class="col-md-6 col-lg-3"><a href="admin/kelola_pertanyaan.php" class="menu-card d-block p-4 text-center"><i class="bi bi-patch-question-fill card-icon"></i><h5 class="mt-3 card-title">Kelola Pertanyaan</h5></a></div><div class="col-md-6 col-lg-3"><a href="admin/kelola_model.php" class="menu-card d-block p-4 text-center"><i class="bi bi-robot card-icon"></i><h5 class="mt-3 card-title">Kelola Model</h5></a></div></div>

            <?php else: ?>
                <div class="text-center mb-5">
                    <h1 class="main-header display-5">Selamat Datang, <?= htmlspecialchars($username) ?>!</h1>
                    <p class="lead text-muted">
                        Silakan mulai proses skrining dengan mengikuti langkah-langkah di bawah ini.
                    </p>
                </div>
                
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <a href="backend/form_identitas.php" class="menu-card d-block p-4 text-center h-100">
                            <i class="bi bi-person-vcard card-icon"></i>
                            <h5 class="mt-3 card-title">Langkah 1: Isi Identitas</h5>
                            <p class="card-text small text-muted">Isi data demografis lengkap dari peserta tes.</p>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="backend/kuisioner.php" class="menu-card d-block p-4 text-center h-100">
                            <i class="bi bi-pencil-square card-icon"></i>
                            <h5 class="mt-3 card-title">Langkah 2: Isi Kuisioner</h5>
                            <p class="card-text small text-muted">Jawab 10 pertanyaan skrining autisme (AQ-10).</p>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="backend/riwayat_tes.php" class="menu-card d-block p-4 text-center h-100">
                            <i class="bi bi-file-earmark-medical-fill card-icon"></i>
                            <h5 class="mt-3 card-title">Lihat Riwayat Hasil</h5>
                            <p class="card-text small text-muted">Lihat kembali semua riwayat hasil tes yang pernah Anda lakukan.</p>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="backend/index.php" class="menu-card d-block p-4 text-center h-100">
                            <i class="bi bi-file-earmark-medical-fill card-icon"></i>
                            <h5 class="mt-3 card-title">input data</h5>
                            <p class="card-text small text-muted">Input data nama.</p>
                        </a>
                    </div>
                </div>

                <hr class="my-5">

                <div class="p-4 rounded-3" style="background-color: #f1f3f5;">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <h4 class="text-center mb-4">Informasi Seputar Autisme</h4>
                            <div class="accordion" id="infoAccordion">
                                <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne"><i class="bi bi-patch-question-fill me-2" style="color: #20c997;"></i><strong>Mitos vs. Fakta</strong></button></h2><div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#infoAccordion"><div class="accordion-body"><p><strong>Mitos:</strong> Autisme disebabkan oleh pola asuh yang buruk.<br><strong>Fakta:</strong> Autisme adalah gangguan perkembangan saraf. Pola asuh tidak menyebabkannya, namun dukungan keluarga sangat penting.</p><hr><p><strong>Mitos:</strong> Individu dengan autisme tidak punya empati.<br><strong>Fakta:</strong> Mereka merasakan emosi, namun cara menunjukkan atau memahaminya bisa berbeda.</p></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"><i class="bi bi-sign-turn-right-fill me-2" style="color: #20c997;"></i><strong>Langkah Selanjutnya</strong></button></h2><div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#infoAccordion"><div class="accordion-body"><p>Penting untuk diingat bahwa hasil dari aplikasi ini <strong>bukanlah sebuah diagnosis medis</strong>. Ini hanyalah alat skrining awal untuk melihat adanya indikasi.</p><ul><li><strong>Jika hasil menunjukkan indikasi,</strong> jangan panik. Gunakan hasil ini sebagai dasar untuk memulai percakapan dengan profesional.</li><li><strong>Konsultasikan dengan Ahli:</strong> Langkah terbaik adalah berkonsultasi dengan dokter anak, psikolog, atau psikiater anak untuk asesmen yang lebih mendalam.</li><li><strong>Amati dan Catat:</strong> Catat perilaku dan perkembangan anak sehari-hari untuk dibagikan kepada ahli saat konsultasi.</li></ul></div></div></div><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree"><i class="bi bi-link-45deg me-2" style="color: #20c997;"></i><strong>Sumber Terpercaya</strong></button></h2><div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#infoAccordion"><div class="accordion-body"><ul><li><a href="https://www.kemkes.go.id" target="_blank" rel="noopener noreferrer">Kementerian Kesehatan RI</a></li><li><a href="https://autismindonesia.org/id/" target="_blank" rel="noopener noreferrer">Yayasan Autisme Indonesia (MPATI)</a></li><li><a href="https://www.who.int/news-room/fact-sheets/detail/autism-spectrum-disorders" target="_blank" rel="noopener noreferrer">World Health Organization (WHO)</a></li></ul></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
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