import "server-only";

import { Pool, type PoolClient, type QueryResultRow } from "pg";

declare global {
  var bankSampahPool: Pool | undefined;
}

function getPool(): Pool {
  const uri = process.env.DATABASE_URL;

  if (!uri) {
    throw new Error("DATABASE_URL belum diatur. Atur variabel koneksi PostgreSQL sebelum menjalankan aplikasi.");
  }

  if (!globalThis.bankSampahPool) {
    const url = new URL(uri);
    if (url.protocol !== "postgres:" && url.protocol !== "postgresql:") {
      throw new Error("DATABASE_URL harus menggunakan protokol postgresql://.");
    }

    globalThis.bankSampahPool = new Pool({
      connectionString: uri,
      max: 5,
      idleTimeoutMillis: 30_000,
      connectionTimeoutMillis: 10_000,
    });
  }

  return globalThis.bankSampahPool;
}

export async function query<T extends QueryResultRow = QueryResultRow>(
  sql: string,
  values: (string | number | Date | null)[] = [],
): Promise<T[]> {
  const result = await getPool().query<T>(sql, values);
  return result.rows;
}

export async function getConnection(): Promise<PoolClient> {
  return getPool().connect();
}
