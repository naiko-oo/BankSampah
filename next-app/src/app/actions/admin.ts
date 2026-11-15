"use server";

import bcrypt from "bcryptjs";
import { randomBytes } from "node:crypto";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { requireRole } from "@/lib/auth";
import { getConnection, query } from "@/lib/db";
import {
  categoryValidationError,
  depositFormValidationError,
  memberValidationError,
  withdrawalBalanceError,
  withdrawalFormValidationError,
} from "@/lib/business-rules";
import {
  centsToDecimal,
  depositSubtotalCents,
  MAX_CATEGORY_PRICE_CENTS,
  MAX_TRANSACTION_CENTS,
  parseAmountCents,
  parseStoredCents,
  parseWeightCents,
} from "@/lib/money";
import type { QueryResultRow } from "pg";

interface PriceRow extends QueryResultRow {
  harga_per_kg: string;
  nama_kategori: string;
}

interface BalanceRow extends QueryResultRow {
  saldo: string;
}

interface CountRow extends QueryResultRow {
  total: string;
}

interface IdRow extends QueryResultRow {
  id: number;
}

class ActionFailure extends Error {}

function value(formData: FormData, key: string): string {
  return String(formData.get(key) ?? "").trim();
}

function transactionCode(kind: "setor" | "tarik"): string {
  const date = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Jakarta",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date()).replaceAll("-", "");
  const prefix = kind === "setor" ? "TRX-SET" : "TRX-TRK";
  return `${prefix}-${date}-${randomBytes(4).toString("hex").toUpperCase()}`;
}

function withError(path: string, message: string): never {
  redirect(`${path}?error=${encodeURIComponent(message)}`);
}

export async function saveCategory(formData: FormData): Promise<void> {
  await requireRole("admin");
  const id = Number(value(formData, "id")) || 0;
  const name = value(formData, "nama_kategori");
  const price = parseAmountCents(value(formData, "harga_per_kg"), MAX_CATEGORY_PRICE_CENTS);
  const description = value(formData, "deskripsi");

  const validationError = categoryValidationError(name, price, description);
  if (validationError) {
    withError("/admin/kategori", validationError);
  }
  if (price === null) {
    withError("/admin/kategori", "Tarif kategori tidak valid.");
  }

  if (id > 0) {
    await query("UPDATE categories SET nama_kategori = $1, harga_per_kg = $2, deskripsi = $3 WHERE id = $4", [
      name, centsToDecimal(price), description || null, id,
    ]);
  } else {
    await query("INSERT INTO categories (nama_kategori, harga_per_kg, deskripsi) VALUES ($1, $2, $3)", [
      name, centsToDecimal(price), description || null,
    ]);
  }

  revalidatePath("/admin/kategori");
  redirect("/admin/kategori?saved=1");
}

export async function deleteCategory(formData: FormData): Promise<void> {
  await requireRole("admin");
  const id = Number(value(formData, "id"));
  if (!Number.isSafeInteger(id) || id < 1) {
    withError("/admin/kategori", "Kategori yang dipilih tidak valid.");
  }

  const used = await query<CountRow>(
    "SELECT COUNT(*) AS total FROM transaction_details WHERE category_id = $1",
    [id],
  );
  if (Number(used[0]?.total ?? "0") > 0) {
    withError("/admin/kategori", "Kategori sudah dipakai dalam transaksi sehingga tidak dapat dihapus.");
  }

  await query("DELETE FROM categories WHERE id = $1", [id]);
  revalidatePath("/admin/kategori");
  redirect("/admin/kategori?saved=1");
}

