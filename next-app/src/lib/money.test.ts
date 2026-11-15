import { describe, expect, it } from "vitest";
import {
  centsToDecimal,
  depositSubtotalCents,
  MAX_CATEGORY_PRICE_CENTS,
  MAX_TRANSACTION_CENTS,
  parseAmountCents,
  parseWeightCents,
} from "./money";

describe("aturan nominal bank sampah", () => {
  it("mengubah nominal positif menjadi sen tanpa kehilangan presisi", () => {
    expect(parseAmountCents("3500")).toBe(350_000n);
    expect(parseAmountCents("125.40")).toBe(12_540n);
    expect(centsToDecimal(12_540n)).toBe("125.40");
  });

  it("menolak nominal kosong, negatif, presisi lebih dari dua desimal, dan di luar batas", () => {
    expect(parseAmountCents("0")).toBeNull();
    expect(parseAmountCents("-1")).toBeNull();
    expect(parseAmountCents("1.234")).toBeNull();
    expect(parseAmountCents("10000000000")).toBeNull();
    expect(parseAmountCents("9999999999.99")).toBe(MAX_TRANSACTION_CENTS);
  });

  it("membatasi berat setoran sampai 999.999,99 kg", () => {
    expect(parseWeightCents("0.01")).toBe(1n);
    expect(parseWeightCents("999999.99")).toBe(99_999_999n);
    expect(parseWeightCents("0")).toBeNull();
    expect(parseWeightCents("1000000")).toBeNull();
  });

  it("menghitung subtotal dan membulatkan tepat ke dua desimal", () => {
    const weight = parseWeightCents("1.25")!;
    const price = parseAmountCents("3500", MAX_CATEGORY_PRICE_CENTS)!;
    expect(centsToDecimal(depositSubtotalCents(weight, price))).toBe("4375.00");

    const halfCentWeight = parseWeightCents("0.01")!;
    const halfCentPrice = parseAmountCents("0.50", MAX_CATEGORY_PRICE_CENTS)!;
    expect(centsToDecimal(depositSubtotalCents(halfCentWeight, halfCentPrice))).toBe("0.01");
  });
});
