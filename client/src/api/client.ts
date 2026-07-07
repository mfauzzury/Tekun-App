import { API_BASE_URL } from "@/env";

function getCsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : "";
}

function buildHeaders(init?: HeadersInit) {
  const headers = new Headers(init ?? {});
  if (!headers.has("Content-Type") && !(init instanceof FormData)) {
    headers.set("Content-Type", "application/json");
  }
  const token = getCsrfToken();
  if (token) {
    headers.set("X-XSRF-TOKEN", token);
  }
  return headers;
}

export async function ensureCsrfCookie(): Promise<void> {
  if (!getCsrfToken()) {
    await fetch(`${API_BASE_URL}/sanctum/csrf-cookie`, {
      credentials: "include",
    });
  }
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const isForm = options.body instanceof FormData;
  const headers = isForm ? new Headers(options.headers) : buildHeaders(options.headers);

  // Laravel CamelCaseMiddleware converts incoming keys when the client expects JSON.
  if (!headers.has("Accept")) {
    headers.set("Accept", "application/json");
  }

  // Always include CSRF token, even for FormData uploads
  if (isForm) {
    const token = getCsrfToken();
    if (token) {
      headers.set("X-XSRF-TOKEN", token);
    }
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    credentials: "include",
    headers,
  });

  let payload: Record<string, unknown> = {};
  try {
    payload = await response.json();
  } catch {
    if (response.status === 413) {
      throw new Error(`Fail terlalu besar untuk dimuat naik. Had aplikasi: 10MB setiap fail.`);
    }
    if (!response.ok) {
      throw new Error(
        response.status === 0 || response.status >= 500
          ? "Pelayan tidak memberi respons. AI-OCR borang penuh boleh mengambil 1–5 minit — cuba semula."
          : "Request failed",
      );
    }
  }

  if (!response.ok) {
    const errorPayload = payload?.error as
      | { message?: string; code?: string; details?: Record<string, string[] | string> | null }
      | undefined;
    const error = new Error(errorPayload?.message || "Request failed") as Error & {
      code?: string;
      details?: Record<string, string[] | string> | null;
    };
    error.code = errorPayload?.code;
    error.details = errorPayload?.details ?? null;
    throw error;
  }

  return payload as T;
}
