import Link from "next/link";
import { deleteMember, saveMember } from "@/app/actions/admin";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatRupiah } from "@/lib/format";
import type { QueryResultRow } from "pg";

interface Member extends QueryResultRow {
  id: number;
  username: string;
  nama_lengkap: string;
  nomor_telepon: string | null;
  alamat: string | null;
  saldo: string;
}

interface MemberPageProps {
  searchParams: Promise<{ edit?: string; error?: string; saved?: string }>;
}

export default async function MembersPage({ searchParams }: MemberPageProps) {
  await requireRole("admin");
  const { edit, error, saved } = await searchParams;
  const members = await query<Member>(
    "SELECT id, username, nama_lengkap, nomor_telepon, alamat, saldo FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap",
  );
  const editingId = Number(edit);
  const editing = Number.isSafeInteger(editingId) && editingId > 0
    ? members.find((member) => member.id === editingId)
    : undefined;

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Data pengguna</p><h1>Data nasabah</h1><p className="muted">Kelola akun dan informasi nasabah bank sampah.</p></div>
        <Link className="button button-secondary" href="/admin">← Dashboard</Link>
      </header>
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      {saved && <div className="alert alert-success" role="status">Perubahan data nasabah berhasil disimpan.</div>}
      <div className="content-grid">
        <section className="panel form-panel">
          <div className="panel-heading"><div><h2>{editing ? "Ubah data nasabah" : "Daftarkan nasabah"}</h2><p className="muted">Kata sandi minimal 8 karakter.</p></div></div>
          <form action={saveMember} className="form-stack">
            <input type="hidden" name="id" value={editing?.id ?? ""} />
            <label>Nama lengkap<input name="nama_lengkap" maxLength={100} required defaultValue={editing?.nama_lengkap ?? ""} /></label>
            <label>Username<input name="username" maxLength={50} required defaultValue={editing?.username ?? ""} autoComplete="off" /></label>
            <label>Nomor telepon<input name="nomor_telepon" maxLength={20} defaultValue={editing?.nomor_telepon ?? ""} /></label>
            <label>Alamat<textarea name="alamat" rows={3} maxLength={2000} defaultValue={editing?.alamat ?? ""} /></label>
            <label>Kata sandi{editing ? " (opsional untuk mengganti)" : ""}
              <input name="password" type="password" minLength={8} required={!editing} autoComplete="new-password" />
            </label>
            <div className="form-actions"><button className="button button-primary" type="submit">{editing ? "Simpan perubahan" : "Buat akun nasabah"}</button>{editing && <Link className="button button-secondary" href="/admin/nasabah">Batal</Link>}</div>
          </form>
        </section>
        <section className="panel">
          <div className="panel-heading"><div><h2>Nasabah terdaftar</h2><p className="muted">{members.length} akun nasabah.</p></div></div>
          {members.length === 0 ? <p className="empty-state">Belum ada akun nasabah.</p> : (
            <div className="table-scroll"><table><thead><tr><th>Nasabah</th><th>Kontak</th><th>Saldo</th><th>Aksi</th></tr></thead><tbody>
              {members.map((member) => <tr key={member.id}>
                <td><strong>{member.nama_lengkap}</strong><br /><span className="muted">@{member.username} · {member.alamat || "Alamat belum diisi"}</span></td>
                <td>{member.nomor_telepon || "—"}</td><td className="text-green">{formatRupiah(member.saldo)}</td>
                <td><div className="table-actions"><Link className="button button-small button-secondary" href={`/admin/nasabah?edit=${member.id}`}>Ubah</Link>
                  <form action={deleteMember}><input type="hidden" name="id" value={member.id} /><button className="button button-small button-danger" type="submit">Hapus</button></form></div></td>
              </tr>)}
            </tbody></table></div>
          )}
        </section>
      </div>
    </>
  );
}
