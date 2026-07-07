import { runHardRuleCheck } from "@/api/pemohon";
import type { HardRuleCheckInput, HardRuleCheckResult } from "@/types";

export type PermohonanHardRuleFormSlice = {
  umur?: string;
  noIcBaru?: string;
  pendapatan?: string;
  pendapatanBulan?: string;
  pendapatanPasangan?: string;
  pendapatanPasanganBulan?: string;
};

export function buildHardRuleInputFromPermohonanForm(form: PermohonanHardRuleFormSlice): HardRuleCheckInput {
  const pendapatan = Number(form.pendapatan) || 0;
  const pendapatanBulan = Math.max(1, Number(form.pendapatanBulan) || 1);
  const pendapatanPasangan = Number(form.pendapatanPasangan) || 0;
  const pendapatanPasanganBulan = Math.max(1, Number(form.pendapatanPasanganBulan) || 1);
  const umur = form.umur?.trim() ? Number(form.umur) : undefined;

  return {
    umur: Number.isFinite(umur) ? umur : undefined,
    noKp: form.noIcBaru?.trim() || undefined,
    pendapatanBulanan: pendapatan / pendapatanBulan + pendapatanPasangan / pendapatanPasanganBulan,
    jumlahKomitmenSediaAda: 0,
    muflis: false,
  };
}

export async function evaluateHardRulesForPermohonanForm(
  form: PermohonanHardRuleFormSlice,
): Promise<HardRuleCheckResult | null> {
  try {
    const res = await runHardRuleCheck(buildHardRuleInputFromPermohonanForm(form));
    return res.data;
  } catch {
    return null;
  }
}
