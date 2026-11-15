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
}

interface HistoryPageProps {
  searchParams: Promise<{ jenis?: string; mulai?: string; selesai?: string }>;
}

function isValidDate(value: string): boolean {
  return /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(Date.parse(`${value}T00:00:00Z`))
    && new Date(`${value}T00:00:00Z`).toISOString().slice(0, 10) === value;
}

export default async function MemberHistoryPage({ searchParams }: HistoryPageProps) {
  const user = await requireRole("nasabah");
  const params = await searchParams;
  const kind = params.jenis === "setor" || params.jenis === "tarik" ? params.jenis : "";
  const start = params.mulai ?? "";
  const end = params.selesai ?? "";
  const invalidFilter =
    (params.jenis !== undefined && params.jenis !== "" && !kind) ||
    (start !== "" && !isValidDate(start)) ||
    (end !== "" && !isValidDate(end)) ||
    (isValidDate(start) && isValidDate(end) && start > end);
  const conditions = ["user_id = ?"];
  const values: (string | number)[] = [user.id];
  if (kind) {
    conditions.push("jenis_transaksi = ?");
    values.push(kind);
  }
  if (start && isValidDate(start)) {
    conditions.push("tanggal_transaksi >= ?");
    values.push(`${start} 00:00:00`);
  }
  if (end && isValidDate(end)) {
    conditions.push("tanggal_transaksi < DATE_ADD(?, INTERVAL 1 DAY)");
    values.push(end);
  }
  const transactions = invalidFilter ? [] : await query<Transaction>(
      `SELECT id, kode_transaksi, jenis_transaksi, total_nominal, tanggal_transaksi
      FROM transactions WHERE ${conditions.join(" AND ")}
      ORDER BY tanggal_transaksi DESC, id DESC LIMIT 500`,
      values,
    );

  return (
    <>
      <header className="page-heading"><div><p className="eyebrow">Aktivitas tabungan</p><h1>Riwayat transaksi</h1><p className="muted">Catatan setoran dan penarikan pada akun Anda.</p></div></header>
      <form className="panel filter-panel" method="get">
        <label>Jenis transaksi<select name="jenis" defaultValue={kind}><option value="">Semua jenis</option><option value="setor">Setoran</option><option value="tarik">Penarikan</option></select></label>
        <label>Dari tanggal<input type="date" name="mulai" defaultValue={start} /></label>
        <label>Sampai tanggal<input type="date" name="selesai" defaultValue={end} /></label>
        <div className="form-actions"><button className="button button-primary" type="submit">Terapkan</button><Link className="button button-secondary" href="/nasabah/riwayat">Atur ulang</Link></div>
      </form>
      {invalidFilter && <div className="alert alert-error" role="alert">Filter tidak valid. Periksa jenis dan rentang tanggal.</div>}
      <section className="panel">
        {transactions.length === 0 ? <p className="empty-state">Belum ada transaksi yang sesuai.</p> : (
          <div className="table-scroll"><table><thead><tr><th>Kode transaksi</th><th>Jenis</th><th>Tanggal</th><th className="align-right">Nominal</th><th></th></tr></thead><tbody>
            {transactions.map((transaction) => <tr key={transaction.id}>
              <td className="mono">{transaction.kode_transaksi}</td>
              <td><span className={`badge ${transaction.jenis_transaksi === "setor" ? "badge-green" : "badge-amber"}`}>{transaction.jenis_transaksi === "setor" ? "Setoran" : "Penarikan"}</span></td>
              <td>{formatTanggal(transaction.tanggal_transaksi)}</td><td className="align-right">{formatRupiah(transaction.total_nominal)}</td>
              <td><Link className="text-link" href={`/transaksi/${transaction.id}`}>Lihat struk →</Link></td>
            </tr>)}
          </tbody></table></div>
        )}
      </section>
    </>
  );
}
