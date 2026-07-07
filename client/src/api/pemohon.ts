import { apiRequest } from "./client";
import type { HardRuleCheckInput, HardRuleCheckResult, HardRulePublicSummary, AiRiskScoringInput, AiRiskScoringResult } from "@/types";

export async function fetchHardRulesSummary() {
  return apiRequest<{ data: HardRulePublicSummary }>("/api/public/sppt/hard-rules");
}

export async function runHardRuleCheck(input: HardRuleCheckInput) {
  return apiRequest<{ data: HardRuleCheckResult }>("/api/public/sppt/hard-rules/check", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function runAiRiskScoring(input: AiRiskScoringInput) {
  return apiRequest<{ data: AiRiskScoringResult }>("/api/public/sppt/risk-scoring/score", {
    method: "POST",
    body: JSON.stringify(input),
  });
}
