<?php
session_start();

// Keamanan: Pastikan pengguna sudah login DAN sudah melalui form_identitas.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_input_id'])) {
    header("Location: form_identitas.php");
    exit;
}

require '../db/koneksi.php';

$id_input = $_SESSION['current_input_id'];
$sweet_alert_script = '';

// Kunci skoring final & akurat berdasarkan pertanyaan standar AQ-10
// Ini adalah ID pertanyaan di mana jawaban "Tidak" / "Tidak Setuju" mendapat skor 1
$reverse_scored_ids = [3, 6, 9, 10]; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $answers = $_POST['answers'] ?? [];
    
    if (count($answers) < 10) {
        $sweet_alert_script = "Swal.fire('Error!', 'Harap jawab semua 10 pertanyaan.', 'error');";
    } else {
        $conn->begin_transaction();
        try {
            // Hapus jawaban lama jika ada (untuk kasus user back dan submit ulang)
            $stmt_delete = $conn->prepare("DELETE FROM tb_answer WHERE id_input = ?");
            $stmt_delete->bind_param("i", $id_input);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            $stmt_insert = $conn->prepare("INSERT INTO tb_answer (id_input, id_question, jawaban, skor) VALUES (?, ?, ?, ?)");
            
            $total_score = 0;
            $scores_for_model = [];

            // Ambil semua kode soal dari DB untuk mapping
            $all_questions = [];
            $result_q = $conn->query("SELECT id_question, kode_soal FROM tb_question ORDER BY id_question ASC");
            while($row_q = $result_q->fetch_assoc()) {
                $all_questions[$row_q['id_question']] = $row_q['kode_soal'];
            }

            foreach ($answers as $id_question => $jawaban) {
                $skor = 0;
                // Logika skoring
                if (in_array($id_question, $reverse_scored_ids)) {
                    $skor = ($jawaban === 'Tidak') ? 1 : 0;
                } else {
                    $skor = ($jawaban === 'Ya') ? 1 : 0;
                }
                
                $total_score += $skor;
                $kode_soal = $all_questions[$id_question];
                $scores_for_model[$kode_soal] = $skor;

                // Simpan jawaban ke tb_answer
                $stmt_insert->bind_param("iisi", $id_input, $id_question, $jawaban, $skor);
                $stmt_insert->execute();
            }
            $stmt_insert->close();

            // Ambil data demografis dari tb_input untuk dikirim ke API
            $stmt_input = $conn->prepare("SELECT age, gender, ethnicity, jaundice, austim, contry_of_res, relation FROM tb_input WHERE id_input = ?");
            $stmt_input->bind_param("i", $id_input);
            $stmt_input->execute();
            $input_data = $stmt_input->get_result()->fetch_assoc();
            $stmt_input->close();

            // Gabungkan semua data fitur untuk dikirim ke API
            $data_for_api = array_merge($input_data, $scores_for_model);

            // Panggil API Python
            $ch = curl_init('http://127.0.0.1:5000/predict');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data_for_api),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            $api_response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error_curl = curl_error($ch);
            curl_close($ch);

            // Proses hasil dari API
            if ($httpcode == 200 && $api_response) {
                $response_data = json_decode($api_response, true);
                if (isset($response_data['error'])) {
                    throw new Exception("API Error: " . $response_data['error']);
                }
                $hasil_prediksi = $response_data['prediksi'] ?? 'Error';
                $feature_contributions = json_encode($response_data['kontribusi_fitur'] ?? []);
            } else {
                // Fallback jika API gagal
                $hasil_prediksi = ($total_score > 6) ? 'ASD' : 'Non-ASD';
                $feature_contributions = null;
                if ($error_curl) {
                     $message = "Gagal menghubungi server prediksi: " . $error_curl;
                     throw new Exception($message);
                }
            }
            
            $keterangan = "Total skor dari kuesioner adalah " . $total_score . ".";

            // Simpan hasil akhir, termasuk data kontribusi SHAP
            $stmt_result = $conn->prepare("INSERT INTO tb_result (id_input, total_score, hasil_prediksi, keterangan, feature_contributions) VALUES (?, ?, ?, ?, ?)");
            $stmt_result->bind_param("issss", $id_input, $total_score, $hasil_prediksi, $keterangan, $feature_contributions);
            $stmt_result->execute();
            $stmt_result->close();
            
            $conn->commit();
            unset($_SESSION['current_input_id']);

            // Redirect ke halaman hasil
            header("Location: hasil_prediksi.php?id_input=" . $id_input);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $sweet_alert_script = "Swal.fire('Error!', 'Terjadi kesalahan sistem: " . addslashes($e->getMessage()) . "', 'error');";
        }
    }
}

// Ambil pertanyaan untuk ditampilkan di form
$questions = [];
$result = $conn->query("SELECT id_question, isi_pertanyaan FROM tb_question ORDER BY id_question ASC");
if ($result && $result->num_rows >= 10) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
} else {
    die("Error: Konfigurasi pertanyaan di database tidak lengkap. Pastikan Anda sudah menjalankan skrip SQL yang benar.");
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langkah 2: Kuesioner AQ-10 - Autism Early Detector</title>
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
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Langkah 2: Jawab Kuesioner</h4></div>
                        <div class="card-body p-4">
                            <p class="card-text text-muted mb-4">Jawablah 10 pertanyaan berikut dengan memilih "Ya" (jika Anda setuju) atau "Tidak" (jika tidak setuju).</p>
                            <form method="POST" action="kuisioner.php">
                                <?php foreach ($questions as $index => $q): ?>
                                    <div class="mb-4 p-3 border rounded bg-light">
                                        <p class="fw-bold mb-2"><?= ($index + 1) . ". " . htmlspecialchars($q['isi_pertanyaan']) ?></p>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="answers[<?= $q['id_question'] ?>]" id="q<?= $q['id_question'] ?>_yes" value="Ya" required>
                                                <label class="form-check-label" for="q<?= $q['id_question'] ?>_yes">Ya / Setuju</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="answers[<?= $q['id_question'] ?>]" id="q<?= $q['id_question'] ?>_no" value="Tidak">
                                                <label class="form-check-label" for="q<?= $q['id_question'] ?>_no">Tidak / Tidak Setuju</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="d-grid mt-4"><button type="submit" class="btn btn-success btn-lg">Selesai & Lihat Hasil <i class="bi bi-check-circle-fill"></i></button></div>
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
        document.addEventListener('DOMContentLoaded', function() { <?php echo $sweet_alert_script; ?> });
    </script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>