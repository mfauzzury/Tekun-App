/** Normalize DD/MM/YYYY or YYYY-MM-DD to HTML date input value (YYYY-MM-DD). */
export function toHtmlDateValue(value: string): string {
  const trimmed = value.trim();
  if (!trimmed) return "";

  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return trimmed;
  }

  const dmY = trimmed.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (dmY) {
    const [, day, month, year] = dmY;
    return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
  }

  return "";
}

export function isoFromBirthParts(day: string, month: string, year: string): string {
  if (!day.trim() || !month.trim() || !year.trim() || year.trim().length !== 4) {
    return "";
  }

  const dayNum = Number(day);
  const monthNum = Number(month);
  const yearNum = Number(year);
  if (dayNum < 1 || dayNum > 31 || monthNum < 1 || monthNum > 12) {
    return "";
  }

  return `${String(yearNum).padStart(4, "0")}-${String(monthNum).padStart(2, "0")}-${String(dayNum).padStart(2, "0")}`;
}

export function birthPartsFromIso(iso: string): { day: string; month: string; year: string } | null {
  const normalized = toHtmlDateValue(iso);
  if (!normalized) return null;

  const [year, month, day] = normalized.split("-");
  return {
    day: String(Number(day)),
    month: String(Number(month)),
    year,
  };
}

export function ageFromIsoDate(iso: string, referenceDate: Date = new Date()): number | null {
  const normalized = toHtmlDateValue(iso);
  if (!normalized) return null;

  const [year, month, day] = normalized.split("-").map(Number);
  const birth = new Date(year, month - 1, day);
  if (
    birth.getFullYear() !== year ||
    birth.getMonth() !== month - 1 ||
    birth.getDate() !== day
  ) {
    return null;
  }

  let age = referenceDate.getFullYear() - year;
  const refMonth = referenceDate.getMonth() + 1;
  const refDay = referenceDate.getDate();
  if (refMonth < month || (refMonth === month && refDay < day)) {
    age -= 1;
  }

  return Math.max(age, 0);
}
