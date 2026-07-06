export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";

function parseTruthy(value: unknown): boolean {
  if (typeof value === "boolean") return value;
  if (typeof value !== "string") return false;
  return ["true", "1", "yes"].includes(value.trim().toLowerCase());
}

/** When true, the browser address bar shows only origin (domain + port). */
export const MASK_URL = parseTruthy(import.meta.env.VITE_MASK_URL ?? import.meta.env.MASK_URL);
