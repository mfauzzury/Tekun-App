import { runAiRiskScoring } from "@/api/pemohon";
import { runPermohonanAiRiskScoring } from "@/api/sppt";
import type { AiRiskScoringInput, AiRiskScoringResult } from "@/types";

export type PermohonanRiskFormSlice = {
  umur?: string;
  noIcBaru?: string;
  kategoriPembiayaan?: string;
  sektorPerniagaan?: string;
  tempohPerniagaan?: string;
  tempohPerniagaanTahun?: string;
  pendapatan?: string;
  pendapatanBulan?: string;
  pendapatanPasangan?: string;
  pendapatanPasanganBulan?: string;
  jumlahPermohonan?: string;
  komitmenBulanan?: string;
  jumlahKomitmenSediaAda?: string;
  negeri?: string;
};

export function buildAiRiskInputFromPermohonanForm(form: PermohonanRiskFormSlice): AiRiskScoringInput {
  const pendapatan = Number(form.pendapatan) || 0;
  const pendapatanBulan = Math.max(1, Number(form.pendapatanBulan) || 1);
  const pendapatanPasangan = Number(form.pendapatanPasangan) || 0;
  const pendapatanPasanganBulan = Math.max(1, Number(form.pendapatanPasanganBulan) || 1);
  const umur = form.umur?.trim() ? Number(form.umur) : undefined;
  const tempoh = form.tempohPerniagaanTahun?.trim()
    ? Number(form.tempohPerniagaanTahun)
    : form.tempohPerniagaan?.trim()
      ? Number(form.tempohPerniagaan)
      : undefined;

  return {
    umur: Number.isFinite(umur) ? umur : undefined,
    noKp: form.noIcBaru?.trim() || undefined,
    kategoriPembiayaan: form.kategoriPembiayaan?.trim() || undefined,
    sektorPerniagaan: form.sektorPerniagaan?.trim() || undefined,
    tempohPerniagaanTahun: Number.isFinite(tempoh) ? tempoh : undefined,
    pendapatanBulanan: pendapatan / pendapatanBulan + pendapatanPasangan / pendapatanPasanganBulan,
    jumlahKomitmenSediaAda: Number(form.jumlahKomitmenSediaAda || form.komitmenBulanan) || 0,
    jumlahPermohonan: Number(form.jumlahPermohonan) || undefined,
    negeri: form.negeri?.trim() || undefined,
  };
}

export function riskCategoryClass(category: string): string {
  if (category === "Risiko Rendah") return "bg-emerald-100 text-emerald-700";
  if (category === "Risiko Sederhana") return "bg-amber-100 text-amber-700";
  if (category === "Risiko Tinggi") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-700";
}

export function formatRecommendedLimit(amount: number): string {
  return `RM ${Math.round(amount).toLocaleString("en-MY")}`;
}

export async function evaluateAiRiskForPermohonanForm(
  form: PermohonanRiskFormSlice,
  useAdminApi = false,
): Promise<AiRiskScoringResult | null> {
  const input = buildAiRiskInputFromPermohonanForm(form);
  try {
    const res = useAdminApi ? await runPermohonanAiRiskScoring(input) : await runAiRiskScoring(input);
    return res.data;
  } catch {
    return null;
  }
}

export async function evaluateAiRiskFromInput(input: AiRiskScoringInput): Promise<AiRiskScoringResult | null> {
  try {
    const res = await runAiRiskScoring(input);
    return res.data;
  } catch {
    return null;
  }
}
