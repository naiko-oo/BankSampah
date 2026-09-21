# ♻️ Sistem Informasi Bank Sampah Digital (RT/RW)
> *Sistem Informasi Pengelolaan dan Pencatatan Transaksi Bank Sampah Berbasis Web untuk Tingkatkan Efisiensi Administrasi di Lingkungan RT/RW.*

## Latar Belakang & Permasalahan
Dalam skema pengelolaan Bank Sampah, proses pencatatan transaksi umumnya masih menggunakan metode konvensional (buku besar). Metode ini memiliki beberapa kendala teknis dan operasional, antara lain:
* **Tingkat Aksesibilitas Data yang Rendah:** Nasabah (warga) tidak memiliki akses *real-time* untuk memantau saldo tabungan dan riwayat setoran sampah mereka.
* **Risiko *Human Error*:** Proses kalkulasi manual antara variabel berat sampah dan tarif per-kategori rawan menghasilkan kekeliruan perhitungan saldo.
* **Inefisiensi Pelaporan:** Pengurus membutuhkan waktu lebih lama untuk melakukan rekapitulasi data transaksi harian menjadi laporan periodik (bulanan).

## Target Pengguna & Manfaat
Sistem ini dirancang untuk dua aktor utama (*multi-role*):
1. **Pengurus / Administrator (Admin):**
   * Mengelola data master (kategori sampah, tarif, dan data nasabah).
   * Melakukan pencatatan transaksi setoran dan penarikan saldo nasabah.
   * Mencetak rekapitulasi laporan transaksi.
2. **Nasabah (Warga):**
   * Mengakses *dashboard* pribadi untuk memantau sisa saldo tabungan.
   * Melihat riwayat transaksi setoran dan penarikan secara transparan.

## Daftar Fitur Inti
Pengembangan fitur diprioritaskan pada fungsionalitas utama (*core functions*):
* **Autentikasi & Otorisasi:** Sistem *login/logout* berbasis peran (Admin dan Nasabah).
* **Manajemen Kategori Sampah (CRUD):** Pengelolaan data jenis sampah (plastik, kertas, logam, dll.) beserta tarif per kilogram.
* **Manajemen Data Nasabah (CRUD):** Registrasi dan pembaruan profil nasabah oleh Administrator.
* **Pencatatan Transaksi Setoran:** Kalkulasi otomatis saldo berdasarkan bobot ($kg$) dan tarif kategori sampah yang dipilih.
* **Pencatatan Penarikan Saldo:** Penyesuaian saldo tabungan nasabah saat terjadi penarikan dana secara tunai.
* **Dashboard & Riwayat Transaksi:** Visualisasi saldo terkini dan daftar log transaksi untuk akun Nasabah.
* **Laporan & Rekapitulasi Data:** Fitur pencetakan rekapitulasi transaksi berbasis rentang waktu untuk kebutuhan evaluasi pengurus.

## Batasan Proyek *(Out of Scope)*
Untuk menjaga agar fokus analisis dan perancangan perangkat lunak tetap realistis dalam jangka waktu yang ditentukan, beberapa fitur berikut dieksklusi dari cakupan pengembangan saat ini:
* Integrasi *Payment Gateway* / API *E-Wallet* (pencairan saldo dilakukan secara tunai oleh pengurus).
* Fitur pelacakan lokasi / *pickup scheduling* berbasis GPS.
* Fitur *E-commerce* / jual-beli produk hasil daur ulang.
* Pengembangan aplikasi *Mobile Native* (sistem dibangun sebagai *Responsive Web Application*).

## Kriteria Keberhasilan Sistem
Proyek ini dinyatakan memenuhi kriteria keberhasilan berdasarkan parameter berikut:
1. **Integritas Fungsionalitas:** Seluruh fitur pencatatan transaksi (setoran dan penarikan) berjalan secara konsisten dan mempengaruhi saldo nasabah secara akurat.
2. **Validasi Data:** Terdapat mekanisme pencegahan input data yang tidak valid (misalnya: bobot bernilai negatif atau penarikan yang melebihi jumlah saldo aktif).
3. **Persistensi Data & Model Terstruktur:** Seluruh entitas tersimpan dalam basis data relasional yang terintegrasi (menghubungkan entitas *User*, *Category*, dan *Transaction*).
4. **Respon Antarmuka (UI/UX):** Antarmuka web dapat diakses dengan baik melalui perangkat desktop maupun *mobile browser*.

Proyek ini dikembangkan untuk mendigitalkan proses pencatatan transaksi tersebut guna meningkatkan akurasi data, transparansi, dan efisiensi operasional pengelola.
