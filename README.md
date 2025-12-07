# Autism Early Detector (AED)

Autism Early Detector (AED) adalah aplikasi web untuk melakukan skrining dini indikasi autisme pada peserta didik menggunakan kuesioner **AQ-10** yang dipadukan dengan model **machine learning (Random Forest)**. Aplikasi ini membantu guru/orang tua mengisi kuesioner dan mendapatkan hasil prediksi secara otomatis.

> ⚠️ **Catatan Penting:** Aplikasi ini hanya digunakan sebagai **alat skrining awal**, **bukan alat diagnosis resmi**. Diagnosis tetap harus dilakukan oleh tenaga profesional (psikolog/psikiater/dokter spesialis).

---

## ✨ Fitur Utama

### 👤 Fitur User (Guru / Orang Tua)

- Registrasi dan login user.
- Pengisian data identitas peserta (nama, usia, jenis kelamin, dll.).
- Pengisian kuesioner **AQ-10** (10 pertanyaan Ya/Tidak).
- Perhitungan skor otomatis dan pengiriman data ke model ML.
- Tampilan hasil prediksi (`ASD` / `Non-ASD`) beserta skor.
- Riwayat tes yang pernah dilakukan.
- Cetak hasil prediksi.
- Pengaturan profil dan ganti password.

### 🛠️ Fitur Admin

- Dashboard admin (ringkasan jumlah user, data tes, dll.).
- Manajemen user (tambah/ubah/hapus akun).
- Manajemen data tes dan hasil prediksi.
- Manajemen pertanyaan kuesioner AQ-10.
- Manajemen model machine learning (upload model `.pkl` baru dan memilih model aktif).
- Laporan dan rekap hasil skrining.

### 🤖 Machine Learning

- Algoritma **Random Forest Classifier**.
- Training dilakukan di Python (scikit-learn).
- Model diexport ke file `.pkl` dan di-deploy lewat **Flask API** di folder `backend/`.
- Aplikasi PHP memanggil API ini untuk mendapatkan hasil prediksi.
- Dapat dikembangkan lebih lanjut dengan:
  - Cross-validation
  - Penanganan data tidak seimbang
  - Explainable AI (misal SHAP) jika diintegrasikan.

---

## 🛠️ Teknologi yang Digunakan

- **Frontend & Backend Web:**
  - PHP
  - HTML, CSS, JavaScript
  - Bootstrap (untuk tampilan)
- **Machine Learning Backend:**
  - Python 3
  - Flask (REST API)
  - scikit-learn
  - Pandas, NumPy
- **Database:**
  - MySQL / MariaDB
- **Tools & Lainnya:**
  - XAMPP / Laragon (Apache + MySQL)
  - Git & GitHub

---

## 📁 Struktur Folder (gambaran umum)

> Nama folder bisa sedikit berbeda di project kamu, sesuaikan jika perlu.

```text
/ (root)
├─ admin/           # Halaman dan logika untuk admin
├─ backend/         # API Flask (Python) dan model ML
├─ css/             # File CSS
├─ js/              # File JavaScript
├─ db/              # File .sql (database AED)
├─ images/          # Asset gambar
├─ includes/        # File helper / konfigurasi PHP
├─ uploads/         # (opsional) file upload, foto, dll.
├─ index.php        # Halaman utama / login
├─ README.md
└─ .gitignore

📦 Persyaratan Sistem

PHP 7.x atau lebih baru

MySQL / MariaDB

Python 3.x

Web server Apache (via XAMPP / Laragon / WAMP)

Pip (Python package manager)

🚀 Cara Instalasi (Lokal)
1. Clone Repository
git clone https://github.com/DepenZ17/Autism-Early-Detector.git
cd Autism-Early-Detector


Letakkan folder ini di htdocs (XAMPP) atau www (Laragon).

2. Setup Database

Buka phpMyAdmin.

Buat database baru, misal: db_aed.

Import file SQL yang ada di folder:

db/db_aed.sql


(ganti dengan nama file .sql yang sesuai di project kamu).

3. Konfigurasi Koneksi Database (PHP)

Cari file konfigurasi, misalnya:

includes/config.php


Sesuaikan:

$DB_HOST = 'localhost';
$DB_NAME = 'db_aed';
$DB_USER = 'root';
$DB_PASS = '';


Jika kamu menggunakan environment variable atau file .env, sesuaikan dengan kebutuhan.

4. Setup Backend Machine Learning (Flask)

Masuk ke folder backend (nama bisa berbeda, sesuaikan):

cd backend
python -m venv venv
# Windows:
venv\Scripts\activate
# Linux/Mac:
# source venv/bin/activate

pip install -r requirements.txt
python app.py


Pastikan API Flask berjalan, misalnya di http://127.0.0.1:5000.

Jika perlu, sesuaikan URL API di file konfigurasi PHP, misalnya:

$ML_API_BASE_URL = 'http://127.0.0.1:5000';

5. Jalankan Aplikasi Web

Aktifkan Apache dan MySQL (XAMPP / Laragon).

Buka browser dan akses:

http://localhost/Autism-Early-Detector


(sesuaikan dengan nama folder project).

▶️ Cara Penggunaan Singkat

Admin:

Login sebagai admin.

Atur data user, pertanyaan AQ-10, dan cek data hasil skrining.

User (Guru/Orang Tua):

Registrasi / login.

Isi data peserta dan kuesioner AQ-10.

Kirim form → sistem akan menghubungi API ML → tampil hasil prediksi.

Simpan / cetak hasil jika diperlukan.
