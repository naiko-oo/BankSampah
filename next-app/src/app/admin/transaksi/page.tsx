import Link from "next/link";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatRupiah, formatTanggal } from "@/lib/format";
import type { RowDataPacket } from "mysql2";

interface Transaction extends RowDataPacket {
  id: number;
  kode_transaksi: string;
  jenis_transaksi: "setor" | "tarik";
  total_nominal: string;
  tanggal_transaksi: Date;
  nama_nasabah: string;
}

export default async function TransactionsPage() {
  await requireRole("admin");
  const transactions = await query<Transaction>(
    `SELECT t.id, t.kode_transaksi, t.jenis_transaksi, t.total_nominal, t.tanggal_transaksi,
      u.nama_lengkap AS nama_nasabah
    FROM transactions t JOIN users u ON u.id = t.user_id
    ORDER BY t.tanggal_transaksi DESC, t.id DESC LIMIT 500`,
  );

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Aktivitas keuangan</p><h1>Transaksi</h1><p className="muted">Daftar setoran dan penarikan yang telah dicatat.</p></div>
        <div className="form-actions"><Link className="button button-secondary" href="/admin/transaksi/tarik">− Catat penarikan</Link><Link className="button button-primary" href="/admin/transaksi/setor">＋ Catat setoran</Link></div>
      </header>
      <section className="panel">
        {transactions.length === 0 ? <p className="empty-state">Belum ada transaksi yang tercatat.</p> : (
          <div className="table-scroll"><table><thead><tr><th>Tanggal</th><th>Kode transaksi</th><th>Nasabah</th><th>Jenis</th><th className="align-right">Nominal</th><th></th></tr></thead><tbody>
            {transactions.map((transaction) => <tr key={transaction.id}>
              <td>{formatTanggal(transaction.tanggal_transaksi)}</td><td className="mono">{transaction.kode_transaksi}</td><td>{transaction.nama_nasabah}</td>
              <td><span className={`badge ${transaction.jenis_transaksi === "setor" ? "badge-green" : "badge-amber"}`}>{transaction.jenis_transaksi === "setor" ? "Setoran" : "Penarikan"}</span></td>
              <td className="align-right">{formatRupiah(transaction.total_nominal)}</td><td><Link className="text-link" href={`/transaksi/${transaction.id}`}>Struk →</Link></td>
            </tr>)}
          </tbody></table></div>
        )}
      </section>
    </>
  );
}