export async function saveMember(formData: FormData): Promise<void> {
  await requireRole("admin");
  const id = Number(value(formData, "id")) || 0;
  const name = value(formData, "nama_lengkap");
  const username = value(formData, "username");
  const phone = value(formData, "nomor_telepon");
  const address = value(formData, "alamat");
  const password = String(formData.get("password") ?? "");

  const validationError = memberValidationError({ id, name, username, password, phone, address });
  if (validationError) {
    withError("/admin/nasabah", validationError);
  }

  const existing = await query<IdRow>(
    "SELECT id FROM users WHERE username = $1 AND id <> $2 LIMIT 1",
    [username, id],
  );
  if (existing.length) {
    withError("/admin/nasabah", "Username sudah digunakan akun lain.");
  }

  if (id > 0) {
    const members = await query<IdRow>(
      "SELECT id FROM users WHERE id = $1 AND role = 'nasabah' LIMIT 1",
      [id],
    );
    if (!members.length) {
      withError("/admin/nasabah", "Data nasabah tidak ditemukan.");
    }

    if (password) {
      const passwordHash = await bcrypt.hash(password, 10);
      await query(
        "UPDATE users SET nama_lengkap = $1, username = $2, nomor_telepon = $3, alamat = $4, password = $5 WHERE id = $6 AND role = 'nasabah'",
        [name, username, phone || null, address || null, passwordHash, id],
      );
    } else {
      await query(
        "UPDATE users SET nama_lengkap = $1, username = $2, nomor_telepon = $3, alamat = $4 WHERE id = $5 AND role = 'nasabah'",
        [name, username, phone || null, address || null, id],
      );
    }
  } else {
    const passwordHash = await bcrypt.hash(password, 10);
    await query(
      "INSERT INTO users (username, password, nama_lengkap, nomor_telepon, alamat, role) VALUES ($1, $2, $3, $4, $5, 'nasabah')",
      [username, passwordHash, name, phone || null, address || null],
    );
  }

  revalidatePath("/admin/nasabah");
  redirect("/admin/nasabah?saved=1");
}

export async function deleteMember(formData: FormData): Promise<void> {
  await requireRole("admin");
  const id = Number(value(formData, "id"));
  if (!Number.isSafeInteger(id) || id < 1) {
    withError("/admin/nasabah", "Nasabah yang dipilih tidak valid.");
  }

  const transactions = await query<CountRow>(
    "SELECT COUNT(*) AS total FROM transactions WHERE user_id = $1",
    [id],
  );
  if (Number(transactions[0]?.total ?? "0") > 0) {
    withError("/admin/nasabah", "Nasabah memiliki riwayat transaksi sehingga datanya tidak dapat dihapus.");
  }

  await query("DELETE FROM users WHERE id = $1 AND role = 'nasabah'", [id]);
  revalidatePath("/admin/nasabah");
  redirect("/admin/nasabah?saved=1");
}

export async function recordDeposit(formData: FormData): Promise<void> {
  const admin = await requireRole("admin");
  const path = "/admin/transaksi/setor";
  const userId = Number(value(formData, "user_id"));
  const categoryIds = formData.getAll("category_id").map((item) => String(item));
  const weights = formData.getAll("berat_kg").map((item) => String(item));
  const note = value(formData, "catatan");

  const validationError = depositFormValidationError(userId, categoryIds.length, weights.length, note);
  if (validationError) {
    withError(path, validationError);
  }

  const details = categoryIds.map((categoryId, index) => {
    const id = Number(categoryId);
    const weight = parseWeightCents(weights[index] ?? "");
    if (!Number.isSafeInteger(id) || id < 1 || weight === null) {
      return null;
    }
    return { categoryId: id, weight };
  });
  if (details.some((detail) => detail === null)) {
    withError(path, "Setiap rincian harus memiliki kategori dan berat positif maksimal 999.999,99 kg.");
  }

  const connection = await getConnection();
  let transactionId = 0;
  let failure: string | null = null;
  try {
    await connection.query("BEGIN");
    const { rows: users } = await connection.query<BalanceRow>(
      "SELECT saldo FROM users WHERE id = $1 AND role = 'nasabah' FOR UPDATE",
      [userId],
    );
    if (!users.length) {
      throw new ActionFailure("Nasabah yang dipilih tidak ditemukan.");
    }

    let totalCents = 0n;
    const prepared: { categoryId: number; weight: bigint; price: string; subtotal: bigint }[] = [];
    for (const detail of details) {
      if (!detail) continue;
      const { rows: categories } = await connection.query<PriceRow>(
        "SELECT harga_per_kg, nama_kategori FROM categories WHERE id = $1",
        [detail.categoryId],
      );
      const category = categories[0];
      if (!category) {
        throw new ActionFailure("Salah satu kategori sudah tidak tersedia.");
      }

      const priceCents = parseAmountCents(category.harga_per_kg, MAX_CATEGORY_PRICE_CENTS);
      if (priceCents === null) {
        throw new Error("Tarif kategori tidak valid di database.");
      }
      const subtotal = depositSubtotalCents(detail.weight, priceCents);
      totalCents += subtotal;
      if (subtotal <= 0n || totalCents > MAX_TRANSACTION_CENTS) {
        throw new ActionFailure("Total setoran melebihi batas nominal sistem.");
      }
      prepared.push({
        categoryId: detail.categoryId,
        weight: detail.weight,
        price: centsToDecimal(priceCents),
        subtotal,
      });
    }

    const currentBalance = parseStoredCents(users[0].saldo);
    if (currentBalance === null || currentBalance < 0n) {
      throw new Error("Saldo nasabah tidak valid di database.");
    }
    if (currentBalance + totalCents > MAX_TRANSACTION_CENTS) {
      throw new ActionFailure("Saldo setelah setoran akan melebihi batas nominal sistem.");
    }

    const result = await connection.query<{ id: number }>(
      "INSERT INTO transactions (kode_transaksi, user_id, admin_id, jenis_transaksi, total_nominal, catatan) VALUES ($1, $2, $3, 'setor', $4, $5) RETURNING id",
      [transactionCode("setor"), userId, admin.id, centsToDecimal(totalCents), note || null],
    );
    transactionId = result.rows[0].id;
    for (const detail of prepared) {
      await connection.query(
        "INSERT INTO transaction_details (transaction_id, category_id, berat_kg, harga_per_kg, subtotal) VALUES ($1, $2, $3, $4, $5)",
        [transactionId, detail.categoryId, centsToDecimal(detail.weight), detail.price, centsToDecimal(detail.subtotal)],
      );
    }
    await connection.query("UPDATE users SET saldo = saldo + $1 WHERE id = $2", [centsToDecimal(totalCents), userId]);
    await connection.query("COMMIT");
  } catch (error) {
    try {
      await connection.query("ROLLBACK");
    } catch (rollbackError) {
      console.error("Gagal membatalkan transaksi setoran.", rollbackError);
    }
    if (error instanceof ActionFailure) {
      failure = error.message;
    } else {
      console.error("Gagal menyimpan transaksi setoran.", error);
      failure = "Setoran gagal disimpan. Periksa koneksi database dan coba kembali.";
    }
  } finally {
    connection.release();
  }

  if (failure) {
    withError(path, failure);
  }
  revalidatePath("/admin");
  revalidatePath("/nasabah");
  revalidatePath("/admin/transaksi");
  redirect(`/transaksi/${transactionId}`);
}

