# Sistem Informasi Bank Sampah Digital RT/RW

## Tujuan

Sistem Informasi Bank Sampah Digital RT/RW dibuat untuk membantu pengurus mencatat setoran sampah, penarikan saldo, data nasabah, kategori sampah, dan laporan transaksi secara terstruktur.

Aplikasi ini menggantikan pencatatan manual menggunakan buku besar. Nasabah dapat melihat saldo tabungan dan riwayat transaksi, sedangkan pengurus dapat mengelola data serta melakukan rekapitulasi administrasi bank sampah.

Sistem memiliki dua peran pengguna:

* **Admin atau Pengurus:** mengelola data operasional dan mencatat transaksi nasabah.
* **Nasabah atau Warga:** memantau saldo, setoran, penarikan, tarif, dan riwayat transaksi pribadi.

## Tech Stack

* **Frontend:** PHP server-rendered, HTML, CSS, Bootstrap 5, dan Bootstrap Icons.
* **Backend:** PHP.
* **Database:** MySQL.
* **Koneksi Database:** PDO dengan prepared statement.

## Aturan Kode

* Gunakan bahasa Indonesia untuk label, pesan, validasi, dan tampilan pengguna.
* Gunakan prepared statement untuk query yang menerima input pengguna.
* Gunakan `htmlspecialchars` saat menampilkan data dari database.
* Gunakan fungsi helper bersama untuk format Rupiah, berat, tanggal, autentikasi, dan pesan notifikasi.
* Gunakan validasi server untuk seluruh data transaksi.
* Nilai berat sampah tidak boleh negatif atau kosong.
* Nilai penarikan harus lebih besar dari nol dan tidak boleh melebihi saldo nasabah.
* Gunakan kata sandi yang sudah di-hash menggunakan `password_hash`.
* Batasi halaman berdasarkan peran pengguna menggunakan pemeriksaan sesi.
* Gunakan nama tabel dan kolom yang konsisten dengan skema database.
* Hindari duplikasi struktur halaman dengan menggunakan template header dan footer.
* Jangan menambahkan komentar kode kecuali benar-benar diperlukan.

## Entitas Utama

### 1. User

* `id`
* `username`
* `password`
* `nama_lengkap`
* `nomor_telepon`
* `alamat`
* `role`
* `saldo`
* `created_at`

Entitas `User` menyimpan akun Admin dan Nasabah. Nilai `role` membedakan hak akses pengguna.

### 2. Category

* `id`
* `nama_kategori`
* `harga_per_kg`
* `deskripsi`
* `created_at`

Entitas `Category` menyimpan jenis sampah yang diterima beserta tarif pembelian per kilogram.

### 3. Transaction

* `id`
* `kode_transaksi`
* `user_id`
* `admin_id`
* `jenis_transaksi`
* `total_nominal`
* `catatan`
* `tanggal_transaksi`

Entitas `Transaction` menyimpan transaksi `setor` dan `tarik`. Setiap transaksi menghubungkan nasabah dengan Admin yang mencatatnya.

### 4. TransactionDetail

* `id`
* `transaction_id`
* `category_id`
* `berat_kg`
* `harga_per_kg`
* `subtotal`

Entitas `TransactionDetail` menyimpan rincian kategori sampah pada transaksi setoran. Satu transaksi setoran dapat memiliki lebih dari satu rincian kategori.

## Aturan Database

* Username pengguna harus unik.
* Kode transaksi harus unik.
* Role pengguna hanya boleh bernilai `admin` atau `nasabah`.
* Jenis transaksi hanya boleh bernilai `setor` atau `tarik`.
* Saldo nasabah tidak boleh menjadi negatif.
* Nilai berat, harga, subtotal, dan nominal harus bernilai valid.
* Satu transaksi dapat memiliki beberapa rincian setoran.
* Penghapusan transaksi induk akan menghapus rincian transaksi terkait.
* Kategori yang sudah digunakan oleh rincian transaksi tidak boleh dihapus sembarangan.
* Data transaksi yang sudah tercatat harus dipertahankan sebagai riwayat administrasi.
* Harga per kilogram disimpan di `transaction_details` agar nilai transaksi lama tetap sesuai dengan tarif saat transaksi dibuat.
* Gunakan relasi foreign key antara pengguna, kategori, transaksi, dan rincian transaksi.
* Gunakan transaksi database ketika menyimpan setoran atau penarikan bersama perubahan saldo.

