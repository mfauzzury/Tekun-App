/** Shared form control styling for the applicant portal (PField children). */
export const pInputClass =
  "block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-ink-900 shadow-xs placeholder:text-slate-400 transition-colors focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500";

export const pInputErrorClass = "border-rose-400 focus:border-rose-500 focus:ring-rose-100";

export function formatRinggit(value: number | string | null | undefined): string {
  const n = typeof value === "string" ? Number(value.replace(/[^\d.-]/g, "")) : Number(value ?? 0);
  return `RM ${(Number.isFinite(n) ? n : 0).toLocaleString("ms-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function initials(name: string | null | undefined): string {
  return (name || "P")
    .split(/\s+/)
    .filter((w) => w && !/^(bin|binti|b\.|bt\.?|a\/l|a\/p)$/i.test(w))
    .map((w) => w[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
}
