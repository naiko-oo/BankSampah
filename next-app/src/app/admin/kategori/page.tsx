import Link from "next/link";
import { deleteCategory, saveCategory } from "@/app/actions/admin";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import { formatRupiah } from "@/lib/format";
import type { RowDataPacket } from "mysql2";

interface Category extends RowDataPacket {
  id: number;
  nama_kategori: string;
  harga_per_kg: string;
  deskripsi: string | null;
}

interface CategoryPageProps {
  searchParams: Promise<{ edit?: string; error?: string; saved?: string }>;
}

export default async function CategoriesPage({ searchParams }: CategoryPageProps) {
  await requireRole("admin");
  const { edit, error, saved } = await searchParams;
  const categories = await query<Category>(
    "SELECT id, nama_kategori, harga_per_kg, deskripsi FROM categories ORDER BY nama_kategori",
  );
  const editingId = Number(edit);
  const editing = Number.isSafeInteger(editingId) && editingId > 0
    ? categories.find((category) => category.id === editingId)
    : undefined;

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Pengaturan data</p><h1>Kategori sampah</h1><p className="muted">Atur jenis sampah dan tarif yang digunakan untuk menghitung setoran.</p></div>
        <Link className="button button-secondary" href="/admin">← Dashboard</Link>
      </header>
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      {saved && <div className="alert alert-success" role="status">Perubahan kategori berhasil disimpan.</div>}
      <div className="content-grid">
        <section className="panel form-panel">
          <div className="panel-heading"><div><h2>{editing ? "Ubah kategori" : "Tambah kategori"}</h2><p className="muted">Nama dan tarif wajib diisi.</p></div></div>
          <form action={saveCategory} className="form-stack">
            <input type="hidden" name="id" value={editing?.id ?? ""} />
            <label>Nama kategori<input name="nama_kategori" required maxLength={100} defaultValue={editing?.nama_kategori ?? ""} /></label>
            <label>Tarif per kilogram (Rp)<input name="harga_per_kg" type="number" min="0.01" max="99999999.99" step="0.01" required defaultValue={editing?.harga_per_kg ?? ""} /></label>
            <label>Deskripsi<textarea name="deskripsi" rows={4} maxLength={2000} defaultValue={editing?.deskripsi ?? ""} /></label>
            <div className="form-actions"><button className="button button-primary" type="submit">{editing ? "Simpan perubahan" : "Tambah kategori"}</button>{editing && <Link className="button button-secondary" href="/admin/kategori">Batal</Link>}</div>
          </form>
        </section>
        <section className="panel">
          <div className="panel-heading"><div><h2>Daftar kategori</h2><p className="muted">{categories.length} jenis terdaftar.</p></div></div>
          {categories.length === 0 ? <p className="empty-state">Belum ada kategori sampah.</p> : (
            <div className="table-scroll"><table><thead><tr><th>Kategori</th><th>Tarif / kg</th><th>Aksi</th></tr></thead><tbody>
              {categories.map((category) => <tr key={category.id}>
                <td><strong>{category.nama_kategori}</strong><br /><span className="muted">{category.deskripsi || "Tidak ada deskripsi"}</span></td>
                <td className="text-green">{formatRupiah(category.harga_per_kg)}</td>
                <td><div className="table-actions"><Link className="button button-small button-secondary" href={`/admin/kategori?edit=${category.id}`}>Ubah</Link>
                  <form action={deleteCategory}><input type="hidden" name="id" value={category.id} /><button className="button button-small button-danger" type="submit">Hapus</button></form></div></td>
              </tr>)}
            </tbody></table></div>
          )}
        </section>
      </div>
    </>
  );
}
