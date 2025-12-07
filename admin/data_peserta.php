<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../db/koneksi.php';

$message = '';
$message_type = '';

// LOGIKA CRUD
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // PROSES DELETE
    if (isset($_POST['delete_peserta'])) {
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

            $stmt_del_answer = $conn->prepare("DELETE FROM tb_answer WHERE id_input = ?");
            $stmt_del_answer->bind_param("i", $id_input_to_delete);
            $stmt_del_answer->execute();
            $stmt_del_answer->close();

            $stmt_del_result = $conn->prepare("DELETE FROM tb_result WHERE id_input = ?");
            $stmt_del_result->bind_param("i", $id_input_to_delete);
            $stmt_del_result->execute();
            $stmt_del_result->close();

            $stmt_del_input = $conn->prepare("DELETE FROM tb_input WHERE id_input = ?");
            $stmt_del_input->bind_param("i", $id_input_to_delete);
            $stmt_del_input->execute();
            $stmt_del_input->close();

            $conn->commit();
            $message = 'Data peserta dan semua data terkait berhasil dihapus!';
            $message_type = 'success';

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = 'Gagal menghapus data: ' . $exception->getMessage();
            $message_type = 'danger';
        }
    }

    // PROSES CREATE & UPDATE
    if (isset($_POST['save_peserta'])) {
        $id_input = $_POST['id_input'];
        $id_user = $_POST['id_user'];
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $gender = $_POST['gender'];
        $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
        $ethnicity = $_POST['ethnicity'];
        $jaundice = $_POST['jaundice'];
        $austim = $_POST['austim'];
        $contry_of_res = $_POST['contry_of_res'];
        $relation = $_POST['relation'];
        
        // Jika ID kosong -> Tambah Baru
        if (empty($id_input)) {
            $stmt = $conn->prepare("INSERT INTO tb_input (id_user, nama_lengkap, gender, age, ethnicity, jaundice, austim, contry_of_res, relation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississsss", $id_user, $nama_lengkap, $gender, $age, $ethnicity, $jaundice, $austim, $contry_of_res, $relation);
            if ($stmt->execute()) {
                $message = 'Data peserta baru berhasil ditambahkan!';
                $message_type = 'success';
            } else { $message = 'Gagal menambah data: ' . $stmt->error; $message_type = 'danger'; }
        }
        // Jika ID ada -> Edit
        else {
             $stmt = $conn->prepare("UPDATE tb_input SET id_user=?, nama_lengkap=?, gender=?, age=?, ethnicity=?, jaundice=?, austim=?, contry_of_res=?, relation=? WHERE id_input = ?");
            $stmt->bind_param("ississsssi", $id_user, $nama_lengkap, $gender, $age, $ethnicity, $jaundice, $austim, $contry_of_res, $relation, $id_input);
             if ($stmt->execute()) {
                $message = 'Data peserta berhasil diperbarui!';
                $message_type = 'success';
            } else { $message = 'Gagal memperbarui data: ' . $stmt->error; $message_type = 'danger'; }
        }
        $stmt->close();
    }
}

// LOGIKA READ
$peserta_data = [];
$query_peserta = "SELECT i.*, u.username FROM tb_input i JOIN tb_user u ON i.id_user = u.id ORDER BY i.id_input DESC";
$result_peserta = $conn->query($query_peserta);
if ($result_peserta) { while ($row = $result_peserta->fetch_assoc()) { $peserta_data[] = $row; } }

