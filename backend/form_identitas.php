<?php
session_start();

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$sweet_alert_script = '';

// PROSES FORM SAAT DISUBMIT
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil semua data dari form
    $id_user = $_SESSION['user_id'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $gender = $_POST['gender'] ?? null;
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $ethnicity = $_POST['ethnicity'] ?? null;
    $jaundice = $_POST['jaundice'] ?? null;
    $austim = $_POST['austim'] ?? null;
    $contry_of_res = $_POST['contry_of_res'] ?? null;
    $relation = $_POST['relation'] ?? null;
    
    // Validasi input wajib
    if (empty($nama_lengkap) || empty($gender) || !$age || $age <= 0 || empty($ethnicity) || empty($jaundice) || empty($austim) || empty($contry_of_res) || empty($relation)) {
        $sweet_alert_script = "Swal.fire('Error!', 'Semua kolom wajib diisi dengan benar. Mohon periksa kembali isian Anda.', 'error');";
    } else {
        $photo_path = null;
        $upload_ok = true; // Flag untuk status upload

        // Proses upload foto hanya jika file dipilih
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $target_dir = "../uploads/photos/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            $unique_filename = "input_" . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $unique_filename;
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                $sweet_alert_script = "Swal.fire('Error!', 'Hanya file gambar (JPG, JPEG, PNG, GIF) yang diizinkan.', 'error');";
                $upload_ok = false;
            }

            if ($upload_ok) {
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    $photo_path = "uploads/photos/" . $unique_filename;
                } else {
                    $sweet_alert_script = "Swal.fire('Error!', 'Terjadi kesalahan saat mengunggah file foto.', 'error');";
                    $upload_ok = false;
                }
            }
        }

        // Lanjutkan ke database HANYA jika tidak ada error upload
        if ($upload_ok) {
            $stmt = $conn->prepare(
                "INSERT INTO tb_input (id_user, nama_lengkap, gender, age, ethnicity, jaundice, austim, contry_of_res, relation, photo) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ississssss", $id_user, $nama_lengkap, $gender, $age, $ethnicity, $jaundice, $austim, $contry_of_res, $relation, $photo_path);
            
            if ($stmt->execute()) {
                $new_input_id = $conn->insert_id;
                $_SESSION['current_input_id'] = $new_input_id;

                $sweet_alert_script = "
                    Swal.fire({
                        icon: 'success',
                        title: 'Data Identitas Tersimpan!',
                        text: 'Anda akan diarahkan ke halaman kuesioner.',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = 'kuisioner.php';
                    });
                ";
            } else {
                $sweet_alert_script = "Swal.fire('Error!', 'Gagal menyimpan data ke database: " . $conn->error . "', 'error');";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langkah 1: Identitas Peserta - Autism Early Detector</title>
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
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Langkah 1: Isi Identitas Peserta Tes</h4>
                        </div>
                        <div class="card-body p-4">
                            <p class="card-text text-muted mb-4">Mohon isi data pada form di bawah ini sesuai dengan pertanyaan dan pilihan yang disediakan.</p>
                            
                            <form method="POST" action="form_identitas.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap Peserta</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="age" class="form-label">Umur (Tahun)</label>
                                        <input type="number" class="form-control" id="age" name="age" min="1" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required>
                                                <label class="form-check-label" for="male">Laki-laki</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gender" id="female" value="Female">
                                                <label class="form-check-label" for="female">Perempuan</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="ethnicity" class="form-label">Etnis/Suku</label>
                                        <select class="form-select" id="ethnicity" name="ethnicity" required>
                                            <option value="Asian" selected>Asian</option>
                                            <option value="White-European">White-European</option>
                                            <option value="Middle Eastern">Middle Eastern</option>
                                            <option value="South Asian">South Asian</option>
                                            <option value="Black">Black</option>
                                            <option value="Hispanic">Hispanic</option>
                                            <option value="Latino">Latino</option>
                                            <option value="Pasifika">Pasifika</option>
                                            <option value="Turkish">Turkish</option>
                                            <option value="others">others</option>
                                            <option value="Others">Others</option>
                                            <option value="Unknown">Unknown</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contry_of_res" class="form-label">Negara Tempat Tinggal</label>
                                        <select class="form-select" id="contry_of_res" name="contry_of_res" required>
                                            <option value="Indonesia" selected>Indonesia</option>
                                            <option value="Afghanistan">Afghanistan</option>
                                            <option value="AmericanSamoa">American Samoa</option>
                                            <option value="Angola">Angola</option>
                                            <option value="Argentina">Argentina</option>
                                            <option value="Armenia">Armenia</option>
                                            <option value="Aruba">Aruba</option>
                                            <option value="Australia">Australia</option>
                                            <option value="Austria">Austria</option>
                                            <option value="Azerbaijan">Azerbaijan</option>
                                            <option value="Bahamas">Bahamas</option>
                                            <option value="Bangladesh">Bangladesh</option>
                                            <option value="Belgium">Belgium</option>
                                            <option value="Bolivia">Bolivia</option>
                                            <option value="Brazil">Brazil</option>
                                            <option value="Burundi">Burundi</option>
                                            <option value="Canada">Canada</option>
                                            <option value="China">China</option>
                                            <option value="Cyprus">Cyprus</option>
                                            <option value="Czech Republic">Czech Republic</option>
                                            <option value="Egypt">Egypt</option>
                                            <option value="Ethiopia">Ethiopia</option>
                                            <option value="France">France</option>
                                            <option value="Germany">Germany</option>
                                            <option value="Hong Kong">Hong Kong</option>
                                            <option value="Iceland">Iceland</option>
                                            <option value="India">India</option>
                                            <option value="Iran">Iran</option>
                                            <option value="Iraq">Iraq</option>
                                            <option value="Ireland">Ireland</option>
                                            <option value="Italy">Italy</option>
                                            <option value="Japan">Japan</option>
                                            <option value="Jordan">Jordan</option>
                                            <option value="Kazakhstan">Kazakhstan</option>
                                            <option value="Malaysia">Malaysia</option>
                                            <option value="Mexico">Mexico</option>
                                            <option value="Netherlands">Netherlands</option>
                                            <option value="New Zealand">New Zealand</option>
                                            <option value="Nicaragua">Nicaragua</option>
                                            <option value="Niger">Niger</option>
                                            <option value="Oman">Oman</option>
                                            <option value="Pakistan">Pakistan</option>
                                            <option value="Romania">Romania</option>
                                            <option value="Russia">Russia</option>
                                            <option value="Saudi Arabia">Saudi Arabia</option>
                                            <option value="Serbia">Serbia</option>
                                            <option value="Sierra Leone">Sierra Leone</option>
                                            <option value="South Africa">South Africa</option>
                                            <option value="Spain">Spain</option>
                                            <option value="Sri Lanka">Sri Lanka</option>
                                            <option value="Sweden">Sweden</option>
                                            <option value="Tonga">Tonga</option>
                                            <option value="Ukraine">Ukraine</option>
                                            <option value="United Arab Emirates">United Arab Emirates</option>
                                            <option value="United Kingdom">United Kingdom</option>
                                            <option value="United States">United States</option>
                                            <option value="Viet Nam">Viet Nam</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Apakah peserta pernah mengalami sakit kuning saat lahir?</label>
                                        <div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="jaundice" id="jaundice_yes" value="yes" required><label class="form-check-label" for="jaundice_yes">Ya</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="jaundice" id="jaundice_no" value="no"><label class="form-check-label" for="jaundice_no">Tidak</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Apakah ada riwayat autisme dalam keluarga dekat?</label>
                                        <div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="austim" id="family_yes" value="yes" required><label class="form-check-label" for="family_yes">Ya</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="austim" id="family_no" value="no"><label class="form-check-label" for="family_no">Tidak</label></div>
                                        </div>
                                    </div>
                                </div>
                                 <div class="mb-3">
                                    <label for="relation" class="form-label">Anda mengisi kuesioner ini sebagai?</label>
                                    <select class="form-select" id="relation" name="relation" required>
                                        <option value="">-- Pilih Hubungan --</option>
                                        <option value="Parent">Orang Tua</option>
                                        <option value="Self">Diri Sendiri</option>
                                        <option value="Relative">Kerabat</option>
                                        <option value="Health care professional">Tenaga Kesehatan Profesional</option>
                                        <option value="Others">Lainnya</option>
                                        <option value="?">Tidak Diketahui</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="photo" class="form-label">Upload Foto Peserta (Opsional)</label>
                                    <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Simpan dan Lanjutkan ke Kuesioner <i class="bi bi-arrow-right-circle-fill"></i>
                                    </button>
                                </div>
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