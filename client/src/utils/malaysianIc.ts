export type MalaysianIcBirthDate = {
  day: number;
  month: number;
  year: number;
  age: number;
};

/**
 * Parse YYMMDD from a Malaysian IC (MyKad) number.
 * Century rule: YY > current year's last 2 digits → 19YY, otherwise 20YY.
 */
export function parseMalaysianIcBirthDate(
  ic: string,
  referenceDate: Date = new Date(),
): MalaysianIcBirthDate | null {
  const digits = ic.replace(/\D/g, "");
  if (digits.length < 6) return null;

  const yy = Number(digits.slice(0, 2));
  const month = Number(digits.slice(2, 4));
  const day = Number(digits.slice(4, 6));

  if (month < 1 || month > 12 || day < 1 || day > 31) return null;

  const currentYy = referenceDate.getFullYear() % 100;
  const year = yy > currentYy ? 1900 + yy : 2000 + yy;

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

  return { day, month, year, age: Math.max(age, 0) };
}
