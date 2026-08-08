# Dokumentasi API SIMPEG

API ini disediakan oleh sistem Kepegawaian (SIMPEG) untuk melayani verifikasi login tersentralisasi dan pengambilan daftar data pegawai. Digunakan oleh sistem-sistem terpisah seperti *Wisuda* atau *Siakad* agar pegawai dapat login menggunakan satu kredensial yang sama (SSO tersentralisasi tingkat API).

**SECURITY WARNING:**
Endpoint ini mensyaratkan autentikasi Header berupa `X-API-KEY`. Kredensial API Key ini sangat rahasia.

---

## 1. Verifikasi Login (Verify Credentials)

Mengecek kredensial (username/NIDN/NIK/email dan password) milik pegawai di database SIMPEG (`wsia_profil`). Jika valid, akan mengembalikan detail data pengguna.

*   **URL:** `https://simpeg.poltekindonusa.ac.id/api/verify-login`
*   **Method:** `POST`
*   **Headers:**
    *   `X-API-KEY: [YOUR_API_KEY]`
    *   `Accept: application/json`
*   **Body (JSON/Form-Data):**
    *   `username` (string, required): NIDN, id_sdm, email, username, nik, atau nip pegawai.
    *   `password` (string, required): Kata sandi pegawai.

### Response Success (Credentials Valid)
```json
{
    "status": "success",
    "message": "Authentication successful",
    "data": {
        "id_sdm": "SDM001",
        "nidn": "0601018501",
        "nip": "1985010101",
        "name": "Andi Susanto",
        "username": "andi.susanto",
        "role": 5,
        "user_type": "pegawai"
    }
}
```

### Response Error (Credentials Invalid / User Not Found)
```json
{
    "status": "error",
    "message": "Invalid username or password" 
}
```

---

## 2. Get Data Pegawai (Employees List)

Mengambil daftar data pegawai secara dinamis. Dapat digunakan untuk auto-complete/search pegawai saat me-ngassign tugas kepanitiaan.

*   **URL:** `https://simpeg.poltekindonusa.ac.id/api/employees`
*   **Method:** `GET`
*   **Headers:**
    *   `X-API-KEY: [YOUR_API_KEY]`
    *   `Accept: application/json`
*   **URL Parameters:**
    *   `q` atau `search` (string, optional): Kata kunci pencarian nama, username, NIDN, nip.

### Response Success
```json
{
    "status": "success",
    "data": [
        {
            "id_sdm": "SDM001",
            "nidn": "0601018501",
            "nip": "1985010101",
            "username": "andi.susanto",
            "nama": "Andi Susanto",
            "status": "Tendik",
            "email": "andi@poltekindonusa.ac.id"
        },
        {
            "id_sdm": "SDM002",
            "nidn": "0602028802",
            "nip": "1988020202",
            "username": "rudi.kurniawan",
            "nama": "Rudi Kurniawan",
            "status": "Dosen",
            "email": "rudi@poltekindonusa.ac.id"
        }
    ]
}
```
