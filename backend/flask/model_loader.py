from flask import Flask, request, jsonify
import joblib
import pandas as pd
import numpy as np
import mysql.connector
import shap
import traceback

app = Flask(__name__)
db_config = { 'host': 'localhost', 'user': 'root', 'password': '', 'database': 'db_aed' }
model_bundle = { "model": None, "encoder_bundle": None, "explainer": None }

def load_artifacts():
    global model_bundle
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT filename_model, filename_encoder FROM tb_model WHERE aktif = 1 LIMIT 1")
        active_files = cursor.fetchone()
        
        if active_files:
            model_path = f"uploads/{active_files['filename_model']}"
            encoder_bundle_path = f"uploads/{active_files['filename_encoder']}"
            explainer_path = "shap_explainer.pkl"

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
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

load_artifacts()

@app.route('/reload-model', methods=['POST'])
def reload_model():
    if load_artifacts():
        return jsonify({"status": "success", "message": "Artefak berhasil dimuat ulang."}), 200
    else:
        return jsonify({"status": "error", "message": "Gagal memuat ulang artefak."}), 500

@app.route('/predict', methods=['POST'])
def predict():
    if not all(model_bundle.values()):
        return jsonify({'error': 'Artefak tidak tersedia'}), 500

    try:
        data = request.get_json()
        df = pd.DataFrame([data])
        
        encoder_bundle = model_bundle["encoder_bundle"]
        encoder = encoder_bundle['encoder']
        cat_features = encoder_bundle['categorical_features']
        num_features = encoder_bundle['numerical_features']
        
        X_cat = df[cat_features]
        X_num = df[num_features]
        
        X_cat_encoded = encoder.transform(X_cat)
        X_all = np.hstack([X_num.to_numpy(), X_cat_encoded])
        
        prediction = model_bundle["model"].predict(X_all)
        hasil = 'ASD' if prediction[0] == 1 else 'Non-ASD'
        
        explainer = model_bundle["explainer"]
        encoded_feature_names = encoder.get_feature_names_out(cat_features)
        full_feature_names = num_features + list(encoded_feature_names)
        
        # [PERBAIKAN FINAL] Cara mengambil nilai SHAP
        shap_values = explainer(X_all)
        
        # Ambil nilai SHAP untuk kelas positif ('ASD', biasanya index 1)
        shap_values_for_asd_class = shap_values.values[:,:,1]
        
        # Karena kita hanya prediksi 1 data, ambil baris pertama
        shap_values_for_prediction = shap_values_for_asd_class[0]

        contributions = dict(zip(full_feature_names, shap_values_for_prediction))
        
        return jsonify({
            'prediksi': hasil,
            'kontribusi_fitur': contributions
        })

    except Exception as e:
        print("\n❌ TERJADI ERROR PADA PROSES PREDIKSI ❌")
        traceback.print_exc()
        return jsonify({'error': f'Terjadi kesalahan saat prediksi: {str(e)}'}), 400

if __name__ == '__main__':
    app.run(port=5000, debug=True)