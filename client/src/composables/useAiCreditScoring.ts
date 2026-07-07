import type { AiCreditScoringResult } from "@/types";

export function decisionActionClass(action: string): string {
  if (action === "auto_approve") return "bg-emerald-100 text-emerald-800 border-emerald-200";
  if (action === "officer_review") return "bg-amber-100 text-amber-800 border-amber-200";
  return "bg-rose-100 text-rose-800 border-rose-200";
}

export function riskBandClass(color: string): string {
  if (color === "green") return "bg-emerald-500";
  if (color === "amber") return "bg-amber-500";
  return "bg-rose-500";
}

export function creditCategoryClass(category: string): string {
  if (category === "Risiko Rendah") return "bg-emerald-100 text-emerald-700";
  if (category === "Risiko Sederhana") return "bg-amber-100 text-amber-700";
  if (category === "Risiko Tinggi") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-700";
}

export function formatRecommendedLimit(amount: number): string {
  return `RM ${Math.round(amount).toLocaleString("en-MY")}`;
}

export function applyCreditScoreToForm(credit: AiCreditScoringResult): {
  skorKredit: string;
  cadanganPembiayaan: string;
  keputusan: string;
} {
  let keputusan = "";
  if (credit.recommendedAction === "auto_approve") keputusan = "Lulus";
  else if (credit.recommendedAction === "reject_escalate") keputusan = "Tolak";

  return {
    skorKredit: String(credit.creditScore),
    cadanganPembiayaan: formatRecommendedLimit(credit.recommendedLimit),
    keputusan,
  };
}
