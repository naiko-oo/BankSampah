import Link from "next/link";
import { recordWithdrawal } from "@/app/actions/admin";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatRupiah } from "@/lib/format";
import type { RowDataPacket } from "mysql2";

interface MemberOption extends RowDataPacket {
  id: number;
  nama_lengkap: string;
  username: string;
  saldo: string;
}

interface WithdrawalPageProps {
  searchParams: Promise<{ error?: string }>;
}

export default async function WithdrawalPage({ searchParams }: WithdrawalPageProps) {
  await requireRole("admin");
  const [{ error }, members] = await Promise.all([
    searchParams,
    query<MemberOption>("SELECT id, nama_lengkap, username, saldo FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap"),
  ]);

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Transaksi baru</p><h1>Penarikan saldo</h1><p className="muted">Saldo diperiksa kembali saat transaksi disimpan.</p></div>
        <Link className="button button-secondary" href="/admin/transaksi">← Transaksi</Link>
      </header>
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      <form action={recordWithdrawal} className="panel form-panel narrow-panel form-stack">
        <label>Nasabah
          <select name="user_id" required defaultValue="">
            <option value="" disabled>Pilih nasabah</option>
            {members.map((member) => <option key={member.id} value={member.id}>{member.nama_lengkap} (@{member.username}) · {formatRupiah(member.saldo)}</option>)}
          </select>
        </label>
        <label>Nominal penarikan (Rp)<input name="total_nominal" type="number" min="0.01" max="9999999999.99" step="0.01" required /></label>
        <label>Catatan (opsional)<textarea name="catatan" rows={3} maxLength={2000} /></label>
        <div className="form-actions"><Link className="button button-secondary" href="/admin/transaksi">Batal</Link><button className="button button-primary" type="submit" disabled={!members.length}>Catat penarikan</button></div>
      </form>
    </>
  );
}
