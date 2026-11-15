import "server-only";

import { redirect } from "next/navigation";
import { getSession, type SessionUser, type UserRole } from "@/lib/session";

export async function requireUser(): Promise<SessionUser> {
  const session = await getSession();

  if (!session.user) {
    redirect("/login");
  }

  return session.user;
}

export async function requireRole(role: UserRole): Promise<SessionUser> {
  const user = await requireUser();

  if (user.role !== role) {
    redirect(user.role === "admin" ? "/admin" : "/nasabah");
  }

  return user;
}
