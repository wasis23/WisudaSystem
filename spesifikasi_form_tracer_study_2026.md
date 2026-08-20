# Spesifikasi Form Website: Pendataan Career & Tracer Study Alumni 2026

Dokumen ini berisi spesifikasi teknis form input website yang disesuaikan secara presisi dengan formulir Google Forms "Pendataan Career & Tracer Study Alumni 2026 - Politeknik Indonusa Surakarta". Gunakan panduan schema dan komponen berikut untuk mengimplementasikan form pada website.

---

## Ringkasan Ketentuan
- **Tanda Wajib (`*`)**: Komponen wajib memiliki validasi required (`required: true`).
- **Tipe Input yang Digunakan**:
  - `text` / `email` / `tel` / `textarea`
  - `radio` (Tandai satu pilihan)
  - `checkbox` (Centang semua yang sesuai / multi-select)
  - `matrix_checkbox` (Kisi kotak centang / grid)

---

## 1. Data Diri & Akademik

### Pertanyaan 1: NIM
- **Label**: NIM *
- **Komponen**: `Input Text` / `Input Number`
- **Tipe HTML**: `<input type="text" name="nim" required />`
- **Validasi**: Wajib diisi (`required`)

### Pertanyaan 2: Nama Lengkap
- **Label**: Nama Lengkap *
- **Komponen**: `Input Text`
- **Tipe HTML**: `<input type="text" name="nama_lengkap" required />`
- **Validasi**: Wajib diisi (`required`)

### Pertanyaan 3: Alamat Email
- **Label**: Alamat Email *
- **Komponen**: `Input Email`
- **Tipe HTML**: `<input type="email" name="email" required />`
- **Validasi**: Wajib diisi (`required`), format email valid

### Pertanyaan 4: No. WhatsApp
- **Label**: No. WhatsApp *
- **Komponen**: `Input Tel` / `Input Text`
- **Tipe HTML**: `<input type="tel" name="no_whatsapp" placeholder="08..." required />`
- **Validasi**: Wajib diisi (`required`)

### Pertanyaan 5: Program Studi
- **Label**: Program Studi *
- **Komponen**: `Checkbox Group` (dengan opsi isian kustom)
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Wajib diisi minimal satu pilihan (`required`)
- **Pilihan**:
  - [ ] Teknologi Rekayasa Otomotif
  - [ ] Teknologi Rekayasa Perangkat Lunak
  - [ ] Produksi Media
  - [ ] Perhotelan
  - [ ] Farmasi
  - [ ] Manajemen Informasi Kesehatan
  - [ ] Teknologi Laboratorium Medis
  - [ ] Yang lain: `<input type="text" name="prodi_lainnya" />`

### Pertanyaan 6: Jenis Kelas
- **Label**: Jenis Kelas *
- **Komponen**: `Radio Button Group`
- **Instruksi Form**: Tandai satu oval saja.
- **Validasi**: Wajib diisi (`required`)
- **Pilihan**:
  - ( ) Reguler
  - ( ) Transfer
  - ( ) Karyawan
  - ( ) RPL

### Pertanyaan 7: Alamat Lengkap
- **Label**: Alamat Lengkap *
- **Komponen**: `Textarea`
- **Tipe HTML**: `<textarea name="alamat_lengkap" rows="3" required></textarea>`
- **Validasi**: Wajib diisi (`required`)

---

## 2. Status Pekerjaan & Karir

### Pertanyaan 8: Status Saat Ini
- **Label**: Jelaskan status anda saat ini? *
- **Komponen**: `Checkbox Group` (dengan opsi isian kustom)
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Wajib diisi minimal satu pilihan (`required`)
- **Pilihan**:
  - [ ] Bekerja (full time/part time)
  - [ ] Berwirausaha
  - [ ] Melanjutkan Pendidikan
  - [ ] Tidak bekerja tetapi sedang mencari kerja
  - [ ] Belum memungkinkan bekerja
  - [ ] Yang lain: `<input type="text" name="status_lainnya" />`

