import mysql, { type Pool, type PoolConnection, type RowDataPacket } from "mysql2/promise";

declare global {
  var bankSampahPool: Pool | undefined;
}

function getPool(): Pool {
  const uri = process.env.DATABASE_URL;

  if (!uri) {
    throw new Error("DATABASE_URL belum diatur. Atur variabel koneksi MySQL sebelum menjalankan aplikasi.");
  }

  if (!globalThis.bankSampahPool) {
    const url = new URL(uri);
    if (url.protocol !== "mysql:" && url.protocol !== "mysql2:") {
      throw new Error("DATABASE_URL harus menggunakan protokol mysql://.");
    }

    globalThis.bankSampahPool = mysql.createPool({
      host: url.hostname,
      port: Number(url.port || 3306),
      user: decodeURIComponent(url.username),
      password: decodeURIComponent(url.password),
      database: decodeURIComponent(url.pathname.replace(/^\/+/, "")),
      connectionLimit: 5,
      maxIdle: 5,
      idleTimeout: 60_000,
      waitForConnections: true,
      queueLimit: 0,
      charset: "utf8mb4",
      ssl: url.searchParams.get("ssl") === "true" ? { rejectUnauthorized: true } : undefined,
    });
  }

  return globalThis.bankSampahPool;
}

export async function query<T extends RowDataPacket>(
  sql: string,
  values: (string | number | Date | null)[] = [],
): Promise<T[]> {
  const [rows] = await getPool().execute<T[]>(sql, values);
  return rows;
}

export async function getConnection(): Promise<PoolConnection> {
  return getPool().getConnection();
}
