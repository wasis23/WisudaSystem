# Dokumentasi API Mahasiswa (SIAKAD)

API ini digunakan untuk mengambil data mahasiswa secara eksternal. Endpoint ini mengembalikan detail mahasiswa dari sistem akademik.

**SECURITY WARNING:**
Endpoint ini bersifat sangat rahasia karena mengekspos hash kata sandi mahasiswa dan data pribadi lainnya. Jangan pernah mengekspos endpoint ini ke publik secara terbuka. Hanya gunakan di jaringan internal atau gunakan mekanisme otentikasi API Key.

---

## 1. Get Data Mahasiswa

Mengambil data mahasiswa. Dapat mengambil list (dengan paginasi) atau satu mahasiswa berdasarkan `nim`.

*   **URL:** `https://siakad.poltekindonusa.ac.id/api/mahasiswa_external.php`
*   **Method:** `GET`
*   **Headers:**
    *   `X-Api-Key: INDONUSA_SECRET_API_KEY_2026_X7Z`
*   **URL Parameters:**
    *   `nim` (optional): NIM/NIPD Mahasiswa spesifik.
    *   `limit` (optional): Limit data (default: 100). Digunakan jika tidak mencari spesifik `nim`.
    *   `offset` (optional): Offset pencarian (default: 0).

### Response Success (Multiple - Tanpa NIM)
```json
{
    "status": true,
    "count": 100,
    "pagination": {
        "limit": 100,
        "offset": 0
    },
    "data": [
        {
            "nim": "D23098",
            "nama": "Jane Doe",
            "email": "jane@example.com",
            "email_institusi": "jane@poltekindonusa.ac.id",
            "password_hash": "$2y$10$...",
            "prodi": "D3 Sistem Informasi",
            "no_hp": "081234567890"
        }
    ]
}
```

### Response Success (Single - Dengan NIM)
```json
{
    "status": true,
    "data": {
        "nim": "D23098",
        "nama": "Jane Doe",
        "email": "jane@example.com",
        "email_institusi": "jane@poltekindonusa.ac.id",
        "password_hash": "$2y$10$...",
        "prodi": "D3 Sistem Informasi",
        "no_hp": "081234567890"
    }
}
```

### Response Error (Invalid API Key)
```json
{
    "status": false,
    "message": "Unauthorized: Invalid or missing API Key"
}
```

### Response Error (Not Found - Dengan NIM)
```json
{
    "status": false,
    "message": "Mahasiswa not found"
}
```
