<?php

namespace App\Services;

/**
 * POC data enrichment from Agensi Pelaporan Kredit (APK): CCRIS, CTOS, EXPERIAN.
 * Deterministic mock until live APK integration (Spec 10.1.5) is available.
 */
class ApkCreditEnrichmentService
{
    /**
     * @return array{
     *     source: string,
     *     enriched_at: string,
     *     ccris: array<string, mixed>,
     *     ctos: array<string, mixed>,
     *     experian: array<string, mixed>,
     *     flags: list<string>
     * }
     */
    public function enrich(string $noKp, ?string $ssmNo = null): array
    {
        $ic = $this->normalizeIc($noKp);
        $seed = $ic !== '' ? crc32($ic) : crc32('unknown');
        $ssmSeed = $ssmNo !== null && $ssmNo !== '' ? crc32($this->normalizeSsm($ssmNo)) : $seed;

        $ccrisScore = 450 + ($seed % 401);
        $ctosScore = 500 + (($seed >> 3) % 351);
        $experianScore = 480 + (($seed >> 5) % 371);

        $totalFacilities = ($seed % 5);
        $specialAttention = ($seed % 7) === 0;
        $legalActions = ($seed % 11) === 0 ? 1 : (($seed % 13) === 0 ? 2 : 0);
        $npfAccounts = ($seed % 17) === 0 ? 1 : 0;
        $totalOutstanding = round((($seed % 200) + 10) * 1000, 2);
        $monthlyCommitment = round($totalOutstanding * 0.02, 2);

        $ctosLitigation = ($ssmSeed % 19) === 0;
        $experianDelinquency = ($experianScore < 520);

        $flags = [];
        if ($legalActions > 0) {
            $flags[] = 'Tindakan guaman CCRIS';
        }
        if ($npfAccounts > 0) {
            $flags[] = 'Akaun NPF dalam CCRIS';
        }
        if ($specialAttention) {
            $flags[] = 'Akaun perhatian khusus (Special Attention)';
        }
        if ($ctosLitigation) {
            $flags[] = 'Rekod litigasi CTOS';
        }
        if ($experianDelinquency) {
            $flags[] = 'Rekod tunggakan EXPERIAN';
        }
        if ($ccrisScore < 500) {
            $flags[] = 'Skor CCRIS rendah';
        }

        return [
            'source' => 'mock_apk',
            'enriched_at' => now()->toIso8601String(),
            'ccris' => [
                'score' => $ccrisScore,
                'total_facilities' => $totalFacilities,
                'total_outstanding_rm' => $totalOutstanding,
                'monthly_commitment_rm' => $monthlyCommitment,
                'special_attention' => $specialAttention,
                'legal_actions' => $legalActions,
                'npf_accounts' => $npfAccounts,
            ],
            'ctos' => [
                'score' => $ctosScore,
                'litigation' => $ctosLitigation,
                'trade_reference_negative' => ($ssmSeed % 23) === 0,
            ],
            'experian' => [
                'score' => $experianScore,
                'delinquency' => $experianDelinquency,
            ],
            'flags' => $flags,
        ];
    }

    private function normalizeIc(string $ic): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', trim($ic)) ?? '');
    }

    private function normalizeSsm(string $ssm): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', trim($ssm)) ?? '');
    }
}
