import Link from "next/link";
import { logout } from "@/app/actions/auth";
import { requireRole } from "@/lib/auth";

export default async function MemberLayout({ children }: LayoutProps<"/nasabah">) {
  const user = await requireRole("nasabah");

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <Link className="brand" href="/nasabah"><span className="brand-icon">♻</span><span>Bank Sampah<small>Portal nasabah</small></span></Link>
        <nav className="side-nav" aria-label="Navigasi nasabah">
          <Link href="/nasabah">Ringkasan</Link>
          <Link href="/nasabah/riwayat">Riwayat transaksi</Link>
        </nav>
        <div className="sidebar-user">
          <span className="avatar">{user.namaLengkap.slice(0, 1).toUpperCase()}</span>
          <span className="user-copy"><strong>{user.namaLengkap}</strong><small>Nasabah</small></span>
          <form action={logout}><button className="icon-button" aria-label="Keluar" title="Keluar">↗</button></form>
        </div>
      </aside>
      <main className="main-content">{children}</main>
    </div>
  );
}
