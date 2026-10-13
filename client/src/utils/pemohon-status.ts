export type PTone = "neutral" | "brand" | "success" | "warning" | "danger" | "info";

const TONES: Record<string, PTone> = {
  draf: "neutral",
  "dalam semakan": "info",
  dalam_semakan: "info",
  "dalam proses": "info",
  dalam_proses: "info",
  "dalam penilaian": "info",
  dalam_penilaian: "info",
  menunggu: "warning",
  "menunggu dokumen": "warning",
  menunggu_dokumen: "warning",
  temuduga: "warning",
  disemak: "info",
  disokong: "info",
  lengkap: "info",
  diluluskan: "success",
  lulus: "success",
  aktif: "success",
  berjaya: "success",
  selesai: "success",
  dibayar: "success",
  ditolak: "danger",
  gagal: "danger",
  tertunggak: "danger",
  tunggakan: "danger",
  npf: "danger",
  ditutup: "neutral",
};

export function statusTone(status: string | null | undefined): PTone {
  return TONES[(status ?? "").trim().toLowerCase()] ?? "neutral";
}

export const TIMELINE_STAGES = ["Dihantar", "Penilaian AI", "Semakan pegawai", "Keputusan"] as const;

/** Index into TIMELINE_STAGES that the application has reached. */
export function statusStage(status: string | null | undefined, aiDone = false): number {
  const tone = statusTone(status);
  const s = (status ?? "").toLowerCase();
  if (s === "diluluskan" || s === "ditolak" || s === "lulus") return 3;
  if (["disemak", "disokong", "dalam penilaian", "lengkap"].includes(s)) return 2;
  if (aiDone) return 2;
  if (tone === "neutral" && s === "draf") return 0;
  return 1;
}
