import Link from "next/link";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatRupiah, formatTanggal } from "@/lib/format";
import type { RowDataPacket } from "mysql2";

interface MemberBalance extends RowDataPacket {
  saldo: string;
}

interface RecentTransaction extends RowDataPacket {
  id: number;
  kode_transaksi: string;
  jenis_transaksi: "setor" | "tarik";
  total_nominal: string;
  tanggal_transaksi: Date;
}

export default async function MemberDashboard() {
  const user = await requireRole("nasabah");
  const [account] = await query<MemberBalance>("SELECT saldo FROM users WHERE id = ? AND role = 'nasabah'", [user.id]);
  const transactions = await query<RecentTransaction>(
    "SELECT id, kode_transaksi, jenis_transaksi, total_nominal, tanggal_transaksi FROM transactions WHERE user_id = ? ORDER BY tanggal_transaksi DESC, id DESC LIMIT 5",
    [user.id],
  );

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Portal nasabah</p><h1>Halo, {user.namaLengkap.split(" ")[0]}</h1><p className="muted">Berikut ringkasan tabungan dan aktivitas Anda.</p></div>
        <Link className="button button-secondary" href="/nasabah/riwayat">Riwayat transaksi</Link>
      </header>
      <section className="balance-card">
        <div><span className="balance-label">Saldo tabungan saat ini</span><strong>{formatRupiah(account?.saldo ?? 0)}</strong><span className="balance-note">Saldo diperbarui setiap transaksi setoran atau penarikan dicatat.</span></div>
        <span className="balance-art" aria-hidden="true">♻</span>
      </section>
      <section className="panel">
        <div className="panel-heading"><div><h2>Aktivitas terbaru</h2><p className="muted">Riwayat transaksi tabungan Anda.</p></div><Link className="text-link" href="/nasabah/riwayat">Lihat riwayat →</Link></div>
        {transactions.length === 0 ? <p className="empty-state">Belum ada transaksi pada akun Anda.</p> : (
          <div className="table-scroll"><table><thead><tr><th>Kode transaksi</th><th>Jenis</th><th>Tanggal</th><th className="align-right">Nominal</th></tr></thead>
            <tbody>{transactions.map((transaction) => <tr key={transaction.id}>
              <td className="mono">{transaction.kode_transaksi}</td>
              <td><span className={`badge ${transaction.jenis_transaksi === "setor" ? "badge-green" : "badge-amber"}`}>{transaction.jenis_transaksi === "setor" ? "Setoran" : "Penarikan"}</span></td>
              <td>{formatTanggal(transaction.tanggal_transaksi)}</td><td className="align-right">{formatRupiah(transaction.total_nominal)}</td>
            </tr>)}</tbody></table></div>
        )}
      </section>
    </>
  );
}
