# Bank Sampah Digital — Next.js

Versi Next.js full-stack untuk deployment di Vercel. Aplikasi PHP lama tetap berada di folder induk dan tidak diubah. Versi ini menggunakan Next.js App Router, React, TypeScript, PostgreSQL (Supabase), dan session cookie yang ditandatangani.

## Fitur

- Login dan pembatasan akses untuk admin dan nasabah.
- Pengelolaan kategori sampah dan tarif.
- Pengelolaan akun nasabah.
- Setoran multi-kategori dan penarikan saldo menggunakan transaksi database atomik.
- Dashboard, riwayat, laporan periode, dan struk transaksi yang dapat dicetak.

## Menjalankan lokal

1. Pasang Node.js 22 atau lebih baru.
2. Buat tabel PostgreSQL dengan menjalankan `supabase/schema.sql` di Supabase SQL Editor.
3. Salin `.env.example` menjadi `.env.local`, lalu isi `DATABASE_URL` dengan connection string PostgreSQL Supabase.
4. Buat rahasia session acak dengan PowerShell:

   ```powershell
   node -e "console.log(require('crypto').randomBytes(32).toString('base64url'))"
   ```

   Masukkan hasilnya sebagai `SESSION_PASSWORD` di `.env.local`. Jangan commit file rahasia.
5. Jalankan:

   ```powershell
   npm install
   npm run dev
   ```

   Buka `http://localhost:3000`.

Akun demo yang diisi oleh skema awal:

| Peran | Username | Kata sandi |
| --- | --- | --- |
| Admin | `admin` | `admin123` |
| Nasabah | `budi` | `password123` |
| Nasabah | `siti` | `password123` |

Ganti kata sandi demo sebelum aplikasi dipakai di lingkungan nyata.

## Deployment ke Vercel

1. Impor repository Git ke Vercel dan atur **Root Directory** menjadi `next-app`.
2. Tambahkan `DATABASE_URL` dan `SESSION_PASSWORD` pada Environment Variables Vercel untuk environment yang digunakan.
3. Gunakan connection string PostgreSQL Supabase untuk `DATABASE_URL` dan jangan membagikan kredensial database.
4. Jalankan `supabase/schema.sql` pada Supabase SQL Editor. Untuk menyalin data dari database MySQL lokal, salin `.env.migration.example` ke `.env.migration.local`, isi `SOURCE_MYSQL_URL`, dan jalankan `npm run migrate:mysql`. Skrip hanya menyalin ke tabel Supabase yang masih kosong; backup sumber terlebih dahulu.
5. Deploy aplikasi. URL publik mengarah ke aplikasi Next.js; database tetap dikelola di layanan terpisah.

Jangan gunakan server MySQL XAMPP lokal sebagai database produksi publik. Skrip migrasi memerlukan database sumber MySQL lokal yang sudah berisi data. Batasi kredensial database, siapkan backup rutin, dan ganti kata sandi demo.

## Pemeriksaan

```powershell
npm test
npm run lint
npm run build
```

Unit test menggunakan Vitest dan mencakup validasi kategori/nasabah, batas nominal dan berat, serta aturan form setoran dan saldo penarikan.
