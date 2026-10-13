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

## User Stories & Acceptance Criteria MVP

1. **Mengelola kategori sampah** — Sebagai admin, saya ingin mengelola kategori dan tarif agar nilai setoran menggunakan harga yang berlaku.
   - Admin dapat menambah dan mengubah kategori dengan nama wajib (maksimal 100 karakter) dan tarif positif yang berada dalam batas nominal sistem.
   - Kategori yang telah dipakai dalam transaksi tidak dapat dihapus.
2. **Mengelola data nasabah** — Sebagai admin, saya ingin mengelola akun nasabah agar data pemilik tabungan akurat.
   - Nama dan username wajib diisi dengan panjang sesuai batas skema; username tidak boleh sama dengan akun lain.
   - Akun baru wajib memiliki kata sandi minimal 8 karakter; perubahan kata sandi bersifat opsional tetapi tetap minimal 8 karakter bila diisi.
   - Nasabah yang memiliki transaksi tidak dapat dihapus.
3. **Mencatat setoran** — Sebagai admin, saya ingin mencatat berat sampah per kategori agar saldo nasabah bertambah sesuai tarif.
   - Setoran berisi sedikitnya satu rincian dengan berat positif maksimal 999.999,99 kg.
   - Setiap subtotal dihitung dari berat dikali tarif dan dibulatkan ke dua angka desimal; subtotal dan total harus berada dalam batas nominal sistem.
   - Transaksi, rincian, dan penambahan saldo berhasil bersama-sama atau tidak tersimpan sama sekali.
4. **Mencatat penarikan** — Sebagai admin, saya ingin mencatat pencairan saldo agar tabungan berkurang secara akurat.
   - Nominal penarikan harus positif dan tidak melebihi saldo yang tersedia saat transaksi diproses.
   - Transaksi penarikan dan pengurangan saldo tersimpan bersama-sama; saldo tidak boleh negatif.
5. **Memantau saldo dan riwayat** — Sebagai nasabah, saya ingin melihat saldo, riwayat, dan struk transaksi saya agar dapat memverifikasi tabungan.
   - Dashboard dan riwayat menampilkan data akun nasabah yang sedang login.
   - Nasabah hanya dapat membuka struk miliknya; admin dapat membuka struk seluruh nasabah.

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

## Instalasi Lokal
1. Siapkan PHP 8 atau lebih baru dengan ekstensi `pdo_mysql`, serta MySQL/MariaDB.
2. Buat database dan tabel dengan mengimpor `backend/database/schema.sql` melalui phpMyAdmin atau klien MySQL.
3. Sesuaikan host, port, nama database, username, dan kata sandi pada `backend/config/database.php`.
4. Jadikan folder `frontend/` sebagai document root server lokal. Pada XAMPP, atur virtual host Apache agar document root menunjuk ke folder tersebut. Untuk server development, jalankan dari folder proyek dengan `php -S localhost:8000 -t frontend`. Jika `php` tidak dikenali di PowerShell, gunakan `C:\xampp\php\php.exe -S localhost:8000 -t frontend`. Buka `http://localhost:8000/`.

## Menjalankan Unit Test

Unit test aturan bisnis tersedia di folder `tests/` dan tidak memerlukan database atau framework tambahan. Dari root proyek, jalankan:

```powershell
php tests\run.php
```

Runner akan menjalankan semua file `*Test.php` dan mengembalikan exit code gagal bila ada test yang gagal. Jika PHP tidak ada di `PATH` pada Windows/XAMPP, jalankan `C:\xampp\php\php.exe tests\run.php`.

### Akun Demo
| Peran | Username | Kata sandi |
| --- | --- | --- |
| Admin | `admin` | `admin123` |
| Nasabah | `budi` | `password123` |
| Nasabah | `siti` | `password123` |

Ganti kata sandi demo sebelum aplikasi digunakan di lingkungan nyata. Admin dapat mengelola tarif dan nasabah, mencatat setoran/penarikan, melihat detail transaksi, serta mencetak laporan berdasarkan periode. Nasabah dapat melihat dashboard, riwayat, dan struk transaksinya sendiri.