## Fitur Backend

### 1. Autentikasi Pengguna

* Login menggunakan username dan kata sandi.
* Verifikasi kata sandi menggunakan hash.
* Penyimpanan identitas pengguna menggunakan sesi PHP.
* Logout dengan menghapus sesi pengguna.
* Pengalihan pengguna ke dashboard sesuai peran.
* Penolakan akses jika pengguna belum login atau tidak memiliki peran yang sesuai.

### 2. Pengelolaan Kategori Sampah

* Menambah kategori sampah.
* Melihat daftar kategori.
* Mengubah nama, tarif, dan deskripsi kategori.
* Menghapus kategori yang belum digunakan.
* Menampilkan tarif kategori pada halaman publik dan dashboard nasabah.

### 3. Pengelolaan Data Nasabah

* Menambah akun nasabah.
* Melihat data nasabah.
* Mengubah nama, username, nomor telepon, alamat, dan kata sandi.
* Melihat saldo nasabah.
* Menghapus atau menonaktifkan data nasabah sesuai kebutuhan administrasi.

### 4. Pencatatan Setoran Sampah

* Memilih nasabah yang melakukan setoran.
* Memilih kategori sampah.
* Memasukkan berat sampah dalam kilogram.
* Mengambil tarif kategori yang berlaku.
* Menghitung subtotal dengan rumus `berat_kg x harga_per_kg`.
* Menjumlahkan beberapa rincian kategori dalam satu transaksi.
* Menambahkan nilai setoran ke saldo nasabah.
* Membuat kode transaksi setoran.
* Menyimpan transaksi induk dan rincian setoran.

### 5. Pencatatan Penarikan Saldo

* Memilih nasabah yang melakukan penarikan.
* Memasukkan nominal penarikan.
* Memeriksa kecukupan saldo nasabah.
* Mengurangi saldo setelah penarikan berhasil.
* Membuat kode transaksi penarikan.
* Menyimpan catatan dan waktu penarikan.
* Menolak penarikan yang melebihi saldo.

### 6. Dashboard dan Laporan

* Menampilkan jumlah nasabah.
* Menampilkan total saldo yang beredar.
* Menampilkan total berat sampah terkumpul.
* Menampilkan jumlah transaksi.
* Menampilkan transaksi terbaru.
* Menampilkan riwayat transaksi berdasarkan nasabah.
* Menyediakan rekapitulasi transaksi berdasarkan periode.
* Menyediakan detail atau struk transaksi.

## Halaman Frontend

### 1. Beranda

* Menampilkan nama dan tujuan Sistem Informasi Bank Sampah Digital.
* Menampilkan tombol masuk ke sistem.
* Menampilkan daftar kategori dan tarif sampah terkini.
* Menampilkan informasi bahwa setoran sampah dapat menjadi saldo tabungan.

### 2. Halaman Login

* Form username dan kata sandi.
* Pesan kesalahan ketika data login tidak valid.
* Pengarahan Admin ke dashboard pengurus.
* Pengarahan Nasabah ke dashboard pribadi.
* Informasi akun demo untuk pengujian lokal.

### 3. Dashboard Admin

* Kartu total nasabah.
* Kartu total saldo beredar.
* Kartu total sampah terkumpul.
* Kartu total transaksi.
* Tabel transaksi terbaru.
* Tombol catat setoran sampah.
* Tombol tarik saldo tunai.
* Menu data nasabah, kategori, transaksi, dan laporan.

### 4. Dashboard Nasabah

* Menampilkan sapaan dan informasi profil nasabah.
* Menampilkan saldo tabungan saat ini.
* Menampilkan total sampah yang disetorkan.
* Menampilkan total nilai setoran.
* Menampilkan total saldo yang ditarik.
* Menampilkan frekuensi transaksi.
* Menampilkan lima transaksi terakhir.
* Menampilkan tarif sampah terkini.

### 5. Pengelolaan Kategori

* Tabel kategori sampah.
* Form tambah dan ubah kategori.
* Input nama kategori, harga per kilogram, dan deskripsi.
* Tombol hapus dengan dialog konfirmasi.

### 6. Pengelolaan Nasabah

