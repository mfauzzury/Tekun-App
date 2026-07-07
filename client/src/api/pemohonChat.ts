import { apiRequest, ensureCsrfCookie } from "./client";

export type PemohonChatRole = "user" | "assistant";

export type PemohonChatTurn = {
  role: PemohonChatRole;
  content: string;
};

export async function sendPemohonChatMessage(message: string, history: PemohonChatTurn[]) {
  await ensureCsrfCookie();
  return apiRequest<{ data: { reply: string } }>("/api/public/pemohon/chat", {
    method: "POST",
    body: JSON.stringify({ message, history }),
  });
}
