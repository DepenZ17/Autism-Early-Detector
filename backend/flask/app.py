from flask import Flask, request, jsonify  # Flask digunakan untuk membuat API
from flask_cors import CORS  # ✅ Untuk mengizinkan permintaan dari domain lain (CORS)
import joblib  # Untuk memuat model dan encoder dari file .pkl
import pandas as pd
import numpy as np
import mysql.connector  # Untuk koneksi ke database MySQL
import shap  # Untuk interpretasi model menggunakan SHAP
import traceback  # Untuk menampilkan detail error saat debugging

app = Flask(__name__)
CORS(app)  # ✅ Mengizinkan akses API dari semua domain (boleh dibatasi nanti agar lebih aman)

# Konfigurasi koneksi ke database
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'db_aed'
}

# Struktur untuk menyimpan artefak model, encoder, dan SHAP explainer
model_bundle = {
    "model": None,
    "encoder_bundle": None,
    "explainer": None
}

# Fungsi untuk memuat model, encoder, dan explainer dari file
def load_artifacts():
    global model_bundle
    try:
        # Koneksi ke database untuk mendapatkan model yang aktif
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT filename_model, filename_encoder FROM tb_model WHERE aktif = 1 LIMIT 1")
        active_files = cursor.fetchone()

        if active_files:
            # Path file model dan encoder
            model_path = f"uploads/{active_files['filename_model']}"
            encoder_bundle_path = f"uploads/{active_files['filename_encoder']}"
            explainer_path = "shap_explainer.pkl"

            # Memuat artefak dari file .pkl
            model_bundle["model"] = joblib.load(model_path)
            model_bundle["encoder_bundle"] = joblib.load(encoder_bundle_path)
            model_bundle["explainer"] = joblib.load(explainer_path)
            print("✅ Artefak (Model, Encoder, Explainer) berhasil dimuat.")
            return True
        else:
            print("❌ Peringatan: Tidak ada model aktif.")
            return False
    except Exception as e:
        print(f"❌ Error saat memuat artefak: {e}")
        return False
    finally:
        # Menutup koneksi database jika sudah selesai
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

# Muat artefak saat server pertama kali dijalankan
load_artifacts()

# Endpoint untuk me-reload model jika admin mengganti model dari panel web
@app.route('/reload-model', methods=['POST'])
def reload_model():
    if load_artifacts():
        return jsonify({"status": "success", "message": "Artefak berhasil dimuat ulang."}), 200
    else:
        return jsonify({"status": "error", "message": "Gagal memuat ulang artefak."}), 500

# Endpoint untuk memproses prediksi autisme
@app.route('/predict', methods=['POST'])
def predict():
    if not all(model_bundle.values()):
        return jsonify({'error': 'Artefak tidak tersedia'}), 500

    try:
        data = request.get_json()  # Ambil data JSON dari frontend
        df = pd.DataFrame([data])  # Konversi ke DataFrame

        # Ambil encoder dan fitur
        encoder_bundle = model_bundle["encoder_bundle"]
        encoder = encoder_bundle['encoder']
        cat_features = encoder_bundle['categorical_features']
        num_features = encoder_bundle['numerical_features']

        # Pisahkan data kategorikal dan numerik
        X_cat = df[cat_features]
        X_num = df[num_features]

        # Encode data kategorikal dan gabungkan dengan numerik
        X_cat_encoded = encoder.transform(X_cat)
        X_all = np.hstack([X_num.to_numpy(), X_cat_encoded])

        # Lakukan prediksi menggunakan model Random Forest
        prediction = model_bundle["model"].predict(X_all)
        hasil = 'ASD' if prediction[0] == 1 else 'Non-ASD'

        # Interpretasi menggunakan SHAP
        explainer = model_bundle["explainer"]
        encoded_feature_names = encoder.get_feature_names_out(cat_features)
        full_feature_names = num_features + list(encoded_feature_names)
        shap_values = explainer(X_all)
        shap_values_for_asd_class = shap_values.values[:, :, 1]  # Ambil SHAP untuk kelas ASD
        shap_values_for_prediction = shap_values_for_asd_class[0]

        # Gabungkan nama fitur dan kontribusinya
        contributions = dict(zip(full_feature_names, shap_values_for_prediction))

        # Kembalikan hasil prediksi dan kontribusi fitur ke frontend
        return jsonify({
            'prediksi': hasil,
            'kontribusi_fitur': contributions
        })

    except Exception as e:
        # Tangani error jika terjadi saat prediksi
        print("\n❌ TERJADI ERROR PADA PROSES PREDIKSI ❌")
        traceback.print_exc()
        return jsonify({'error': f'Terjadi kesalahan saat prediksi: {str(e)}'}), 400

# Jalankan server Flask di port 5000
if __name__ == '__main__':
    app.run(port=5000, debug=True)
