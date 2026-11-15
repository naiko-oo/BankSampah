import { describe, expect, it } from "vitest";
import {
  categoryValidationError,
  depositFormValidationError,
  memberValidationError,
  withdrawalBalanceError,
  withdrawalFormValidationError,
} from "./business-rules";
import { MAX_CATEGORY_PRICE_CENTS, parseAmountCents } from "./money";

describe("validasi kategori", () => {
  it("menerima kategori dengan tarif yang valid", () => {
    expect(categoryValidationError("Botol plastik", 350_000n, "")).toBeNull();
    expect(categoryValidationError("Kategori".repeat(12) + "A", MAX_CATEGORY_PRICE_CENTS, "")).toBeNull();
  });

  it("menolak nama, tarif, dan deskripsi yang tidak sesuai batas", () => {
    expect(categoryValidationError("", 100n, "")).toMatch(/Nama kategori/);
    expect(categoryValidationError("a".repeat(101), 100n, "")).toMatch(/Nama kategori/);
    expect(categoryValidationError("Kertas", null, "")).toMatch(/Tarif/);
    expect(categoryValidationError("Kertas", MAX_CATEGORY_PRICE_CENTS + 1n, "")).toMatch(/Tarif/);
    expect(categoryValidationError("Kertas", 100n, "x".repeat(2001))).toMatch(/Deskripsi/);
  });
});

describe("validasi nasabah", () => {
  const member = {
    id: 0,
    name: "Budi Santoso",
    username: "budi",
    password: "password123",
    phone: "",
    address: "",
  };

  it("menerima pembuatan akun dan perubahan tanpa mengganti kata sandi", () => {
    expect(memberValidationError(member)).toBeNull();
    expect(memberValidationError({ ...member, id: 3, password: "" })).toBeNull();
  });

  it("menolak nama atau username kosong dan melebihi batas", () => {
    expect(memberValidationError({ ...member, name: "" })).toMatch(/Nama dan username/);
    expect(memberValidationError({ ...member, name: "n".repeat(101) })).toMatch(/Nama dan username/);
    expect(memberValidationError({ ...member, username: "u".repeat(51) })).toMatch(/Nama dan username/);
  });

  it("mewajibkan kata sandi baru minimal delapan karakter", () => {
    expect(memberValidationError({ ...member, password: "short" })).toMatch(/minimal 8/);
    expect(memberValidationError({ ...member, id: 4, password: "short" })).toMatch(/minimal 8/);
  });

  it("menerapkan batas panjang kata sandi dan informasi kontak", () => {
    expect(memberValidationError({ ...member, password: "p".repeat(73) })).toMatch(/72 karakter/);
    expect(memberValidationError({ ...member, phone: "0".repeat(21) })).toMatch(/Nomor telepon/);
    expect(memberValidationError({ ...member, address: "a".repeat(2001) })).toMatch(/Nomor telepon/);
  });
});

describe("validasi setoran dan penarikan", () => {
  it("menerima rincian setoran valid dan menolak rincian yang tidak cocok", () => {
    expect(depositFormValidationError(2, 1, 1, "")).toBeNull();
    expect(depositFormValidationError(0, 1, 1, "")).toMatch(/Pilih nasabah/);
    expect(depositFormValidationError(2, 0, 0, "")).toMatch(/Tambahkan/);
    expect(depositFormValidationError(2, 2, 1, "")).toMatch(/Tambahkan/);
    expect(depositFormValidationError(2, 51, 51, "")).toMatch(/Tambahkan/);
    expect(depositFormValidationError(2, 1, 1, "x".repeat(2001))).toMatch(/Catatan/);
  });

  it("menerima penarikan valid serta menolak nominal dan nasabah yang tidak valid", () => {
    expect(withdrawalFormValidationError(2, parseAmountCents("100"), "")).toBeNull();
    expect(withdrawalFormValidationError(0, 100n, "")).toMatch(/Pilih nasabah/);
    expect(withdrawalFormValidationError(2, null, "")).toMatch(/Nominal/);
    expect(withdrawalFormValidationError(2, 100n, "x".repeat(2001))).toMatch(/Catatan/);
  });

  it("menolak penarikan yang melebihi saldo atau saldo negatif", () => {
    expect(withdrawalBalanceError(100n, 100n)).toBeNull();
    expect(withdrawalBalanceError(101n, 100n)).toMatch(/tidak mencukupi/);
    expect(withdrawalBalanceError(1n, -1n)).toMatch(/tidak valid/);
  });
});
