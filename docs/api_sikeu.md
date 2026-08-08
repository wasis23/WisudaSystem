# Dokumentasi API Wisuda (SIKEU)

API ini digunakan untuk memverifikasi status pembayaran wisuda dan kuota tambahan bagi mahasiswa dari Sistem Keuangan (SIKEU). 

---

## 1. Cek Status Pembayaran Wisuda

Endpoint ini mengembalikan informasi status pembayaran wisuda mahasiswa, jumlah kuota tamu, dan kuota tambahan yang telah dibayarkan (jika ada).

*   **URL:** `https://sikeu.poltekindonusa.ac.id/api/wisuda/status/:no_pend`
*   **Method:** `GET`
*   **Headers:**
    *   (Optional) Autentikasi SIKEU Token / Basic Auth jika di-setting di backend.

### Response Success
```json
{
    "status": true,
    "data": {
        "has_paid_wisuda": true,
        "tambahan_wisuda_paid_quota": 2,
        "total_allowed_guests": 4,
        "snack_quota": 5
    }
}
```

**Keterangan Field Response:**
*   `has_paid_wisuda`: `true` jika mahasiswa memiliki riwayat pembayaran apapun di sistem keuangan untuk wisuda/tambahan.
*   `tambahan_wisuda_paid_quota`: Jumlah kursi tambahan yang dibayarkan. Dihitung berdasarkan nilai pembayaran dibagi harga per kursi (misal: Rp. 150.000).
*   `total_allowed_guests`: Total tamu undangan yang diizinkan masuk. (Secara default `2` + `tambahan_wisuda_paid_quota`).
*   `snack_quota`: Kuota konsumsi total, dihitung berdasarkan `total_allowed_guests` + 1 porsi untuk wisudawan itu sendiri.

### Response Error (Database/Server)
```json
{
    "status": false,
    "message": "Error details",
    "data": null
}
```
