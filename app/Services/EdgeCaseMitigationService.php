<?php

namespace App\Services;

/**
 * Spec Scenario 3 — borderline / gray-zone applicants with structured mitigation suggestions.
 */
class EdgeCaseMitigationService
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array{recommended_action: string}  $decision
     * @param  array<string, mixed>  $apk
     * @return array{
     *     is_borderline: bool,
     *     zone: string|null,
     *     zone_label: string|null,
     *     summary: string|null,
     *     auto_approve_blocked: bool,
     *     mitigations: list<array<string, mixed>>
     * }
     */
    public function analyze(
        array $input,
        int $creditScore,
        array $decision,
        float $recommendedLimit,
        array $apk,
    ): array {
        $thresholds = config('sppt.credit_scoring', []);
        $autoApproveMin = (int) ($thresholds['auto_approve_min_score'] ?? 80);
        $officerReviewMin = (int) ($thresholds['officer_review_min_score'] ?? 60);
        $nearRejectMin = (int) ($thresholds['borderline_near_reject_min'] ?? 50);

        $recommendedAction = (string) ($decision['recommended_action'] ?? '');
        $criticalFlags = $this->hasCriticalFlags($apk, $input);

        $isGrayZone = $recommendedAction === 'officer_review'
            && $creditScore >= $officerReviewMin
            && $creditScore < $autoApproveMin;

        $isNearReject = $recommendedAction === 'reject_escalate'
            && ! $criticalFlags
            && $creditScore >= $nearRejectMin
            && $creditScore < $officerReviewMin;

        if (! $isGrayZone && ! $isNearReject) {
            return [
                'is_borderline' => false,
                'zone' => null,
                'zone_label' => null,
                'summary' => null,
                'auto_approve_blocked' => false,
                'mitigations' => [],
            ];
        }

        $zone = $isNearReject ? 'near_reject' : 'gray';
        $mitigations = $this->buildMitigations($input, $creditScore, $recommendedLimit, $apk, $isNearReject);

        return [
            'is_borderline' => true,
            'zone' => $zone,
            'zone_label' => $isNearReject
                ? 'Kes Sempadan (Hampir Tolak)'
                : 'Zon Kelabu / Kes Sempadan',
            'summary' => $isNearReject
                ? 'Skor berada di hujung bawah julat semakan. Pertimbangkan mitigasi sebelum tolak atau eskalasi — auto-lulus tidak dibenarkan.'
                : 'Skor berada dalam zon kelabu (60–79). Sistem mencadangkan mitigasi di bawah — keputusan muktamad kekal dengan pegawai; auto-lulus disekat.',
            'auto_approve_blocked' => true,
            'mitigations' => $mitigations,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $apk
     * @return list<array<string, mixed>>
     */
    private function buildMitigations(
        array $input,
        int $creditScore,
        float $recommendedLimit,
        array $apk,
        bool $isNearReject,
    ): array {
        $mitigations = [];

        $jumlahPermohonan = max(0.0, (float) ($input['jumlah_permohonan'] ?? 0));
        $pendapatan = max(0.0, (float) ($input['pendapatan_bulanan'] ?? 0));
        $komitmen = max(0.0, (float) ($input['jumlah_komitmen_sedia_ada'] ?? 0));
        $commitmentRatio = $pendapatan > 0 ? $komitmen / $pendapatan : ($komitmen > 0 ? 1.0 : 0.0);

        $suggestedAmount = $this->suggestedReducedAmount($jumlahPermohonan, $recommendedLimit, $creditScore);
        if ($suggestedAmount > 0 && ($jumlahPermohonan <= 0 || $suggestedAmount < $jumlahPermohonan - 1)) {
            $mitigations[] = [
                'code' => 'reduce_amount',
                'title' => 'Kurangkan Jumlah Pembiayaan',
                'description' => sprintf(
                    'Kurangkan jumlah diluluskan kepada %s untuk menurunkan risiko leverage dan nisbah komitmen.',
                    $this->formatRm($suggestedAmount),
                ),
                'suggested_amount' => round($suggestedAmount, 2),
                'suggested_amount_label' => $this->formatRm($suggestedAmount),
                'priority' => $isNearReject || $jumlahPermohonan > $recommendedLimit ? 'high' : 'medium',
            ];
        }

        $needsGuarantor = $commitmentRatio >= 0.4
            || count($apk['flags'] ?? []) > 0
            || $creditScore < 72;

        if ($needsGuarantor) {
            $mitigations[] = [
                'code' => 'additional_guarantor',
                'title' => 'Penjamin Tambahan',
                'description' => $commitmentRatio >= 0.4
                    ? 'Nisbah komitmen melebihi 40% — pertimbangkan penjamin tambahan atau cagaran untuk menampung risiko bayaran.'
                    : 'Rekod APK / risiko sederhana — penjamin tambahan boleh mengukuhkan permohonan sebelum kelulusan.',
                'priority' => $commitmentRatio >= 0.55 ? 'high' : 'medium',
            ];
        }

        $suggestedTenure = $this->suggestedExtendedTenure($pendapatan, $jumlahPermohonan, $suggestedAmount);
        if ($suggestedTenure !== null) {
            $mitigations[] = [
                'code' => 'extend_tenure',
                'title' => 'Lanjutkan Tempoh Ansuran',
                'description' => sprintf(
                    'Lanjutkan tempoh kepada %d bulan untuk mengurangkan beban ansuran bulanan tanpa menolak permohonan.',
                    $suggestedTenure,
                ),
                'suggested_tenure_months' => $suggestedTenure,
                'priority' => 'medium',
            ];
        }

        if ($suggestedAmount > 0 && $suggestedTenure !== null && count($mitigations) >= 2) {
            $mitigations[] = [
                'code' => 'restructure_package',
                'title' => 'Pakej Penstrukturan Semula',
                'description' => sprintf(
                    'Gabungan: jumlah %s + tempoh %d bulan — pilihan seimbang untuk kes sempadan.',
                    $this->formatRm($suggestedAmount),
                    $suggestedTenure,
                ),
                'suggested_amount' => round($suggestedAmount, 2),
                'suggested_amount_label' => $this->formatRm($suggestedAmount),
                'suggested_tenure_months' => $suggestedTenure,
                'priority' => 'high',
            ];
        }

        return $mitigations;
    }

    private function suggestedReducedAmount(float $requested, float $recommendedLimit, int $creditScore): float
    {
        $candidates = [];

        if ($recommendedLimit > 0) {
            $candidates[] = $recommendedLimit;
        }

        if ($requested > 0) {
            $factor = match (true) {
                $creditScore < 65 => 0.65,
                $creditScore < 72 => 0.75,
                default => 0.85,
            };
            $candidates[] = round($requested * $factor, 2);
        }

        if ($candidates === []) {
            return 0.0;
        }

        $amount = min($candidates);

        return max(1000.0, round($amount / 100) * 100);
    }

    private function suggestedExtendedTenure(float $pendapatan, float $requested, float $reducedAmount): ?int
    {
        $options = config('sppt-repayment.formula.tempoh_bulan_options', [12, 24, 36, 48, 60, 84, 120]);
        $defaultTenure = (int) config('sppt-repayment.formula.default_tempoh_bulan', 36);
        $principal = $reducedAmount > 0 ? $reducedAmount : $requested;

        if ($principal < 1000 || $pendapatan <= 0) {
            return null;
        }

        $estimatedInstallment = $principal / max(1, $defaultTenure);
        $installmentRatio = $estimatedInstallment / $pendapatan;

        if ($installmentRatio < 0.25 && $defaultTenure >= 36) {
            return null;
        }

        foreach ($options as $months) {
            if ($months > $defaultTenure) {
                return (int) $months;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $apk
     * @param  array<string, mixed>  $input
     */
    private function hasCriticalFlags(array $apk, array $input): bool
    {
        $ccris = is_array($apk['ccris'] ?? null) ? $apk['ccris'] : [];

        return count($apk['flags'] ?? []) >= 2
            || ((int) ($ccris['legal_actions'] ?? 0)) > 0
            || ((int) ($ccris['npf_accounts'] ?? 0)) > 0
            || (bool) ($input['muflis'] ?? false);
    }

    private function formatRm(float $amount): string
    {
        return 'RM '.number_format($amount, 0, '.', ',');
    }
}
