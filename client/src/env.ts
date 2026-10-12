export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8000";

function parseTruthy(value: unknown): boolean {
  if (typeof value === "boolean") return value;
  if (typeof value !== "string") return false;
  return ["true", "1", "yes"].includes(value.trim().toLowerCase());
}

/** When true, the browser address bar shows only origin (domain + port). */
export const MASK_URL = parseTruthy(import.meta.env.VITE_MASK_URL ?? import.meta.env.MASK_URL);

/** Power BI "Publish to web" embed URL — interactive filters & report parameters enabled in the report. */
export const POWERBI_REPORT_URL =
  import.meta.env.VITE_POWERBI_REPORT_URL ||
  "https://app.powerbi.com/view?r=eyJrIjoiYWI5YTMyZGMtYmU4Mi00MDYyLTllMmQtMzQyMzZmNzA3NGI0IiwidCI6IjQ3ZmRiZWFlLTQ0ZGUtNGQ5Yy1hZWU1LTYyODMzOGZhNzY2YiIsImMiOjEwfQ%3D%3D";
