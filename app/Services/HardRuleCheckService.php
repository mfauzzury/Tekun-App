<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\SpptDataset;

class HardRuleCheckService
{
    private const MODULE = 'setup';

    private const DATASET_KEY = 'saringan_auto_kelayakan';

    /**
     * @return array{active: bool, rules: list<array<string, mixed>>}
     */
    public function resolveConfig(): array
    {
        $dataset = SpptDataset::query()
            ->where('module', self::MODULE)
            ->where('dataset_key', self::DATASET_KEY)
            ->first();

        if ($dataset && is_array($dataset->payload) && isset($dataset->payload['rules'])) {
            return $this->normalizeConfig($dataset->payload);
        }

        return $this->normalizeConfig(
            config('sppt-setup.hard_rules_defaults.'.self::DATASET_KEY, ['active' => true, 'rules' => []])
        );
    }

    /**
     * Public-safe summary (no blacklist IC values).
     *
     * @return array{active: bool, rules: list<array<string, mixed>>}
     */
    public function publicSummary(): array
    {
        $config = $this->resolveConfig();

        $rules = collect($config['rules'])
            ->filter(fn (array $rule) => $rule['active'] ?? true)
            ->sortBy(fn (array $rule) => $rule['sort'] ?? 0)
            ->values()
            ->map(function (array $rule) {
                $summary = [
                    'code' => $rule['code'],
                    'label' => $rule['label'],
                    'hint' => $this->ruleHint($rule),
                ];

                return $summary;
            })
            ->all();

        return [
            'active' => $config['active'],
            'rules' => $rules,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{eligible: bool, autoReject: bool, reasons: list<string>, failedRules: list<string>}
     */
    public function evaluate(array $input): array
    {
        $config = $this->resolveConfig();

        if (! ($config['active'] ?? true)) {
            return [
                'eligible' => true,
                'autoReject' => false,
                'reasons' => [],
                'failedRules' => [],
            ];
        }

        $reasons = [];
        $failedRules = [];

        foreach ($config['rules'] as $rule) {
            if (! ($rule['active'] ?? true)) {
                continue;
            }

            $violation = $this->checkRule($rule, $input);
            if ($violation !== null) {
                $reasons[] = $violation;
                $failedRules[] = (string) ($rule['code'] ?? 'unknown');
            }
        }

        return [
            'eligible' => $reasons === [],
            'autoReject' => $reasons !== [],
            'reasons' => $reasons,
            'failedRules' => $failedRules,
        ];
    }

    /**
     * @param  array{active?: bool, rules?: list<array<string, mixed>>}  $payload
     * @return array{active: bool, rules: list<array<string, mixed>>}
     */
    public function normalizeConfig(array $payload): array
    {
        $rules = collect($payload['rules'] ?? [])
            ->map(function (array $rule, int $index) {
                return [
                    'code' => (string) ($rule['code'] ?? ''),
                    'label' => (string) ($rule['label'] ?? ''),
                    'active' => (bool) ($rule['active'] ?? true),
                    'sort' => (int) ($rule['sort'] ?? ($index + 1)),
                    'config' => is_array($rule['config'] ?? null) ? $rule['config'] : [],
                ];
            })
            ->filter(fn (array $rule) => $rule['code'] !== '')
            ->sortBy('sort')
            ->values()
            ->all();

        return [
            'active' => (bool) ($payload['active'] ?? true),
            'rules' => $rules,
        ];
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function checkRule(array $rule, array $input): ?string
    {
        $code = $rule['code'] ?? '';
        $config = is_array($rule['config'] ?? null) ? $rule['config'] : [];

        return match ($code) {
            'age_limit' => $this->checkAgeLimit($config, $input),
            'blacklist' => $this->checkBlacklist($config, $input),
            'commitment_ratio' => $this->checkCommitmentRatio($config, $input),
            'active_financing_limit' => $this->checkActiveFinancingLimit($config, $input),
            'bankruptcy' => $this->checkBankruptcy($input),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $input
     */
    private function checkAgeLimit(array $config, array $input): ?string
    {
        $umur = isset($input['umur']) ? (int) $input['umur'] : null;
        if ($umur === null) {
            return null;
        }

        $minAge = (int) ($config['min_age'] ?? 18);
        $maxAge = (int) ($config['max_age'] ?? 65);

        if ($umur < $minAge || $umur > $maxAge) {
            return "Umur mesti di antara {$minAge} hingga {$maxAge} tahun.";
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $input
     */
    private function checkBlacklist(array $config, array $input): ?string
    {
        $noKp = $this->normalizeIc((string) ($input['no_kp'] ?? $input['noKp'] ?? ''));
        if ($noKp === '') {
            return null;
        }

        $ics = collect($config['ics'] ?? [])
            ->map(fn ($ic) => $this->normalizeIc((string) $ic))
            ->filter()
            ->values()
            ->all();

        if (in_array($noKp, $ics, true)) {
            return 'No. Kad Pengenalan disenaraikan dalam senarai hitam (blacklist).';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $input
     */
    private function checkCommitmentRatio(array $config, array $input): ?string
    {
        $pendapatan = (float) ($input['pendapatan_bulanan'] ?? $input['pendapatanBulanan'] ?? 0);
        $komitmen = (float) ($input['jumlah_komitmen_sedia_ada'] ?? $input['jumlahKomitmenSediaAda'] ?? 0);
        $maxRatio = (float) ($config['max_ratio'] ?? 0.7);

        if ($pendapatan <= 0) {
            if ($komitmen > 0) {
                return 'Komitmen kewangan sedia ada melebihi had maksimum berbanding pendapatan.';
            }

            return null;
        }

        $ratio = $komitmen / $pendapatan;
        if ($ratio > $maxRatio) {
            return 'Komitmen kewangan sedia ada melebihi had maksimum berbanding pendapatan.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $input
     */
    private function checkActiveFinancingLimit(array $config, array $input): ?string
    {
        $maxCount = (int) ($config['max_active_count'] ?? 1);
        $maxTotal = (float) ($config['max_total_amount'] ?? 300000);

        $activeCount = isset($input['jumlah_pembiayaan_aktif'])
            ? (int) $input['jumlah_pembiayaan_aktif']
            : (isset($input['jumlahPembiayaanAktif']) ? (int) $input['jumlahPembiayaanAktif'] : null);

        $activeTotal = isset($input['jumlah_pembiayaan_aktif_rm'])
            ? (float) $input['jumlah_pembiayaan_aktif_rm']
            : (isset($input['jumlahPembiayaanAktifRm']) ? (float) $input['jumlahPembiayaanAktifRm'] : null);

        $noKp = $this->normalizeIc((string) ($input['no_kp'] ?? $input['noKp'] ?? ''));

        if ($activeCount === null && $activeTotal === null && $noKp !== '') {
            $activeAccounts = AkaunPembiayaan::query()
                ->where('ic', $noKp)
                ->whereRaw('LOWER(status) IN (?, ?)', ['aktif', 'tunggakan'])
                ->get();

            $activeCount = $activeAccounts->count();
            $activeTotal = (float) $activeAccounts->sum('jumlah_pembiayaan');
        }

        if ($activeCount !== null && $activeCount > $maxCount) {
            return "Bilangan pembiayaan aktif ({$activeCount}) melebihi had maksimum ({$maxCount}).";
        }

        if ($activeTotal !== null && $activeTotal > $maxTotal) {
            $formatted = number_format($maxTotal, 0, '.', ',');

            return 'Jumlah pembiayaan aktif (RM '.number_format($activeTotal, 0, '.', ',').") melebihi had maksimum (RM {$formatted}).";
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function checkBankruptcy(array $input): ?string
    {
        $muflis = $input['muflis'] ?? $input['isBankrupt'] ?? null;
        if ($muflis === true || $muflis === 1 || $muflis === '1') {
            return 'Pemohon disenaraikan sebagai muflis / insolvensi.';
        }

        return null;
    }

    private function normalizeIc(string $ic): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', trim($ic)) ?? '');
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function ruleHint(array $rule): string
    {
        $config = is_array($rule['config'] ?? null) ? $rule['config'] : [];

        return match ($rule['code'] ?? '') {
            'age_limit' => sprintf(
                '%d–%d tahun',
                (int) ($config['min_age'] ?? 18),
                (int) ($config['max_age'] ?? 65)
            ),
            'blacklist' => 'No. KP dalam senarai hitam',
            'commitment_ratio' => sprintf(
                'Maks. %.0f%% berbanding pendapatan',
                ((float) ($config['max_ratio'] ?? 0.7)) * 100
            ),
            'active_financing_limit' => sprintf(
                'Maks. %d akaun / RM %s',
                (int) ($config['max_active_count'] ?? 1),
                number_format((float) ($config['max_total_amount'] ?? 300000), 0, '.', ',')
            ),
            'bankruptcy' => 'Status muflis / insolvensi',
            default => '',
        };
    }
}
