<?php

namespace App\Services;

use App\Models\Permohonan;

/**
 * Spec 2.1.1 / 2.6 — AI Credit Scoring with APK enrichment and decision engine routing.
 */
class AiCreditScoringService
{
    public function __construct(
        protected AiRiskScoringService $riskScoring,
        protected ApkCreditEnrichmentService $apkEnrichment,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function score(array $input): array
    {
        $noKp = (string) ($input['no_kp'] ?? $input['noKp'] ?? '');
        $ssmNo = (string) ($input['no_ssm'] ?? $input['noSsm'] ?? '');

        $apk = $this->apkEnrichment->enrich($noKp, $ssmNo !== '' ? $ssmNo : null);
        $riskResult = $this->riskScoring->score($input);

        $creditScore = $this->adjustScoreWithApk((int) $riskResult['risk_score'], $apk);
        $category = $this->categoryFromScore($creditScore);
        $decision = $this->resolveDecision($creditScore, $apk, $input);

        $factors = $riskResult['factors'];
        $factors = array_merge($factors, $this->apkFactors($apk));

        $adverseReasons = $this->adverseActionReasons($creditScore, $apk, $decision);

        return [
            'credit_score' => $creditScore,
            'credit_category' => $category,
            'recommended_limit' => $riskResult['recommended_limit'],
            'risk_band' => $decision['risk_band'],
            'risk_band_color' => $decision['risk_band_color'],
            'recommended_action' => $decision['recommended_action'],
            'decision_label' => $decision['decision_label'],
            'decision_description' => $decision['decision_description'],
            'factors' => array_slice($factors, 0, 8),
            'adverse_action_reasons' => $adverseReasons,
            'apk' => $apk,
            'confidence' => $riskResult['confidence'],
            'source' => $riskResult['source'],
            'scored_at' => now()->toIso8601String(),
            'message' => 'Skor kredit AI (Spec 2.1.1) — sokongan keputusan pegawai, bukan keputusan automatik muktamad.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inputFromPermohonan(Permohonan $permohonan): array
    {
        $base = $this->riskScoring->inputFromPermohonan($permohonan);
        $details = is_array($permohonan->details) ? $permohonan->details : [];

        $base['no_ssm'] = (string) ($details['no_ssm'] ?? $details['no_pendaftaran_syarikat'] ?? '');

        return $base;
    }

    /**
     * @param  array<string, mixed>  $apk
     * @param  array<string, mixed>  $input
     * @return array{
     *     risk_band: string,
     *     risk_band_color: string,
     *     recommended_action: string,
     *     decision_label: string,
     *     decision_description: string
     * }
     */
    private function resolveDecision(int $creditScore, array $apk, array $input): array
    {
        $thresholds = config('sppt.credit_scoring', []);
        $autoApproveMin = (int) ($thresholds['auto_approve_min_score'] ?? 80);
        $officerReviewMin = (int) ($thresholds['officer_review_min_score'] ?? 60);

        $criticalFlags = count($apk['flags'] ?? []) >= 2
            || ((int) ($apk['ccris']['legal_actions'] ?? 0)) > 0
            || ((int) ($apk['ccris']['npf_accounts'] ?? 0)) > 0
            || (bool) ($input['muflis'] ?? false);

        if ($criticalFlags || $creditScore < $officerReviewMin) {
            return [
                'risk_band' => 'high',
                'risk_band_color' => 'red',
                'recommended_action' => 'reject_escalate',
                'decision_label' => 'Tolak / Eskalasi',
                'decision_description' => 'Risiko tinggi — permohonan perlu ditolak atau di eskalasi kepada pegawai kanan.',
            ];
        }

        if ($creditScore >= $autoApproveMin) {
            return [
                'risk_band' => 'low',
                'risk_band_color' => 'green',
                'recommended_action' => 'auto_approve',
                'decision_label' => 'Auto-Lulus (Cadangan)',
                'decision_description' => 'Risiko rendah — sistem mencadangkan kelulusan automatik tertakluk semakan pegawai.',
            ];
        }

        return [
            'risk_band' => 'medium',
            'risk_band_color' => 'amber',
            'recommended_action' => 'officer_review',
            'decision_label' => 'Semakan Pegawai',
            'decision_description' => 'Risiko sederhana — permohonan dirujuk untuk semakan dan keputusan pegawai.',
        ];
    }

    /**
     * @param  array<string, mixed>  $apk
     */
    private function adjustScoreWithApk(int $baseScore, array $apk): int
    {
        $score = $baseScore;
        $ccris = is_array($apk['ccris'] ?? null) ? $apk['ccris'] : [];
        $ctos = is_array($apk['ctos'] ?? null) ? $apk['ctos'] : [];
        $experian = is_array($apk['experian'] ?? null) ? $apk['experian'] : [];

        $ccrisScore = (int) ($ccris['score'] ?? 650);
        if ($ccrisScore >= 700) {
            $score += 5;
        } elseif ($ccrisScore < 550) {
            $score -= 12;
        } elseif ($ccrisScore < 600) {
            $score -= 6;
        }

        if ((int) ($ccris['legal_actions'] ?? 0) > 0) {
            $score -= 20;
        }
        if ((int) ($ccris['npf_accounts'] ?? 0) > 0) {
            $score -= 15;
        }
        if ((bool) ($ccris['special_attention'] ?? false)) {
            $score -= 8;
        }
        if ((bool) ($ctos['litigation'] ?? false)) {
            $score -= 10;
        }
        if ((bool) ($experian['delinquency'] ?? false)) {
            $score -= 8;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $apk
     * @return list<array{factor: string, impact: string, description: string}>
     */
    private function apkFactors(array $apk): array
    {
        $factors = [];
        $ccris = is_array($apk['ccris'] ?? null) ? $apk['ccris'] : [];

        if (isset($ccris['score'])) {
            $factors[] = [
                'factor' => 'Skor CCRIS',
                'impact' => (int) $ccris['score'] >= 650 ? 'positif' : 'negatif',
                'description' => 'Skor CCRIS: '.(int) $ccris['score'].' / 850.',
            ];
        }

        $ctos = is_array($apk['ctos'] ?? null) ? $apk['ctos'] : [];
        if (isset($ctos['score'])) {
            $factors[] = [
                'factor' => 'Skor CTOS',
                'impact' => (int) $ctos['score'] >= 650 ? 'positif' : 'negatif',
                'description' => 'Skor CTOS: '.(int) $ctos['score'].' / 850.',
            ];
        }

        foreach ($apk['flags'] ?? [] as $flag) {
            $factors[] = [
                'factor' => 'APK',
                'impact' => 'negatif',
                'description' => (string) $flag,
            ];
        }

        return $factors;
    }

    /**
     * @param  array<string, mixed>  $apk
     * @param  array{recommended_action: string}  $decision
     * @return list<string>
     */
    private function adverseActionReasons(int $creditScore, array $apk, array $decision): array
    {
        if ($decision['recommended_action'] === 'auto_approve') {
            return [];
        }

        $reasons = [];
        foreach ($apk['flags'] ?? [] as $flag) {
            $reasons[] = (string) $flag;
        }

        if ($creditScore < 60) {
            $reasons[] = 'Skor kredit AI di bawah ambang minimum (60/100).';
        }

        return array_values(array_unique($reasons));
    }

    private function categoryFromScore(int $score): string
    {
        if ($score >= 80) {
            return 'Risiko Rendah';
        }
        if ($score >= 60) {
            return 'Risiko Sederhana';
        }

        return 'Risiko Tinggi';
    }
}
