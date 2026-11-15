"use client";

import { useState } from "react";
import Link from "next/link";
import { recordDeposit } from "@/app/actions/admin";

interface MemberOption {
  id: number;
  nama_lengkap: string;
  username: string;
}

interface CategoryOption {
  id: number;
  nama_kategori: string;
  harga_per_kg: string;
}

export default function DepositForm({
  members,
  categories,
  error,
}: {
  members: MemberOption[];
  categories: CategoryOption[];
  error?: string;
}) {
  const [rows, setRows] = useState([0]);
  const addRow = () => setRows((current) => [...current, Math.max(-1, ...current) + 1]);
  const removeRow = (id: number) => setRows((current) => current.length > 1 ? current.filter((row) => row !== id) : current);

  return (
    <>
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      {members.length === 0 && <div className="alert alert-error">Belum ada akun nasabah. Daftarkan nasabah terlebih dahulu.</div>}
      {categories.length === 0 && <div className="alert alert-error">Belum ada kategori sampah. Tambahkan kategori terlebih dahulu.</div>}
      <form action={recordDeposit} className="panel form-panel">
        <div className="form-grid">
          <label>Nasabah
            <select name="user_id" required defaultValue="">
              <option value="" disabled>Pilih nasabah</option>
              {members.map((member) => <option key={member.id} value={member.id}>{member.nama_lengkap} (@{member.username})</option>)}
            </select>
          </label>
          <label>Catatan (opsional)<input name="catatan" maxLength={2000} /></label>
        </div>
        <div className="panel-heading form-section-heading"><div><h2>Rincian setoran</h2><p className="muted">Berat dan tarif digunakan untuk menghitung total secara otomatis di server.</p></div></div>
        <div className="detail-list">
          {rows.map((rowId, index) => (
            <div className="detail-row" key={rowId}>
              <label>Kategori
                <select name="category_id" required defaultValue="">
                  <option value="" disabled>Pilih kategori</option>
                  {categories.map((category) => <option key={category.id} value={category.id}>{category.nama_kategori} · Rp {Number(category.harga_per_kg).toLocaleString("id-ID")}/kg</option>)}
                </select>
              </label>
              <label>Berat (kg)<input name="berat_kg" type="number" min="0.01" max="999999.99" step="0.01" required /></label>
              {index > 0 && <button className="button button-small button-danger remove-detail" type="button" onClick={() => removeRow(rowId)}>Hapus rincian</button>}
            </div>
          ))}
        </div>
        <div className="form-actions form-bottom">
          <button className="button button-secondary" type="button" onClick={addRow} disabled={rows.length >= 50}>＋ Tambah kategori</button>
          <div className="form-actions"><Link className="button button-secondary" href="/admin/transaksi">Batal</Link><button className="button button-primary" type="submit" disabled={!members.length || !categories.length}>Simpan setoran</button></div>
        </div>
      </form>
    </>
  );
}