### Pertanyaan 9: Tempat Bekerja
- **Label**: Jika Anda bekerja, di mana tempat Anda bekerja?
- **Komponen**: `Input Text`
- **Tipe HTML**: `<input type="text" name="tempat_bekerja" />`
- **Validasi**: Opsional

### Pertanyaan 10: Penghasilan / Gaji per Bulan
- **Label**: Jika Anda bekerja, berapa gaji Anda perbulan?
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] < Rp2.000.000,00
  - [ ] > Rp2.000.000,00 - Rp3.000.000,00
  - [ ] Rp3.000.000,00 - Rp5.000.000,00
  - [ ] Rp5.000.000,00 - Rp10.000.000,00
  - [ ] > Rp10.000.000,00

### Pertanyaan 11: Keselarasan Bidang Studi dengan Pekerjaan
- **Label**: Seberapa erat hubungan antara bidang studi dengan pekerjaan Anda?
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Sangat Erat
  - [ ] Erat
  - [ ] Cukup Erat
  - [ ] Kurang Erat
  - [ ] Tidak Sama Sekali

### Pertanyaan 12: Kesesuaian Tingkat Pendidikan
- **Label**: Tingkat pendidikan apa yang paling tepat/sesuai untuk pekerjaan Anda saat ini?
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Setingkat Lebih Tinggi
  - [ ] Tingkat yang Sama
  - [ ] Setingkat Lebih Rendah
  - [ ] Tidak Perlu Pendidikan Tinggi

### Pertanyaan 13: Waktu Tunggu Lulusan
- **Label**: Waktu Tunggu Lulusan
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] WT ≤ 3 bulan
  - [ ] 3 bulan < WT ≤ 6 bulan
  - [ ] WT > 6 bulan

### Pertanyaan 14: Lokasi / Alamat Tempat Bekerja
- **Label**: Dimana lokasi/alamat tempat Anda bekerja?
- **Komponen**: `Input Text` / `Textarea`
- **Tipe HTML**: `<textarea name="alamat_tempat_kerja" rows="2"></textarea>`
- **Validasi**: Opsional

### Pertanyaan 15: Jenis Instansi / Perusahaan
- **Label**: Apa jenis perusahaan/instansi/institusi tempat anda bekerja sekarang?
- **Komponen**: `Checkbox Group` (dengan opsi isian kustom)
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Instansi pemerintah
  - [ ] BUMN/BUMD
  - [ ] Institusi/Organisasi Multilateral
  - [ ] Organisasi non-profit/Lembaga Swadaya Masyarakat
  - [ ] Perusahaan swasta
  - [ ] Wiraswasta/perusahaan sendiri
  - [ ] Yang lain: `<input type="text" name="jenis_instansi_lainnya" />`

### Pertanyaan 16: Nama Perusahaan / Kantor
- **Label**: Apa nama perusahaan/kantor tempat Anda bekerja?
- **Komponen**: `Input Text`
- **Tipe HTML**: `<input type="text" name="nama_perusahaan" />`
- **Validasi**: Opsional

### Pertanyaan 17: Posisi / Jabatan
- **Label**: Apa posisi anda di perusahaan/kantor tempat anda bekerja?
- **Komponen**: `Checkbox Group` (dengan opsi isian kustom)
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Fouder
  - [ ] Co-fouder
  - [ ] Staff
  - [ ] Freelance/kerja lepas
  - [ ] Yang lain: `<input type="text" name="posisi_lainnya" />`

### Pertanyaan 18: Tingkat / Cakupan Tempat Kerja
- **Label**: Apa tingkat tempat kerja Anda?
- **Komponen**: `Checkbox Group` (dengan opsi isian kustom)
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Lokal/wilayah/wiraswasta tidak berbadan hukum
  - [ ] Nasional/wirausaha berbadan hukum
  - [ ] Multinasional/Internasional
  - [ ] Yang lain: `<input type="text" name="tingkat_tempat_kerja_lainnya" />`

