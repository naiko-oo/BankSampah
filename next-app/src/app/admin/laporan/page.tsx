import Link from "next/link";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatBerat, formatRupiah, formatTanggal } from "@/lib/format";
import type { QueryResultRow } from "pg";
import PrintButton from "@/app/PrintButton";

interface ReportSummary extends QueryResultRow {
  jumlah: string;
  setoran: string;
  penarikan: string;
}

interface ReportTransaction extends QueryResultRow {
  id: number;
  kode_transaksi: string;
  jenis_transaksi: "setor" | "tarik";
  total_nominal: string;
  tanggal_transaksi: Date;
  nama_nasabah: string;
  total_berat: string;
}

interface ReportPageProps {
  searchParams: Promise<{ mulai?: string; selesai?: string }>;
}

function isValidDate(value: string): boolean {
  return /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(Date.parse(`${value}T00:00:00Z`))
    && new Date(`${value}T00:00:00Z`).toISOString().slice(0, 10) === value;
}

function todayInJakarta(): string {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Jakarta",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());
}

export default async function ReportPage({ searchParams }: ReportPageProps) {
  await requireRole("admin");
  const params = await searchParams;
  const today = todayInJakarta();
  const start = params.mulai ?? `${today.slice(0, 7)}-01`;
  const end = params.selesai ?? today;
  const valid = isValidDate(start) && isValidDate(end) && start <= end;

  let summary: ReportSummary | undefined;
  let transactions: ReportTransaction[] = [];
  let totalWeight = "0";
  if (valid) {
    const values = [`${start} 00:00:00`, end];
    [summary] = await query<ReportSummary>(
      `SELECT COUNT(*) AS jumlah,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'setor' THEN total_nominal ELSE 0 END), 0) AS setoran,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'tarik' THEN total_nominal ELSE 0 END), 0) AS penarikan
      FROM transactions WHERE tanggal_transaksi >= $1::timestamp AND tanggal_transaksi < $2::date + INTERVAL '1 day'`,
      values,
    );
    const [weight] = await query<QueryResultRow & { total_berat: string }>(
      `SELECT COALESCE(SUM(d.berat_kg), 0) AS total_berat
      FROM transaction_details d JOIN transactions t ON t.id = d.transaction_id
      WHERE t.tanggal_transaksi >= $1::timestamp AND t.tanggal_transaksi < $2::date + INTERVAL '1 day'`,
      values,
    );
    totalWeight = weight?.total_berat ?? "0";
    transactions = await query<ReportTransaction>(
      `SELECT t.id, t.kode_transaksi, t.jenis_transaksi, t.total_nominal, t.tanggal_transaksi,
        u.nama_lengkap AS nama_nasabah, COALESCE(d.total_berat, 0) AS total_berat
      FROM transactions t JOIN users u ON u.id = t.user_id
      LEFT JOIN (
        SELECT transaction_id, SUM(berat_kg) AS total_berat
        FROM transaction_details GROUP BY transaction_id
      ) d ON d.transaction_id = t.id
      WHERE t.tanggal_transaksi >= $1::timestamp AND t.tanggal_transaksi < $2::date + INTERVAL '1 day'
      ORDER BY t.tanggal_transaksi DESC, t.id DESC`,
      values,
    );
  }

  return (
    <>
      <header className="page-heading no-print">
        <div><p className="eyebrow">Analisis operasional</p><h1>Laporan transaksi</h1><p className="muted">Rekap setoran, penarikan, dan berat sampah berdasarkan periode.</p></div>
        <Link className="button button-secondary" href="/admin">← Dashboard</Link>
      </header>
      <form className="panel filter-panel no-print" method="get">
        <label>Tanggal awal<input name="mulai" type="date" required defaultValue={start} /></label>
        <label>Tanggal akhir<input name="selesai" type="date" required defaultValue={end} /></label>
        <div className="form-actions"><button className="button button-primary" type="submit">Tampilkan</button><PrintButton className="button button-secondary">Cetak laporan</PrintButton></div>
      </form>
      {!valid ? <div className="alert alert-error" role="alert">Rentang tanggal tidak valid. Pastikan tanggal awal tidak melewati tanggal akhir.</div> : (
        <>
          <p className="report-period">Periode {start} sampai {end}</p>
          <section className="stats-grid report-stats">
            <article className="stat-card"><span className="stat-label">Jumlah transaksi</span><strong>{summary?.jumlah ?? 0}</strong></article>
            <article className="stat-card"><span className="stat-label">Total nilai setoran</span><strong className="text-green">{formatRupiah(summary?.setoran ?? 0)}</strong></article>
            <article className="stat-card"><span className="stat-label">Total penarikan</span><strong>{formatRupiah(summary?.penarikan ?? 0)}</strong></article>
            <article className="stat-card"><span className="stat-label">Berat sampah terkumpul</span><strong>{formatBerat(totalWeight)}</strong></article>
          </section>
          <section className="panel">
            {transactions.length === 0 ? <p className="empty-state">Tidak ada transaksi pada periode ini.</p> : (
              <div className="table-scroll"><table><thead><tr><th>Tanggal</th><th>Kode transaksi</th><th>Nasabah</th><th>Jenis</th><th className="align-right">Berat</th><th className="align-right">Nominal</th></tr></thead><tbody>
                {transactions.map((transaction) => <tr key={transaction.id}>
                  <td>{formatTanggal(transaction.tanggal_transaksi)}</td>
                  <td><Link className="text-link mono" href={`/transaksi/${transaction.id}`}>{transaction.kode_transaksi}</Link></td>
                  <td>{transaction.nama_nasabah}</td>
                  <td><span className={`badge ${transaction.jenis_transaksi === "setor" ? "badge-green" : "badge-amber"}`}>{transaction.jenis_transaksi === "setor" ? "Setoran" : "Penarikan"}</span></td>
                  <td className="align-right">{transaction.jenis_transaksi === "setor" ? formatBerat(transaction.total_berat) : "—"}</td>
                  <td className="align-right">{formatRupiah(transaction.total_nominal)}</td>
                </tr>)}
              </tbody></table></div>
            )}
          </section>
        </>
      )}
    </>
  );
}
