import { existsSync } from "node:fs";
import mysql from "mysql2/promise";
import pg from "pg";

process.loadEnvFile(".env.local");
if (existsSync(".env.migration.local")) {
  process.loadEnvFile(".env.migration.local");
}

const sourceUrl = process.env.SOURCE_MYSQL_URL;
const destinationUrl = process.env.DATABASE_URL;

if (!sourceUrl) {
  throw new Error("SOURCE_MYSQL_URL belum diatur di .env.migration.local.");
}
if (!destinationUrl) {
  throw new Error("DATABASE_URL belum diatur di .env.local.");
}

const tables = [
  {
    name: "users",
    columns: ["id", "username", "password", "nama_lengkap", "nomor_telepon", "alamat", "role", "saldo", "created_at"],
  },
  {
    name: "categories",
    columns: ["id", "nama_kategori", "harga_per_kg", "deskripsi", "created_at"],
  },
  {
    name: "transactions",
    columns: ["id", "kode_transaksi", "user_id", "admin_id", "jenis_transaksi", "total_nominal", "catatan", "tanggal_transaksi"],
  },
  {
    name: "transaction_details",
    columns: ["id", "transaction_id", "category_id", "berat_kg", "harga_per_kg", "subtotal"],
  },
];

const source = await mysql.createConnection(sourceUrl);
const destination = new pg.Pool({ connectionString: destinationUrl, max: 1 });

try {
  const counts = [];
  for (const table of tables) {
    const [[sourceCount]] = await source.query(`SELECT COUNT(*) AS total FROM \`${table.name}\``);
    const destinationCount = await destination.query(`SELECT COUNT(*) AS total FROM ${table.name}`);
    if (Number(destinationCount.rows[0].total) !== 0) {
      throw new Error(`Tabel ${table.name} di Supabase tidak kosong. Migrasi dibatalkan agar data tidak tertimpa/terduplikasi.`);
    }
    counts.push(`${table.name}: ${sourceCount.total}`);
  }

  console.log(`Data sumber ditemukan (${counts.join(", ")}). Menyalin data...`);
  const client = await destination.connect();
  try {
    await client.query("BEGIN");
    for (const table of tables) {
      const columnList = table.columns.map((column) => `\`${column}\``).join(", ");
      const [rows] = await source.query(`SELECT ${columnList} FROM \`${table.name}\` ORDER BY id`);
      if (rows.length === 0) continue;

      const targetColumns = table.columns.join(", ");
      const placeholders = table.columns.map((_, index) => `$${index + 1}`).join(", ");
      const insert = `INSERT INTO ${table.name} (${targetColumns}) VALUES (${placeholders})`;

      for (const row of rows) {
        await client.query(insert, table.columns.map((column) => row[column]));
      }

      await client.query(
        `SELECT setval(pg_get_serial_sequence($1, 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM ${table.name}`,
        [table.name],
      );
    }
    await client.query("COMMIT");
  } catch (error) {
    await client.query("ROLLBACK");
    throw error;
  } finally {
    client.release();
  }

  console.log(`Migrasi selesai. Baris sumber: ${counts.join(", ")}.`);
} finally {
  await source.end();
  await destination.end();
}