export async function recordWithdrawal(formData: FormData): Promise<void> {
  const admin = await requireRole("admin");
  const path = "/admin/transaksi/tarik";
  const userId = Number(value(formData, "user_id"));
  const amount = parseAmountCents(value(formData, "total_nominal"));
  const note = value(formData, "catatan");

  const validationError = withdrawalFormValidationError(userId, amount, note);
  if (validationError) {
    withError(path, validationError);
  }
  if (amount === null) {
    withError(path, "Nominal penarikan tidak valid.");
  }

  const connection = await getConnection();
  let transactionId = 0;
  let failure: string | null = null;
  try {
    await connection.query("BEGIN");
    const { rows: users } = await connection.query<BalanceRow>(
      "SELECT saldo FROM users WHERE id = $1 AND role = 'nasabah' FOR UPDATE",
      [userId],
    );
    const balance = users[0] ? parseStoredCents(users[0].saldo) : null;
    if (balance === null) {
      throw new ActionFailure("Nasabah tidak ditemukan atau saldonya tidak valid.");
    }
    const balanceError = withdrawalBalanceError(amount, balance);
    if (balanceError) {
      throw new ActionFailure(balanceError);
    }

    const result = await connection.query<{ id: number }>(
      "INSERT INTO transactions (kode_transaksi, user_id, admin_id, jenis_transaksi, total_nominal, catatan) VALUES ($1, $2, $3, 'tarik', $4, $5) RETURNING id",
      [transactionCode("tarik"), userId, admin.id, centsToDecimal(amount), note || null],
    );
    transactionId = result.rows[0].id;
    const update = await connection.query(
      "UPDATE users SET saldo = saldo - $1 WHERE id = $2 AND saldo >= $3",
      [centsToDecimal(amount), userId, centsToDecimal(amount)],
    );
    if (update.rowCount !== 1) {
      throw new Error("Saldo berubah atau tidak mencukupi saat transaksi diproses.");
    }
    await connection.query("COMMIT");
  } catch (error) {
    try {
      await connection.query("ROLLBACK");
    } catch (rollbackError) {
      console.error("Gagal membatalkan transaksi penarikan.", rollbackError);
    }
    if (error instanceof ActionFailure) {
      failure = error.message;
    } else {
      console.error("Gagal menyimpan transaksi penarikan.", error);
      failure = "Penarikan gagal disimpan. Periksa saldo dan coba kembali.";
    }
  } finally {
    connection.release();
  }

  if (failure) {
    withError(path, failure);
  }
  revalidatePath("/admin");
  revalidatePath("/nasabah");
  revalidatePath("/admin/transaksi");
  redirect(`/transaksi/${transactionId}`);
}
