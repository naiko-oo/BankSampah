import Link from "next/link";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatRupiah, formatTanggal } from "@/lib/format";
import type { QueryResultRow } from "pg";

interface StatsRow extends QueryResultRow {
  nasabah: string;
  kategori: string;
  setoran: string;
  penarikan: string;
}

interface RecentTransaction extends QueryResultRow {
  id: number;
  kode_transaksi: string;
  jenis_transaksi: "setor" | "tarik";
  total_nominal: string;
  tanggal_transaksi: Date;
  nama_nasabah: string;
}

export default async function AdminDashboard() {
  await requireRole("admin");
  const [stats] = await query<StatsRow>(
    `SELECT
      (SELECT COUNT(*) FROM users WHERE role = 'nasabah') AS nasabah,
      (SELECT COUNT(*) FROM categories) AS kategori,
      COALESCE((SELECT SUM(total_nominal) FROM transactions WHERE jenis_transaksi = 'setor'), 0) AS setoran,
      COALESCE((SELECT SUM(total_nominal) FROM transactions WHERE jenis_transaksi = 'tarik'), 0) AS penarikan`,
  );
  const transactions = await query<RecentTransaction>(
    `SELECT t.id, t.kode_transaksi, t.jenis_transaksi, t.total_nominal, t.tanggal_transaksi,
      u.nama_lengkap AS nama_nasabah
    FROM transactions t JOIN users u ON u.id = t.user_id
    ORDER BY t.tanggal_transaksi DESC, t.id DESC LIMIT 8`,
  );

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Ringkasan pengurus</p><h1>Dashboard</h1><p className="muted">Pantau aktivitas bank sampah dari satu tempat.</p></div>
        <Link className="button button-primary" href="/admin/transaksi/setor">＋ Catat setoran</Link>
      </header>
      <section className="stats-grid" aria-label="Statistik">
        <article className="stat-card"><span className="stat-icon">♙</span><span className="stat-label">Total nasabah</span><strong>{stats?.nasabah ?? 0}</strong><small>akun terdaftar</small></article>
        <article className="stat-card"><span className="stat-icon">♻</span><span className="stat-label">Kategori sampah</span><strong>{stats?.kategori ?? 0}</strong><small>jenis aktif</small></article>
        <article className="stat-card"><span className="stat-icon">↙</span><span className="stat-label">Total setoran</span><strong className="text-green">{formatRupiah(stats?.setoran ?? 0)}</strong><small>akumulasi seluruh waktu</small></article>
        <article className="stat-card"><span className="stat-icon">↗</span><span className="stat-label">Total penarikan</span><strong>{formatRupiah(stats?.penarikan ?? 0)}</strong><small>akumulasi seluruh waktu</small></article>
      </section>
      <section className="panel">
        <div className="panel-heading"><div><h2>Transaksi terbaru</h2><p className="muted">Aktivitas terakhir yang tercatat.</p></div><Link className="text-link" href="/admin/transaksi">Lihat semua →</Link></div>
        {transactions.length === 0 ? <p className="empty-state">Belum ada transaksi yang tercatat.</p> : (
          <div className="table-scroll"><table><thead><tr><th>Kode transaksi</th><th>Nasabah</th><th>Jenis</th><th>Tanggal</th><th className="align-right">Nominal</th></tr></thead>
            <tbody>{transactions.map((transaction) => <tr key={transaction.id}>
              <td className="mono">{transaction.kode_transaksi}</td><td>{transaction.nama_nasabah}</td>
              <td><span className={`badge ${transaction.jenis_transaksi === "setor" ? "badge-green" : "badge-amber"}`}>{transaction.jenis_transaksi === "setor" ? "Setoran" : "Penarikan"}</span></td>
              <td>{formatTanggal(transaction.tanggal_transaksi)}</td><td className="align-right">{formatRupiah(transaction.total_nominal)}</td>
            </tr>)}</tbody></table></div>
        )}
      </section>
    </>
  );
}
