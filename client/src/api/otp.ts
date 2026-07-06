import { apiRequest, ensureCsrfCookie } from "./client";

export type OtpChannel = "sms" | "email";

export async function requestOtp(channel: OtpChannel, destination: string) {
  await ensureCsrfCookie();
  const payload = channel === "sms" ? { channel, telefon: destination } : { channel, email: destination };
  return apiRequest<{ data: { sent: boolean } }>("/api/public/otp/request", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function verifyOtp(channel: OtpChannel, destination: string, code: string) {
  await ensureCsrfCookie();
  const payload = channel === "sms" ? { channel, telefon: destination, code } : { channel, email: destination, code };
  return apiRequest<{ data: { verified: boolean } }>("/api/public/otp/verify", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
