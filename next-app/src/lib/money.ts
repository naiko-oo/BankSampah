const MAX_AMOUNT_CENTS = 999_999_999_999n;
const MAX_PRICE_CENTS = 9_999_999_999n;
const MAX_WEIGHT_CENTS = 99_999_999n;

function parseCents(value: string): bigint | null {
  const normalized = value.trim();
  const match = /^(\d+)(?:\.(\d{1,2}))?$/.exec(normalized);

  if (!match) {
    return null;
  }

  return BigInt(match[1]) * 100n + BigInt((match[2] ?? "").padEnd(2, "0"));
}

export function parseStoredCents(value: string): bigint | null {
  return parseCents(value);
}

export function parseAmountCents(value: string, maximum = MAX_AMOUNT_CENTS): bigint | null {
  const cents = parseCents(value);
  return cents !== null && cents > 0n && cents <= maximum ? cents : null;
}

export function parseWeightCents(value: string): bigint | null {
  const cents = parseCents(value);
  return cents !== null && cents > 0n && cents <= MAX_WEIGHT_CENTS ? cents : null;
}

export function depositSubtotalCents(weightCents: bigint, priceCents: bigint): bigint {
  return (weightCents * priceCents + 50n) / 100n;
}

export function centsToDecimal(cents: bigint): string {
  return `${cents / 100n}.${(cents % 100n).toString().padStart(2, "0")}`;
}

export const MAX_TRANSACTION_CENTS = MAX_AMOUNT_CENTS;
export const MAX_CATEGORY_PRICE_CENTS = MAX_PRICE_CENTS;