$users_list = [];
$result_users = $conn->query("SELECT id, username FROM tb_user ORDER BY username ASC");
if($result_users) { while($row = $result_users->fetch_assoc()){ $users_list[] = $row; } }

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Peserta - Admin</title>
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
                <h1 class="main-header">Manajemen Data Peserta</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Data Peserta
                </button>
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
                                    <th>#ID</th>
                                    <th>Nama Peserta</th>
                                    <th>Umur</th>
                                    <th>Gender</th>
                                    <th>Diinput oleh</th>
                                    <th>Waktu Input</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($peserta_data)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">Belum ada data peserta.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($peserta_data as $data): ?>
                                    <tr>
                                        <th><?= $data['id_input'] ?></th>
                                        <td>
                                            <img src="../<?= htmlspecialchars($data['photo'] ?? 'images/default-avatar.png') ?>" alt="Avatar" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                            <?= htmlspecialchars($data['nama_lengkap']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($data['age']) ?></td>
                                        <td><?= htmlspecialchars($data['gender'] == 'Female' ? 'Perempuan' : 'Laki-laki') ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($data['username']) ?></span></td>
                                        <td><?= date('d M Y, H:i', strtotime($data['timestamp'])) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?= json_encode($data) ?>)'>
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deletePeserta(<?= $data['id_input'] ?>, '<?= htmlspecialchars($data['nama_lengkap']) ?>')">
                                                <i class="bi bi-trash-fill"></i> Hapus
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

    <div class="modal fade" id="pesertaModal" tabindex="-1" aria-labelledby="pesertaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="data_peserta.php">
                    <div class="modal-header"><h5 class="modal-title" id="pesertaModalLabel">Form Data Peserta</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id_input" id="id_input">
                        <input type="hidden" name="save_peserta" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_user" class="form-label">Diinput oleh User</label>
                                <select class="form-select" id="id_user" name="id_user" required>
                                    <?php foreach ($users_list as $user): ?><option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap Peserta</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Umur (Tahun)</label>
                                <input type="number" class="form-control" id="age" name="age" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <div>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gender" id="modal_male" value="Male" required><label class="form-check-label" for="modal_male">Laki-laki</label></div>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="gender" id="modal_female" value="Female"><label class="form-check-label" for="modal_female">Perempuan</label></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="ethnicity" class="form-label">Etnis/Suku</label><select class="form-select" id="ethnicity" name="ethnicity" required><option value="Asian" selected>Asian</option><option value="Black">Black</option><option value="Hispanic">Hispanic</option><option value="Latino">Latino</option><option value="Middle Eastern">Middle Eastern</option><option value="Others">Others</option><option value="Pasifika">Pasifika</option><option value="South Asian">South Asian</option><option value="Turkish">Turkish</option><option value="Unknown">Unknown</option><option value="White-European">White-European</option><option value="others">others</option></select></div>
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
                            <div class="col-md-6 mb-3"><label class="form-label">Sakit Kuning Saat Lahir?</label><div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="jaundice" id="modal_jaundice_yes" value="yes" required><label class="form-check-label" for="modal_jaundice_yes">Ya</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="jaundice" id="modal_jaundice_no" value="no"><label class="form-check-label" for="modal_jaundice_no">Tidak</label></div></div></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Riwayat Autisme Keluarga?</label><div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="austim" id="modal_family_yes" value="yes" required><label class="form-check-label" for="modal_family_yes">Ya</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="austim" id="modal_family_no" value="no"><label class="form-check-label" for="modal_family_no">Tidak</label></div></div></div>
                        </div>
                        <div class="mb-3"><label for="relation" class="form-label">Hubungan Penginput</label><select class="form-select" id="relation" name="relation" required><option value="">-- Pilih --</option><option value="?">Tidak Diketahui</option><option value="Health care professional">Tenaga Kesehatan</option><option value="Others">Lainnya</option><option value="Parent">Orang Tua</option><option value="Relative">Kerabat</option><option value="Self">Diri Sendiri</option></select></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Data</button></div>
                </form>
            </div>
        </div>
    </div>
    
    <form method="POST" id="deleteForm" style="display: none;"><input type="hidden" name="id_input" id="deletePesertaId"><input type="hidden" name="delete_peserta" value="1"></form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const pesertaModal = new bootstrap.Modal(document.getElementById('pesertaModal'));

        function openAddModal() {
            document.getElementById('pesertaModalLabel').innerText = 'Tambah Data Peserta Baru';
            document.querySelector('#pesertaModal form').reset();
            document.getElementById('id_input').value = '';
            pesertaModal.show();
        }

        function openEditModal(data) {
            document.getElementById('pesertaModalLabel').innerText = 'Edit Data Peserta';
            document.querySelector('#pesertaModal form').reset();
            
            document.getElementById('id_input').value = data.id_input;
            document.getElementById('id_user').value = data.id_user;
            document.getElementById('nama_lengkap').value = data.nama_lengkap;
            document.getElementById('age').value = data.age;
            document.getElementById('ethnicity').value = data.ethnicity;
            document.getElementById('contry_of_res').value = data.contry_of_res;
            document.getElementById('relation').value = data.relation;

            if (data.gender === 'Male') { document.getElementById('modal_male').checked = true; } 
            else { document.getElementById('modal_female').checked = true; }

            if (data.jaundice === 'yes') { document.getElementById('modal_jaundice_yes').checked = true; } 
            else { document.getElementById('modal_jaundice_no').checked = true; }

            if (data.austim === 'yes') { document.getElementById('modal_family_yes').checked = true; } 
            else { document.getElementById('modal_family_no').checked = true; }
            
            pesertaModal.show();
        }

        function deletePeserta(id, name) {
            Swal.fire({
                title: 'Anda yakin?',
                text: `Anda akan menghapus data "${name}" beserta semua hasil tesnya. Tindakan ini tidak dapat dibatalkan!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deletePesertaId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            })
        }
    </script>
</body>
</html>