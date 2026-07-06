const LEGACY_MASA_BERNIAGA: Record<string, string> = {
  pagi: "08:00",
  petang: "14:00",
  malam: "20:00",
};

/** Normalize legacy masa labels or HH:MM(:SS) to HTML time input value (HH:MM). */
export function toHtmlTimeValue(value: string): string {
  const trimmed = value.trim();
  if (!trimmed) return "";

  const legacy = LEGACY_MASA_BERNIAGA[trimmed.toLowerCase()];
  if (legacy) return legacy;

  const match = trimmed.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
  if (!match) return "";

  const hour = Number(match[1]);
  const minute = Number(match[2]);
  if (hour < 0 || hour > 23 || minute < 0 || minute > 59) return "";

  return `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
}
