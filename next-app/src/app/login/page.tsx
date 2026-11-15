import { redirect } from "next/navigation";
import { login } from "@/app/actions/auth";
import { getSession } from "@/lib/session";

interface LoginPageProps {
  searchParams: Promise<{ error?: string }>;
}

export default async function LoginPage({ searchParams }: LoginPageProps) {
  const session = await getSession();
  if (session.user) {
    redirect(session.user.role === "admin" ? "/admin" : "/nasabah");
  }

  const { error } = await searchParams;

  return (
    <main className="login-page">
      <section className="login-card">
        <div className="brand-mark" aria-hidden="true">♻</div>
        <p className="eyebrow">Bank Sampah Digital</p>
        <h1>Selamat datang</h1>
        <p className="muted">Masuk untuk mengelola atau memantau tabungan sampah.</p>
        {error === "invalid" && (
          <div className="alert alert-error" role="alert">
            Username atau kata sandi tidak sesuai.
          </div>
        )}
        <form action={login} className="form-stack">
          <label>
            Username
            <input name="username" autoComplete="username" required maxLength={50} />
          </label>
          <label>
            Kata sandi
            <input name="password" type="password" autoComplete="current-password" required />
          </label>
          <button className="button button-primary button-wide" type="submit">Masuk</button>
        </form>
        <p className="login-footnote">Sistem pencatatan bank sampah lingkungan RT/RW.</p>
      </section>
    </main>
  );
}
