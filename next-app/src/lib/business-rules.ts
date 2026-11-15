import { MAX_CATEGORY_PRICE_CENTS, MAX_TRANSACTION_CENTS } from "./money";

export function categoryValidationError(
  name: string,
  priceCents: bigint | null,
  description: string,
): string | null {
  if (!name || Array.from(name).length > 100) {
    return "Nama kategori wajib diisi dan maksimal 100 karakter.";
  }
  if (priceCents === null || priceCents > MAX_CATEGORY_PRICE_CENTS) {
    return "Tarif harus lebih besar dari nol dan maksimal Rp 99.999.999,99.";
  }
  if (description.length > 2000) {
    return "Deskripsi maksimal 2.000 karakter.";
  }
  return null;
}

export interface MemberInput {
  id: number;
  name: string;
  username: string;
  password: string;
  phone: string;
  address: string;
}

export function memberValidationError({
  id,
  name,
  username,
  password,
  phone,
  address,
}: MemberInput): string | null {
  if (!name || Array.from(name).length > 100 || !username || Array.from(username).length > 50) {
    return "Nama dan username wajib diisi serta sesuai batas panjang.";
  }
  if ((id === 0 && password.length < 8) || (password !== "" && password.length < 8)) {
    return id === 0 ? "Kata sandi akun baru minimal 8 karakter." : "Kata sandi baru minimal 8 karakter.";
  }
  if (password.length > 72) {
    return "Kata sandi maksimal 72 karakter.";
  }
  if (phone.length > 20 || address.length > 2000) {
    return "Nomor telepon atau alamat melebihi batas panjang.";
  }
  return null;
}

export function depositFormValidationError(
  userId: number,
  categoryCount: number,
  weightCount: number,
  note: string,
): string | null {
  if (!Number.isSafeInteger(userId) || userId < 1) {
    return "Pilih nasabah yang melakukan setoran.";
  }
  if (categoryCount < 1 || categoryCount !== weightCount || categoryCount > 50) {
    return "Tambahkan 1 sampai 50 rincian kategori dan berat.";
  }
  if (note.length > 2000) {
    return "Catatan maksimal 2.000 karakter.";
  }
  return null;
}

export function withdrawalFormValidationError(
  userId: number,
  amountCents: bigint | null,
  note: string,
): string | null {
  if (!Number.isSafeInteger(userId) || userId < 1) {
    return "Pilih nasabah yang melakukan penarikan.";
  }
  if (amountCents === null || amountCents > MAX_TRANSACTION_CENTS) {
    return "Nominal penarikan harus positif dan tidak melebihi Rp 9.999.999.999,99.";
  }
  if (note.length > 2000) {
    return "Catatan maksimal 2.000 karakter.";
  }
  return null;
}

export function withdrawalBalanceError(amountCents: bigint, balanceCents: bigint): string | null {
  if (balanceCents < 0n) {
    return "Saldo nasabah tidak valid.";
  }
  if (amountCents > balanceCents) {
    return "Saldo nasabah tidak mencukupi untuk penarikan tersebut.";
  }
  return null;
}