---

## 3. Kewirausahaan & Studi Lanjut

### Pertanyaan 19: Nama Usaha
- **Label**: Jika Anda berwirausaha, apa nama usaha Anda?
- **Komponen**: `Input Text`
- **Tipe HTML**: `<input type="text" name="nama_usaha" />`
- **Validasi**: Opsional

### Pertanyaan 20: Penghasilan Usaha per Bulan
- **Label**: Jika Anda berwirausaha, berapa penghasilan usaha Anda perbulan?
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] < Rp2.000.000,00
  - [ ] Rp2.000.000,00 - Rp5.000.000,00
  - [ ] Rp5.000.000,00 - Rp10.000.000
  - [ ] > Rp10.000.000,00

### Pertanyaan 21: Keselarasan Usaha dengan Bidang Studi
- **Label**: Seberapa erat hubungan antara bidang studi dengan usaha Anda?
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Sangat Erat
  - [ ] Erat
  - [ ] Cukup Erat
  - [ ] Kurang Erat
  - [ ] Tidak Sama Sekali

### Pertanyaan 22: Status Studi Lanjut
- **Label**: Apakah Anda studi lanjut?
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Ya
  - [ ] Tidak

### Pertanyaan 23: Nama Kampus Studi Lanjut
- **Label**: Jika Anda studi lanjut, di mana tempat kampus Anda?
- **Komponen**: `Input Text`
- **Tipe HTML**: `<input type="text" name="kampus_studi_lanjut" />`
- **Validasi**: Opsional

### Pertanyaan 24: Alamat Kampus Studi Lanjut
- **Label**: Jika Anda studi lanjut, di mana alamat kampus Anda?
- **Komponen**: `Input Text` / `Textarea`
- **Tipe HTML**: `<textarea name="alamat_kampus_studi_lanjut" rows="2"></textarea>`
- **Validasi**: Opsional

### Pertanyaan 25: Sumber Pembiayaan Kuliah
- **Label**: Sebutkan sumber dana dalam pembiayaan kuliah? (Selama kuliah di Politeknik Indonusa)
- **Komponen**: `Checkbox Group` (dengan opsi isian kustom)
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Opsional
- **Pilihan**:
  - [ ] Biaya Sendiri / Keluarga
  - [ ] Beasiswa ADIK/AFIRMASI
  - [ ] Beasiswa KIP KULIAH
  - [ ] Beasiswa UKT/BPP
  - [ ] Beasiswa Prestasi
  - [ ] Beasiswa Hafidz Al-Quran
  - [ ] Beasiswa Perusahaan/Swasta
  - [ ] Beasiswa Yatim
  - [ ] Yang lain: `<input type="text" name="sumber_dana_lainnya" />`

---

## 4. Evaluasi Kompetensi & Pembelajaran (Matriks / Grid)

### Pertanyaan 26: Tingkat Penguasaan Kompetensi Saat Lulus
- **Label**: Pada saat lulus, pada tingkat mana kompetensi di bawah ini Anda kuasai? *
- **Komponen**: `Matrix Checkbox Grid` (Kisi Kotak Centang)
- **Instruksi Form**: Centang semua yang sesuai. (Skala kolom: 1 sampai 5)
- **Validasi**: Wajib diisi untuk setiap baris (`required`)
- **Tabel Matriks**:

| Aspek Kompetensi | 1 | 2 | 3 | 4 | 5 |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Etika | [ ] | [ ] | [ ] | [ ] | [ ] |
| Keahlian berdasarkan bidang ilmu | [ ] | [ ] | [ ] | [ ] | [ ] |
| Bahasa Inggris | [ ] | [ ] | [ ] | [ ] | [ ] |
| Penggunaan Teknologi Informasi | [ ] | [ ] | [ ] | [ ] | [ ] |
| Komunikasi | [ ] | [ ] | [ ] | [ ] | [ ] |
| Kerja sama tim | [ ] | [ ] | [ ] | [ ] | [ ] |
| Pengembangan Diri | [ ] | [ ] | [ ] | [ ] | [ ] |