* Tabel data warga atau nasabah.
* Form pendaftaran nasabah baru.
* Form ubah data nasabah.
* Tampilan username, nama, kontak, alamat, dan saldo.
* Tombol hapus dengan dialog konfirmasi.

### 7. Transaksi dan Riwayat

* Form setoran dengan pilihan nasabah dan kategori.
* Input berat sampah dan perhitungan subtotal.
* Form penarikan saldo.
* Tabel semua transaksi.
* Filter berdasarkan jenis atau periode transaksi.
* Halaman detail atau struk transaksi.
* Halaman riwayat transaksi pribadi nasabah.

## Persyaratan Antarmuka

* Gunakan bahasa Indonesia untuk seluruh teks antarmuka.
* Buat tampilan sederhana, bersih, dan responsif.
* Gunakan warna hijau sebagai identitas tema lingkungan.
* Gunakan kartu untuk ringkasan statistik.
* Gunakan tabel responsif untuk data transaksi.
* Gunakan badge hijau untuk setoran dan badge kuning atau oranye untuk penarikan.
* Gunakan formulir yang memiliki label dan validasi yang jelas.
* Tampilkan pesan berhasil, peringatan, dan kesalahan.
* Tampilkan keadaan kosong jika belum ada data.
* Gunakan dialog konfirmasi sebelum menghapus data.
* Jangan menambahkan grafik pada versi pertama.
* Pastikan tampilan dapat digunakan pada desktop dan peramban seluler.

## Deliverables

* Source code PHP untuk halaman publik, autentikasi, admin, nasabah, dan template.
* File konfigurasi koneksi MySQL menggunakan PDO.
* Skema database MySQL pada `database/schema.sql`.
* Data awal akun demo dan kategori sampah.
* Fitur login, logout, dan pembatasan akses berdasarkan peran.
* Dashboard Admin dan Dashboard Nasabah.
* Modul CRUD kategori sampah.
* Modul CRUD data nasabah.
* Modul transaksi setoran dan penarikan.
* Modul riwayat dan detail transaksi.
* Modul laporan dan rekapitulasi.
* README berisi instalasi, konfigurasi database, dan cara menjalankan aplikasi.
* Dokumentasi detail aplikasi pada `WEBDETAIL.md`.

## Struktur Proyek

```text
bank-sampah/
  index.php
  login.php
  logout.php
  README.md
  WEBDETAIL.md
  admin/
    index.php
    kategori/
    nasabah/
    transaksi/
    laporan/
  config/
    database.php
    helpers.php
  database/
    schema.sql
  nasabah/
    index.php
    riwayat.php
  templates/
    header.php
    footer.php
```

## Shared Package Requirements

Aplikasi ini belum menggunakan monorepo, npm workspace, atau package shared karena backend dan frontend masih berada dalam aplikasi PHP yang sama.

Sebagai pengganti shared package, fungsi dan konfigurasi yang digunakan bersama ditempatkan di:

* `config/helpers.php` untuk autentikasi, format data, sanitasi, dan pesan.
* `templates/header.php` untuk navigasi serta struktur halaman.
* `templates/footer.php` untuk penutup halaman dan aset JavaScript.
* `database/schema.sql` untuk struktur dan relasi data.

Jika aplikasi dikembangkan menjadi frontend dan backend terpisah pada versi berikutnya, tipe data seperti `User`, `Category`, `Transaction`, dan `TransactionDetail` dapat dipindahkan ke package bersama.

## Aturan Model

* Model database utama terdiri dari `User`, `Category`, `Transaction`, dan `TransactionDetail`.
* Data Admin dan Nasabah disimpan dalam model `User` dengan pembeda pada kolom `role`.
* Model `Transaction` menyimpan informasi umum setoran atau penarikan.
* Model `TransactionDetail` hanya digunakan untuk rincian setoran sampah.
* Model `Category` menyimpan tarif sampah yang sedang berlaku.
* Harga transaksi lama harus tetap disimpan pada `TransactionDetail`.
* Saldo pada `User` harus diperbarui bersama transaksi secara konsisten.
* Relasi model harus mengikuti foreign key pada database.
* Data dari database harus divalidasi sebelum ditampilkan atau digunakan untuk perhitungan.
* Struktur model tidak boleh membuat saldo, transaksi, atau rincian setoran menjadi ambigu.
