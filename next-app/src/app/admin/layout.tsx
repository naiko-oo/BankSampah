import Link from "next/link";
import { logout } from "@/app/actions/auth";
import { requireRole } from "@/lib/auth";

export default async function AdminLayout({ children }: LayoutProps<"/admin">) {
  const user = await requireRole("admin");

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <Link className="brand" href="/admin"><span className="brand-icon">♻</span><span>Bank Sampah<small>Panel pengurus</small></span></Link>
        <nav className="side-nav" aria-label="Navigasi pengurus">
          <Link href="/admin">Ringkasan</Link>
          <Link href="/admin/kategori">Kategori sampah</Link>
          <Link href="/admin/nasabah">Data nasabah</Link>
          <Link href="/admin/transaksi">Transaksi</Link>
          <Link href="/admin/laporan">Laporan</Link>
        </nav>
        <div className="sidebar-user">
          <span className="avatar">{user.namaLengkap.slice(0, 1).toUpperCase()}</span>
          <span className="user-copy"><strong>{user.namaLengkap}</strong><small>Administrator</small></span>
          <form action={logout}><button className="icon-button" aria-label="Keluar" title="Keluar">↗</button></form>
        </div>
      </aside>
      <main className="main-content">{children}</main>
    </div>
  );
}