### Pertanyaan 27: Tingkat Kebutuhan Kompetensi dalam Pekerjaan Saat Ini
- **Label**: Pada saat ini, pada tingkat mana kompetensi di bawah ini diperlukan dalam pekerjaan? *
- **Komponen**: `Matrix Checkbox Grid` (Kisi Kotak Centang)
- **Instruksi Form**: Centang semua yang sesuai. (Skala kolom: 1 sampai 5)
- **Validasi**: Wajib diisi untuk setiap baris (`required`)
- **Tabel Matriks**:

| Aspek Kompetensi | 1 | 2 | 3 | 4 | 5 |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Etika | [ ] | [ ] | [ ] | [ ] | [ ] |
| Keahlian berdasarkan bidang ilmu | [ ] | [ ] | [ ] | [ ] | [ ] |
| Bahasa Inggris | [ ] | [ ] | [ ] | [ ] | [ ] |
| Penggunaan Teknologi Informasi | [ ] | [ ] | [ ] | [ ] | [ ] |
| Komunikasi | [ ] | [ ] | [ ] | [ ] | [ ] |
| Kerja sama tim | [ ] | [ ] | [ ] | [ ] | [ ] |
| Pengembangan Diri | [ ] | [ ] | [ ] | [ ] | [ ] |

### Pertanyaan 28: Penekanan Metode Pembelajaran
- **Label**: Menurut Anda seberapa besar penekanan pada metode pembelajaran di bawah ini dilaksanakan di program studi Anda? *
- **Komponen**: `Matrix Checkbox Grid` (Kisi Kotak Centang)
- **Instruksi Form**: Centang semua yang sesuai. (Skala kolom: 1 sampai 5)
- **Validasi**: Wajib diisi untuk setiap baris (`required`)
- **Tabel Matriks**:

| Metode Pembelajaran | 1 | 2 | 3 | 4 | 5 |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Perkuliahan | [ ] | [ ] | [ ] | [ ] | [ ] |
| Demonstrasi | [ ] | [ ] | [ ] | [ ] | [ ] |
| Partisipasi dalam proyek riset | [ ] | [ ] | [ ] | [ ] | [ ] |
| Magang | [ ] | [ ] | [ ] | [ ] | [ ] |
| Praktikum | [ ] | [ ] | [ ] | [ ] | [ ] |
| Kerja Lapangan | [ ] | [ ] | [ ] | [ ] | [ ] |
| Diskusi | [ ] | [ ] | [ ] | [ ] | [ ] |

---

## 5. Kepuasan & Masukan

### Pertanyaan 29: Kepuasan Layanan Kampus
- **Label**: Bagaimana kepuasan anda terhadap layanan yang diselenggaran oleh Politeknik Indonusa Surakarta selama Anda menempuh pendidikan? *
- **Komponen**: `Checkbox Group`
- **Instruksi Form**: Centang semua yang sesuai.
- **Validasi**: Wajib diisi (`required`)
- **Pilihan**:
  - [ ] Sangat Puas
  - [ ] Puas
  - [ ] Cukup Puas
  - [ ] Kurang Puas
  - [ ] Tidak Sama Sekali

### Pertanyaan 30: Saran & Masukan
- **Label**: Saran untuk pengembangan pembelajaran maupun sarana dan prasarana yang mendukung proses pembelajaran di Politeknik Indonusa Surakarta! *
- **Komponen**: `Textarea` (Paragraf Panjang)
- **Tipe HTML**: `<textarea name="saran_masukan" rows="5" required></textarea>`
- **Validasi**: Wajib diisi (`required`)