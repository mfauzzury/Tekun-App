<?php

namespace App\Services;

/**
 * POC test case K4 — credit bureau API (CCRIS/CTOS) simulator that the panel can disconnect.
 *
 * When connected it returns the mock bureau report (ApkCreditEnrichmentService); when disconnected
 * it fails like an unreachable API, and the pipeline continues on alternative data only.
 */
class CreditBureauSimulatorService
{
    public const SETTING_KEY = 'spptBiroKreditStatus';

    public function __construct(
        protected SettingService $settings,
        protected ApkCreditEnrichmentService $bureau,
    ) {}

    public function connected(): bool
    {
        return $this->settings->get(self::SETTING_KEY, 'sambung') !== 'putus';
    }

    public function setConnected(bool $connected): void
    {
        $this->settings->set(self::SETTING_KEY, $connected ? 'sambung' : 'putus');
    }

    /**
     * @param  array<string, mixed>  $details  application details (no_ic_baru, data_alternatif)
     * @return array{status: 'tersambung'|'tiada_rekod'|'terputus', sumber: string, mesej: string, latency_ms: int, laporan: ?array<string, mixed>}
     */
    public function query(array $details): array
    {
        $started = microtime(true);
        $sumber = 'Simulator biro kredit (CCRIS / CTOS)';

        if (! $this->connected()) {
            usleep(300_000); // a failed connection attempt still costs time

            return [
                'status' => 'terputus',
                'sumber' => $sumber,
                'mesej' => 'Sambungan API biro kredit terputus. Penilaian diteruskan dengan data alternatif sahaja.',
                'latency_ms' => $this->elapsed($started),
                'laporan' => null,
            ];
        }

        // The applicant declared no CCRIS record (thin file): the bureau has nothing to return.
        if (($details['data_alternatif']['ccris_tersedia'] ?? null) === false) {
            return [
                'status' => 'tiada_rekod',
                'sumber' => $sumber,
                'mesej' => 'Tiada rekod CCRIS / CTOS untuk pemohon ini — dinilai dengan data alternatif.',
                'latency_ms' => $this->elapsed($started),
                'laporan' => null,
            ];
        }

        $report = $this->bureau->enrich((string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? ''));

        return [
            'status' => 'tersambung',
            'sumber' => $sumber,
            'mesej' => 'Laporan biro kredit diterima (CCRIS '.($report['ccris']['score'] ?? '-').', CTOS '.($report['ctos']['score'] ?? '-').').',
            'latency_ms' => $this->elapsed($started),
            'laporan' => $report,
        ];
    }

    protected function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
