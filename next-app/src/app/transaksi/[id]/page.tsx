import Link from "next/link";
import { requireUser } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatBerat, formatRupiah, formatTanggal } from "@/lib/format";
import type { RowDataPacket } from "mysql2";
import PrintButton from "@/app/PrintButton";
import { notFound } from "next/navigation";

interface Transaction extends RowDataPacket {
  id: number;
  kode_transaksi: string;
  jenis_transaksi: "setor" | "tarik";
  total_nominal: string;
  catatan: string | null;
  tanggal_transaksi: Date;
  user_id: number;
  nama_nasabah: string;
  username: string;
  nama_admin: string;
}

interface Detail extends RowDataPacket {
  nama_kategori: string;
  berat_kg: string;
  harga_per_kg: string;
  subtotal: string;
}

interface ReceiptPageProps {
  params: Promise<{ id: string }>;
}

export default async function ReceiptPage({ params }: ReceiptPageProps) {
  const viewer = await requireUser();
  const { id: idValue } = await params;
  const id = Number(idValue);
  if (!Number.isSafeInteger(id) || id < 1) notFound();

  const transactions = await query<Transaction>(
    `SELECT t.id, t.kode_transaksi, t.jenis_transaksi, t.total_nominal, t.catatan,
      t.tanggal_transaksi, t.user_id, u.nama_lengkap AS nama_nasabah, u.username,
      a.nama_lengkap AS nama_admin
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    JOIN users a ON a.id = t.admin_id
    WHERE t.id = ?`,
    [id],
  );
  const transaction = transactions[0];
  if (!transaction || (viewer.role === "nasabah" && viewer.id !== transaction.user_id)) {
    notFound();
  }

  const details = transaction.jenis_transaksi === "setor"
    ? await query<Detail>(
      `SELECT c.nama_kategori, d.berat_kg, d.harga_per_kg, d.subtotal
      FROM transaction_details d JOIN categories c ON c.id = d.category_id
      WHERE d.transaction_id = ? ORDER BY d.id`,
      [id],
    )
    : [];

  return (
    <>
      <header className="page-heading no-print">
        <div><p className="eyebrow">Bukti transaksi</p><h1>Struk transaksi</h1></div>
        <div className="form-actions">
          <Link className="button button-secondary" href={viewer.role === "admin" ? "/admin/transaksi" : "/nasabah/riwayat"}>← Kembali</Link>
          <PrintButton>Cetak struk</PrintButton>
        </div>
      </header>
      <article className="receipt panel">
        <div className="receipt-header"><span className="brand-icon">♻</span><h2>Bank Sampah Digital RT/RW</h2><p className="muted">Bukti transaksi tabungan</p></div>
        <div className="receipt-meta">
          <div><small>Kode transaksi</small><strong className="mono">{transaction.kode_transaksi}</strong></div>
          <div><small>Tanggal</small><strong>{formatTanggal(transaction.tanggal_transaksi)}</strong></div>
          <div><small>Nasabah</small><strong>{transaction.nama_nasabah} (@{transaction.username})</strong></div>
          <div><small>Dicatat oleh</small><strong>{transaction.nama_admin}</strong></div>
          <div><small>Jenis transaksi</small><strong>{transaction.jenis_transaksi === "setor" ? "Setoran sampah" : "Penarikan saldo"}</strong></div>
        </div>
        {details.length > 0 && <div className="table-scroll"><table><thead><tr><th>Kategori</th><th className="align-right">Berat</th><th className="align-right">Tarif / kg</th><th className="align-right">Subtotal</th></tr></thead><tbody>
          {details.map((detail, index) => <tr key={`${detail.nama_kategori}-${index}`}><td>{detail.nama_kategori}</td><td className="align-right">{formatBerat(detail.berat_kg)}</td><td className="align-right">{formatRupiah(detail.harga_per_kg)}</td><td className="align-right">{formatRupiah(detail.subtotal)}</td></tr>)}
        </tbody></table></div>}
        <div className="receipt-total"><span>Total {transaction.jenis_transaksi === "setor" ? "setoran" : "penarikan"}</span><strong>{formatRupiah(transaction.total_nominal)}</strong></div>
        {transaction.catatan && <div className="receipt-note"><small>Catatan</small><p>{transaction.catatan}</p></div>}
      </article>
    </>
  );
}
