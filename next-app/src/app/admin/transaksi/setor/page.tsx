import Link from "next/link";
import { requireRole } from "@/lib/auth";
import { query } from "@/lib/db";
import type { QueryResultRow } from "pg";
import DepositForm from "../DepositForm";

interface MemberOption extends QueryResultRow {
  id: number;
  nama_lengkap: string;
  username: string;
}

interface CategoryOption extends QueryResultRow {
  id: number;
  nama_kategori: string;
  harga_per_kg: string;
}

interface DepositPageProps {
  searchParams: Promise<{ error?: string }>;
}

export default async function DepositPage({ searchParams }: DepositPageProps) {
  await requireRole("admin");
  const [{ error }, members, categories] = await Promise.all([
    searchParams,
    query<MemberOption>("SELECT id, nama_lengkap, username FROM users WHERE role = 'nasabah' ORDER BY nama_lengkap"),
    query<CategoryOption>("SELECT id, nama_kategori, harga_per_kg FROM categories ORDER BY nama_kategori"),
  ]);

  return (
    <>
      <header className="page-heading">
        <div><p className="eyebrow">Transaksi baru</p><h1>Catat setoran</h1><p className="muted">Nilai setoran dihitung dari berat dan tarif kategori yang berlaku.</p></div>
        <Link className="button button-secondary" href="/admin/transaksi">← Transaksi</Link>
      </header>
      <DepositForm members={members} categories={categories} error={error} />
    </>
  );
}
