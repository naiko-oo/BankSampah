"use server";

import bcrypt from "bcryptjs";
import type { RowDataPacket } from "mysql2";
import { redirect } from "next/navigation";
import { query } from "@/lib/db";
import { getSession, type SessionUser, type UserRole } from "@/lib/session";

interface LoginRow extends RowDataPacket {
  id: number;
  username: string;
  password: string;
  nama_lengkap: string;
  role: UserRole;
}

export async function login(formData: FormData): Promise<void> {
  const username = String(formData.get("username") ?? "").trim();
  const password = String(formData.get("password") ?? "");

  if (!username || !password || username.length > 50 || password.length > 200) {
    redirect("/login?error=invalid");
  }

  const users = await query<LoginRow>(
    "SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ? LIMIT 1",
    [username],
  );
  const user = users[0];

  if (!user || !(await bcrypt.compare(password, user.password))) {
    redirect("/login?error=invalid");
  }

  const session = await getSession();
  const sessionUser: SessionUser = {
    id: user.id,
    username: user.username,
    namaLengkap: user.nama_lengkap,
    role: user.role,
  };
  session.user = sessionUser;
  await session.save();

  redirect(user.role === "admin" ? "/admin" : "/nasabah");
}

export async function logout(): Promise<void> {
  const session = await getSession();
  session.destroy();
  redirect("/login");
}
