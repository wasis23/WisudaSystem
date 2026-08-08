# Dokumentasi Sinkronisasi Mahasiswa (SIMANTA)

SIMANTA memiliki skrip utilitas khusus (`force_sync.php`) untuk melakukan sinkronisasi paksa data mahasiswa dari database SIAKAD ke database lokal SIMANTA secara berkala atau ketika dipicu (triggered).

---

## 1. Sinkronisasi Data Mahasiswa via Skrip (Internal)

Skrip ini diakses secara internal atau dieksekusi secara otomatis oleh *scheduler* / *cron-job* untuk menarik data mahasiswa terbaru dari database SIAKAD (contoh tabel `viewMahasiswaPt`) ke database `simanta`.

*   **File:** `/force_sync.php` (Berada di root direktori SIMANTA)
*   **Method:** Akses CLI (`php force_sync.php`) atau `GET` (di web, hanya boleh akses internal)
*   **Mekanisme Kerja:**
    1. Sistem membaca koneksi lokal SIMANTA dan koneksi remote ke database SIAKAD menggunakan konfigurasi pada file `.env`.
    2. Sistem mengeksekusi kueri langsung `SELECT * FROM viewMahasiswaPt WHERE nipd = ...` pada database SIAKAD.
    3. Jika data ditemukan, ia akan mengecek apakah data mahasiswa tersebut sudah ada di tabel `mahasiswa` lokal SIMANTA (`SELECT nim FROM mahasiswa WHERE nim = :nim`).
    4. Jika belum ada, SIMANTA akan meng-insert data tersebut (termasuk *hash* password dari SIAKAD, identitas, kelas, dsb.) ke dalam tabel `mahasiswa`.
    5. Menghasilkan pesan sukses atau data sudah ada.

### Contoh Output CLI
```bash
Data ditemukan di SIAKAD. Sinkronisasi ke lokal...
Berhasil memasukkan data D23098 ke lokal.
```
Atau jika data sudah tersinkronisasi:
```bash
Data ditemukan di SIAKAD. Sinkronisasi ke lokal...
Data D23098 sudah ada di lokal.
```

### Catatan Keamanan & Operasional
1. Mengingat skrip ini membaca kredensial database SIAKAD dari file `.env`, pastikan kredensial `SIAKAD_DB_HOST`, `SIAKAD_DB_USER`, `SIAKAD_DB_PASS`, dan `SIAKAD_DB_NAME` dijaga kerahasiaannya.
2. Sangat disarankan agar `force_sync.php` hanya dapat dieksekusi dari dalam server atau *command-line*, dan tidak dibuka bebas ke internet untuk mencegah *Denial of Service* pada database (dengan memblokir akses ke skrip ini via `.htaccess` jika diakses langsung).
