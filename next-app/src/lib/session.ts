import "server-only";

import { getIronSession } from "iron-session";
import { cookies } from "next/headers";

export type UserRole = "admin" | "nasabah";

export interface SessionUser {
  id: number;
  username: string;
  namaLengkap: string;
  role: UserRole;
}

export interface SessionData {
  user?: SessionUser;
}

function getSessionPassword(): string {
  const password = process.env.SESSION_PASSWORD;

  if (!password || password.length < 32) {
    throw new Error("SESSION_PASSWORD wajib diatur dan memiliki minimal 32 karakter.");
  }

  return password;
}

export async function getSession() {
  return getIronSession<SessionData>(await cookies(), {
    cookieName: "bank-sampah-session",
    password: getSessionPassword(),
    cookieOptions: {
      httpOnly: true,
      sameSite: "lax",
      secure: process.env.NODE_ENV === "production",
      path: "/",
    },
  });
}
