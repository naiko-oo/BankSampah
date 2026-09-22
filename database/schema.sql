-- ==========================================================
-- SISTEM INFORMASI BANK SAMPAH DIGITAL (RT/RW)
-- Skema Basis Data Relasional MySQL
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `bank_sampah` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bank_sampah`;

-- 1. TABEL PENGGUNA (USERS: Admin & Nasabah)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `nomor_telepon` VARCHAR(20) DEFAULT NULL,
    `alamat` TEXT DEFAULT NULL,
    `role` ENUM('admin', 'nasabah') NOT NULL DEFAULT 'nasabah',
    `saldo` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL KATEGORI SAMPAH (CATEGORIES)
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `harga_per_kg` DECIMAL(10, 2) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL TRANSAKSI INDUK (TRANSACTIONS: Setor & Tarik)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `admin_id` INT NOT NULL,
    `jenis_transaksi` ENUM('setor', 'tarik') NOT NULL,
    `total_nominal` DECIMAL(12, 2) NOT NULL,
    `catatan` TEXT DEFAULT NULL,
    `tanggal_transaksi` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trans_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL RINCIAN TRANSAKSI SETORAN (TRANSACTION_DETAILS)
CREATE TABLE IF NOT EXISTS `transaction_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `berat_kg` DECIMAL(8, 2) NOT NULL,
    `harga_per_kg` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(12, 2) NOT NULL,
    CONSTRAINT `fk_detail_trans` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_detail_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- DATA AWAL (SEEDER) UNTUK PENGUJIAN
-- ==========================================================

-- Data Akun Pengguna:
-- 1. Admin: admin / admin123
-- 2. Nasabah: budi / password123 (Saldo awal: 50.000)
-- 3. Nasabah: siti / password123 (Saldo awal: 0)
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `nomor_telepon`, `alamat`, `role`, `saldo`) VALUES
(1, 'admin', '$2y$10$xOsa/cpReihIPY5XyWAwmuVTSRy6nTFOjdxK/ODBa9Gzg5CZTeTI.', 'Pengurus Bank Sampah', '081234567890', 'Kantor Sekretariat RT 03/RW 05', 'admin', 0.00),
(2, 'budi', '$2y$10$mEtrev0HKhYpk5k6p4ffzOgqwrnY4UVeC8TuMP5sMM5o367dZaLIy', 'Budi Santoso', '081298765432', 'Jl. Kenanga No. 12, RT 03/RW 05', 'nasabah', 50000.00),
(3, 'siti', '$2y$10$mEtrev0HKhYpk5k6p4ffzOgqwrnY4UVeC8TuMP5sMM5o367dZaLIy', 'Siti Aminah', '081311223344', 'Jl. Mawar No. 05, RT 03/RW 05', 'nasabah', 0.00)
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Data Kategori Sampah
INSERT INTO `categories` (`id`, `nama_kategori`, `harga_per_kg`, `deskripsi`) VALUES
(1, 'Botol Plastik PET Bersih', 3500.00, 'Botol air mineral bening, bersih tanpa tutup dan label.'),
(2, 'Gelas Plastik (PP)', 2500.00, 'Gelas plastik bekas air mineral, bersih.'),
(3, 'Kardus Bekas / Box', 2000.00, 'Kardus kering, tidak basah dan dipipihkan.'),
(4, 'Kertas HVS / Buku', 1500.00, 'Buku tulis, dokumen, majalah bekas non-glossy.'),
(5, 'Kaleng Alumunium / Logam', 4500.00, 'Kaleng minuman ringan, seng, besi bekas.')
ON DUPLICATE KEY UPDATE `nama_kategori`=`nama_kategori`;
