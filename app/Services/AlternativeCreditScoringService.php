<?php

namespace App\Services;

use App\Models\Permohonan;
use Throwable;

/**
 * POC C2 — explainable credit scorecard for thin-file micro traders using alternative data
 * (e-wallet, DuitNow QR, utility bills, platform sales) with protected-attribute bias checks.
 *
 * The score is fully deterministic; Claude only narrates the computed factors.
 */
class AlternativeCreditScoringService
{
    public const MODEL_NAME = 'TEKUN-ALT-SCORECARD v1';

    protected const BASE_LOG_ODDS = -0.8;

    protected const MAX_DSR = 0.4;

    /** @var array<string, string> */
    protected const PROTECTED_ATTRIBUTES = [
        'jantina' => 'Jantina',
        'bangsa' => 'Bangsa / Kaum',
        'agama' => 'Agama',
        'negeri' => 'Negeri / Lokasi',
        'umur' => 'Umur / Tarikh Lahir',
    ];

    /** @var array<string, array<string, string>> */
    protected const STATEMENT_FIELDS = [
        'ewallet' => [
            'penyedia' => 'string',
            'purata_masuk_bulanan' => 'amount',
            'bilangan_transaksi_bulanan' => 'count',
            'bulan_aktif' => 'months',
        ],
        'duitnow_qr' => [
            'purata_jualan_bulanan' => 'amount',
            'bilangan_transaksi_bulanan' => 'count',
            'bulan_aktif' => 'months',
            'trend_pertumbuhan_peratus' => 'percent',
        ],
        'bil_utiliti' => [
            'bulan_dibayar_tepat' => 'months',
            'bulan_dinilai' => 'months',
        ],
        'platform' => [
            'nama' => 'string',
            'purata_jualan_bulanan' => 'amount',
            'penarafan' => 'rating',
            'bulan_aktif' => 'months',
        ],
    ];

    public function __construct(
        protected AnthropicClient $anthropic,
        protected PembiayaanFormulaService $formula,
    ) {}

    /**
     * @return array{
     *     skor: int, gred: string, tahap_risiko: string, keyakinan: int, fail_nipis: bool,
     *     sumber_data: list<array{kod: string, label: string, tersedia: bool, nilai_ringkas: string}>,
     *     faktor: list<array{kod: string, label: string, nilai: string, sumbangan: float, arah: string, penerangan: string}>,
     *     skor_asas: int, pendapatan_disahkan_bulanan: float, ansuran_dicadang: float, nisbah_khidmat_hutang: float,
     *     cadangan_had: float, permohonan_melebihi_had: bool, model: array<string, mixed>, bias: array<string, mixed>,
     *     penjelasan_ai: ?string, penjelasan_sumber: string, latency_ms: int, dinilai_pada: string
     * }
     */
    public function score(Permohonan $permohonan, bool $withNarrative = true): array
    {
        $started = microtime(true);
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $jumlah = $this->requestedAmount($permohonan, $details);

        $result = $this->evaluate($details, $jumlah);
        $bias = $this->biasCheck($details, $jumlah, $result['skor'], $permohonan->negeri);

        $narrative = null;
        $source = 'templat';
        if ($withNarrative && $this->anthropic->isConfigured()) {
            $narrative = $this->aiNarrative($result);
            if ($narrative !== null) {
                $source = 'ai';
            }
        }
        $narrative ??= $this->templateNarrative($result);

        return $result + [
            'model' => $this->modelInfo(),
            'bias' => $bias,
            'penjelasan_ai' => $narrative,
            'penjelasan_sumber' => $source,
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'dinilai_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     kumpulan: list<array{atribut: string, nilai: string, bilangan: int, purata_skor: float, kadar_risiko_rendah: float}>,
     *     nisbah_impak_buruk: list<array{atribut: string, kumpulan_terendah: string, kumpulan_tertinggi: string, nisbah: float, lulus_peraturan_4_5: bool, catatan: string}>,
     *     jumlah_permohonan: int, nota: string, dijana_pada: string
     * }
     */
    public function fairnessReport(): array
    {
        $rows = Permohonan::query()
            ->where('status', '!=', 'Draf')
            ->whereNotNull('details')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $lowRiskMin = (int) config('sppt.credit_scoring.auto_approve_min_score', 80);
        $groups = ['jantina' => [], 'bangsa' => [], 'negeri' => []];
        $count = 0;

        foreach ($rows as $row) {
            $details = is_array($row->details) ? $row->details : [];
            if ($details === []) {
                continue;
            }
            $count++;
            $skor = $this->evaluate($details, $this->requestedAmount($row, $details))['skor'];

            $values = [
                'jantina' => $this->genderLabel((string) ($details['jantina'] ?? '')),
                'bangsa' => trim((string) ($details['bangsa'] ?? '')),
                'negeri' => trim((string) ($details['negeri'] ?? $row->negeri ?? '')),
            ];
            foreach ($values as $attr => $value) {
                $value = $value !== '' ? $value : 'Tidak dinyatakan';
                $groups[$attr][$value][] = $skor;
            }
        }

        $kumpulan = [];
        $impact = [];
        foreach ($groups as $attr => $byValue) {
            ksort($byValue);
            $rates = [];
            foreach ($byValue as $value => $scores) {
                $n = count($scores);
                $rate = $n > 0 ? count(array_filter($scores, fn (int $s) => $s >= $lowRiskMin)) / $n : 0.0;
                $kumpulan[] = [
                    'atribut' => $attr,
                    'nilai' => (string) $value,
                    'bilangan' => $n,
                    'purata_skor' => round(array_sum($scores) / max(1, $n), 1),
                    'kadar_risiko_rendah' => round($rate, 3),
                ];
                if ($n >= 5 && $value !== 'Tidak dinyatakan') {
                    $rates[(string) $value] = $rate;
                }
            }
            $impact[] = $this->disparateImpact($attr, $rates);
        }

        $allPass = collect($impact)->every(fn (array $i) => $i['lulus_peraturan_4_5']);

        return [
            'kumpulan' => $kumpulan,
            'nisbah_impak_buruk' => $impact,
            'jumlah_permohonan' => $count,
            'nota' => ($allPass
                ? 'Tiada impak buruk dikesan: nisbah kadar risiko rendah antara kumpulan memenuhi peraturan 4/5 (≥ 0.8).'
                : 'Amaran: sekurang-kurangnya satu atribut gagal peraturan 4/5 — semak ciri model dan data latihan untuk proksi berat sebelah.')
                .' Kumpulan dengan kurang daripada 5 permohonan atau atribut tidak dinyatakan dikecualikan daripada pengiraan nisbah.',
            'dijana_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{ok: bool, jenis: string, data: array<string, mixed>, bulan_dikesan: list<string>, catatan: string, latency_ms: int, message: string}
     */
    public function extractFromStatement(string $absolutePath, string $mime, string $jenis): array
    {
        $fail = fn (string $message, int $latency = 0) => [
            'ok' => false,
            'jenis' => $jenis,
            'data' => [],
            'bulan_dikesan' => [],
            'catatan' => '',
            'latency_ms' => $latency,
            'message' => $message,
        ];

        if (! isset(self::STATEMENT_FIELDS[$jenis])) {
            return $fail('Jenis penyata tidak disokong: '.$jenis.'.');
        }
        if (! $this->anthropic->isConfigured()) {
            return $fail('Penyedia AI tidak dikonfigurasi — sila isi data alternatif secara manual.');
        }
        if (! is_readable($absolutePath)) {
            return $fail('Fail penyata tidak dapat dibaca.');
        }

        $mime = strtolower(trim($mime));
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $isText = str_starts_with($mime, 'text/') || in_array($mime, ['application/csv', 'application/vnd.ms-excel'], true)
            || in_array($extension, ['csv', 'txt', 'tsv'], true);

        $fields = self::STATEMENT_FIELDS[$jenis];
        $schema = json_encode(array_map(fn (string $type) => match ($type) {
            'string' => 'string|null',
            'amount' => 'number (RM purata sebulan)|null',
            'count' => 'integer (purata sebulan)|null',
            'months' => 'integer|null',
            'percent' => 'number (peratus, cth 8.5)|null',
            'rating' => 'number 0-5|null',
        }, $fields), JSON_UNESCAPED_UNICODE);

        $system = 'Anda penganalisis kredit TEKUN. Baca penyata '.$this->sourceLabel($jenis).' dan kira agregat bulanan. '
            .'Jangan reka nilai: gunakan null jika tidak dapat ditentukan daripada dokumen.';
        $prompt = "Kira ringkasan bulanan daripada penyata ini.\n"
            ."Format JSON: {\"data\": {$schema}, \"bulan_dikesan\": [\"YYYY-MM\", ...], \"catatan\": \"ringkasan pendek dalam Bahasa Melayu\"}\n"
            ."- purata = jumlah transaksi masuk/jualan dibahagi bilangan bulan yang dikesan.\n"
            ."- bulan_aktif = bilangan bulan berbeza yang mempunyai transaksi.\n"
            ."- trend_pertumbuhan_peratus = perubahan peratus purata separuh kedua berbanding separuh pertama tempoh.\n"
            ."- bil_utiliti: bulan_dibayar_tepat = bulan dibayar sebelum/pada tarikh akhir; bulan_dinilai = jumlah bulan bil.";

        $files = [];
        try {
            if ($isText) {
                $text = (string) file_get_contents($absolutePath, false, null, 0, 60000);
                $prompt .= "\n\nKandungan penyata (teks/CSV):\n<<<\n".$text."\n>>>";
            } else {
                $files[] = ['path' => $absolutePath, 'mime' => $mime];
            }

            $reply = $this->anthropic->json($system, $prompt, $files, 1500);
        } catch (Throwable $e) {
            return $fail('Analisis AI gagal: '.$e->getMessage());
        }

        $raw = is_array($reply['data']['data'] ?? null) ? $reply['data']['data'] : [];
        $data = [];
        foreach ($fields as $key => $type) {
            $data[$key] = $this->coerce($raw[$key] ?? null, $type);
        }
        if ($jenis === 'bil_utiliti' && $data['bulan_dibayar_tepat'] !== null && $data['bulan_dinilai'] !== null) {
            $data['bulan_dibayar_tepat'] = min($data['bulan_dibayar_tepat'], $data['bulan_dinilai']);
        }

        $months = [];
        foreach ((array) ($reply['data']['bulan_dikesan'] ?? []) as $month) {
            if (is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', trim($month))) {
                $months[] = trim($month);
            }
        }
        $months = array_values(array_unique($months));
        sort($months);

        $found = count(array_filter($data, fn ($v) => $v !== null));

        return [
            'ok' => $found > 0,
            'jenis' => $jenis,
            'data' => $data,
            'bulan_dikesan' => $months,
            'catatan' => (string) ($reply['data']['catatan'] ?? ''),
            'latency_ms' => (int) $reply['latency_ms'],
            'message' => $found > 0
                ? 'Data '.$this->sourceLabel($jenis).' diekstrak ('.count($months).' bulan dikesan). Sila semak sebelum disimpan.'
                : 'Tiada data yang boleh diekstrak daripada penyata.',
        ];
    }

    /**
     * Pure, deterministic scorecard — never calls external services.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function evaluate(array $details, float $jumlah): array
    {
        $alt = is_array($details['data_alternatif'] ?? null) ? $details['data_alternatif'] : [];
        $ewallet = is_array($alt['ewallet'] ?? null) ? $alt['ewallet'] : [];
        $qr = is_array($alt['duitnow_qr'] ?? null) ? $alt['duitnow_qr'] : [];
        $utility = is_array($alt['bil_utiliti'] ?? null) ? $alt['bil_utiliti'] : [];
        $platform = is_array($alt['platform'] ?? null) ? $alt['platform'] : [];

        $ewIncome = max(0.0, (float) ($ewallet['purata_masuk_bulanan'] ?? 0));
        $qrIncome = max(0.0, (float) ($qr['purata_jualan_bulanan'] ?? 0));
        $plIncome = max(0.0, (float) ($platform['purata_jualan_bulanan'] ?? 0));
        $utilAssessed = max(0, (int) ($utility['bulan_dinilai'] ?? 0));
        $utilOnTime = min($utilAssessed, max(0, (int) ($utility['bulan_dibayar_tepat'] ?? 0)));

        $hasEw = $ewIncome > 0;
        $hasQr = $qrIncome > 0;
        $hasPl = $plIncome > 0;
        $hasUtil = $utilAssessed > 0;
        $hasIncomeAlt = $hasEw || $hasQr || $hasPl;
        $hasCcris = (bool) ($alt['ccris_tersedia'] ?? true);
        $hasBank = (bool) ($alt['penyata_bank_tersedia'] ?? false);
        $thinFile = ! $hasCcris && ! $hasBank;

        $verified = max($ewIncome, $qrIncome) + $plIncome;
        $declared = max(0.0, (float) ($details['pendapatan'] ?? 0)) / max(1, (int) ($details['pendapatan_bulan'] ?? 1));
        if ($declared <= 0) {
            $declared = max(0.0, (float) ($details['anggaran_pendapatan'] ?? 0));
        }
        $commitments = max(0.0, (float) ($details['komitmen_bulanan'] ?? $details['jumlah_komitmen_sedia_ada'] ?? 0));
        $tenure = $this->months($details['tempoh_pembiayaan'] ?? null) ?? 36;
        $rate = (float) config('sppt.offer_letter.default_profit_rate', 4);
        $instalment = $jumlah > 0 ? (float) $this->formula->flatRate($jumlah, $rate, $tenure)['ansuran_bulanan'] : 0.0;
        $incomeBase = $verified > 0 ? $verified : $declared;

        $features = [];

        // 1. Verified income level.
        if ($hasIncomeAlt) {
            $w = match (true) {
                $verified >= 4000 => 0.8,
                $verified >= 2500 => 0.6,
                $verified >= 1500 => 0.3,
                $verified >= 800 => 0.0,
                default => -0.5,
            };
            $overlap = $hasEw && $hasQr ? ' (e-wallet & QR bertindih — nilai tertinggi diambil)' : '';
            $features[] = $this->feature('pendapatan_disahkan', 'Pendapatan disahkan (saluran digital)', $this->rm($verified).'/bulan', $w,
                'Purata aliran masuk bulanan yang disahkan melalui saluran digital ialah '.$this->rm($verified).$overlap.'.');
        } elseif ($hasBank && $declared > 0) {
            $w = match (true) {
                $declared >= 4000 => 0.6,
                $declared >= 2500 => 0.4,
                $declared >= 1500 => 0.2,
                $declared >= 800 => 0.0,
                default => -0.5,
            };
            $features[] = $this->feature('pendapatan_disahkan', 'Pendapatan (disokong penyata bank)', $this->rm($declared).'/bulan', $w,
                'Pendapatan diisytihar '.$this->rm($declared).' sebulan disokong oleh penyata bank.');
        } else {
            $features[] = $this->feature('pendapatan_disahkan', 'Pengesahan pendapatan', 'Tidak disahkan', -0.4,
                'Pendapatan diisytihar '.($declared > 0 ? $this->rm($declared).' sebulan ' : '').'tidak dapat disahkan — tiada data e-wallet, DuitNow QR, platform atau penyata bank.');
        }

        // 2. Declared vs verified consistency.
        if ($hasIncomeAlt && $declared > 0) {
            $ratio = $declared / $verified;
            $w = match (true) {
                $ratio <= 1.2 => 0.3,
                $ratio <= 1.5 => 0.0,
                $ratio <= 2.0 => -0.4,
                default => -0.8,
            };
            $features[] = $this->feature('konsistensi_pendapatan', 'Konsistensi pendapatan diisytihar', number_format($ratio, 2).'x disahkan', $w,
                $ratio <= 1.2
                    ? 'Pendapatan diisytihar ('.$this->rm($declared).') selaras dengan aliran masuk digital yang disahkan.'
                    : 'Pendapatan diisytihar ('.$this->rm($declared).') adalah '.number_format($ratio, 1).' kali ganda pendapatan disahkan — perlu pengesahan lanjut.');
        }

        // 3. Months of activity.
        if ($hasIncomeAlt) {
            $active = max((int) ($ewallet['bulan_aktif'] ?? 0), (int) ($qr['bulan_aktif'] ?? 0), (int) ($platform['bulan_aktif'] ?? 0));
            $w = match (true) {
                $active >= 12 => 0.6,
                $active >= 9 => 0.5,
                $active >= 6 => 0.25,
                $active >= 3 => 0.0,
                default => -0.4,
            };
            $features[] = $this->feature('tempoh_aktif', 'Kestabilan aktiviti jualan', $active.' bulan aktif', $w,
                $active >= 6
                    ? 'Jualan direkodkan secara konsisten selama '.$active.' bulan, menunjukkan perniagaan yang stabil.'
                    : 'Sejarah transaksi digital hanya '.$active.' bulan — terlalu singkat untuk menilai kestabilan.');

            // 4. Transaction frequency.
            $tx = max((int) ($ewallet['bilangan_transaksi_bulanan'] ?? 0), (int) ($qr['bilangan_transaksi_bulanan'] ?? 0));
            if ($tx > 0) {
                $w = match (true) {
                    $tx >= 150 => 0.4,
                    $tx >= 60 => 0.25,
                    $tx >= 20 => 0.1,
                    default => -0.2,
                };
                $features[] = $this->feature('kekerapan_transaksi', 'Kekerapan transaksi', $tx.' transaksi/bulan', $w,
                    $tx >= 60
                        ? 'Purata '.$tx.' transaksi sebulan menunjukkan pangkalan pelanggan yang aktif.'
                        : 'Hanya '.$tx.' transaksi sebulan — aliran tunai bergantung kepada sebilangan kecil pelanggan.');
            }
        }

        // 5. Sales growth trend.
        if ($hasQr && isset($qr['trend_pertumbuhan_peratus']) && is_numeric($qr['trend_pertumbuhan_peratus'])) {
            $trend = (float) $qr['trend_pertumbuhan_peratus'];
            $w = match (true) {
                $trend >= 10 => 0.3,
                $trend >= 0 => 0.1,
                $trend >= -10 => -0.1,
                default => -0.4,
            };
            $features[] = $this->feature('trend_jualan', 'Trend jualan DuitNow QR', sprintf('%+.1f%%', $trend), $w,
                $trend >= 0
                    ? 'Jualan QR meningkat '.number_format($trend, 1).'% dalam tempoh dinilai.'
                    : 'Jualan QR menurun '.number_format(abs($trend), 1).'% dalam tempoh dinilai.');
        }

        // 6. Utility payment discipline.
        if ($hasUtil) {
            $ratio = $utilOnTime / $utilAssessed;
            $w = match (true) {
                $ratio >= 0.9 => 0.5,
                $ratio >= 0.75 => 0.2,
                $ratio >= 0.5 => -0.2,
                default => -0.6,
            };
            $features[] = $this->feature('bil_utiliti', 'Disiplin bayaran bil utiliti', $utilOnTime.'/'.$utilAssessed.' bulan tepat masa', $w,
                $ratio >= 0.75
                    ? 'Bil utiliti dibayar tepat masa '.$utilOnTime.' daripada '.$utilAssessed.' bulan — petunjuk disiplin bayaran yang baik.'
                    : 'Bil utiliti lewat dibayar '.($utilAssessed - $utilOnTime).' daripada '.$utilAssessed.' bulan.');
        }

        // 7. Debt-service ratio.
        $obligations = $instalment + $commitments;
        $dsr = $incomeBase > 0 ? $obligations / $incomeBase : ($obligations > 0 ? 1.0 : 0.0);
        $maxAmount = $this->maxFinancing($incomeBase, $commitments, $tenure, $rate);
        $exceeds = $jumlah > 0 && $jumlah > $maxAmount;
        if ($jumlah > 0 || $commitments > 0) {
            $w = match (true) {
                $incomeBase <= 0 => -1.2,
                $dsr <= 0.3 => 0.5,
                $dsr <= 0.4 => 0.2,
                $dsr <= 0.5 => -0.3,
                $dsr <= 0.6 => -0.7,
                default => -1.2,
            };
            $features[] = $this->feature('nisbah_khidmat_hutang', 'Nisbah khidmat hutang (DSR)', $incomeBase > 0 ? round($dsr * 100).'%' : 'Tiada pendapatan', $w,
                $incomeBase <= 0
                    ? 'Tiada pendapatan untuk menampung ansuran '.$this->rm($instalment, 2).' sebulan.'
                    : 'Ansuran dicadang '.$this->rm($instalment, 2).' + komitmen sedia ada '.$this->rm($commitments).' = '.round($dsr * 100).'% daripada pendapatan'
                        .($dsr <= self::MAX_DSR ? ' (dalam had 40%).' : ' (melebihi had 40%; had dicadang '.$this->rm($maxAmount).').'));
        }

        // 8. Business tenure.
        $years = $this->years($details['tempoh_berniaga'] ?? null);
        if ($years !== null) {
            $w = match (true) {
                $years >= 5 => 0.4,
                $years >= 2 => 0.2,
                $years >= 1 => 0.0,
                default => -0.3,
            };
            $features[] = $this->feature('tempoh_berniaga', 'Tempoh berniaga', rtrim(rtrim(number_format($years, 1), '0'), '.').' tahun', $w,
                $years >= 2
                    ? 'Perniagaan telah beroperasi selama '.rtrim(rtrim(number_format($years, 1), '0'), '.').' tahun.'
                    : 'Perniagaan masih baharu (kurang daripada 2 tahun).');
        }

        // 9. Platform rating.
        if ($hasPl && isset($platform['penarafan']) && is_numeric($platform['penarafan'])) {
            $rating = max(0.0, min(5.0, (float) $platform['penarafan']));
            $w = match (true) {
                $rating >= 4.5 => 0.3,
                $rating >= 4.0 => 0.15,
                $rating >= 3.0 => 0.0,
                default => -0.3,
            };
            $features[] = $this->feature('penarafan_platform', 'Penarafan pelanggan platform', number_format($rating, 1).' / 5', $w,
                'Penarafan pelanggan di '.((string) ($platform['nama'] ?? '') ?: 'platform').' ialah '.number_format($rating, 1).' daripada 5.');
        }

        // 10. Thin file — informational only, never penalised.
        if ($thinFile) {
            $features[] = $this->feature('fail_nipis', 'Fail kredit nipis', 'Tiada CCRIS / penyata bank', 0.0,
                $hasIncomeAlt || $hasUtil
                    ? 'Pemohon tiada rekod CCRIS lengkap atau penyata bank; ini tidak dikenakan penalti kerana data alternatif digunakan sebagai pengganti.'
                    : 'Pemohon tiada rekod CCRIS lengkap atau penyata bank dan tiada data alternatif — kemukakan rekod e-wallet, DuitNow QR, bil utiliti atau jualan platform untuk penilaian yang lebih tepat.');
        }

        $logOdds = self::BASE_LOG_ODDS + array_sum(array_column($features, 'w'));
        $base = 100 * $this->sigmoid(self::BASE_LOG_ODDS);
        $final = 100 * $this->sigmoid($logOdds);
        $delta = $logOdds - self::BASE_LOG_ODDS;
        $slope = abs($delta) > 1e-9 ? ($final - $base) / $delta : 100 * $this->sigmoid(self::BASE_LOG_ODDS) * (1 - $this->sigmoid(self::BASE_LOG_ODDS));

        $faktor = [];
        foreach ($features as $f) {
            $points = round($f['w'] * $slope, 1);
            $faktor[] = [
                'kod' => $f['kod'],
                'label' => $f['label'],
                'nilai' => $f['nilai'],
                'sumbangan' => $points,
                'arah' => $points > 0 ? 'naik' : ($points < 0 ? 'turun' : 'neutral'),
                'penerangan' => $f['penerangan'],
            ];
        }
        usort($faktor, fn (array $a, array $b) => abs($b['sumbangan']) <=> abs($a['sumbangan']));

        $skor = max(0, min(100, (int) round($final)));

        return [
            'skor' => $skor,
            'gred' => match (true) {
                $skor >= 85 => 'A',
                $skor >= 75 => 'B',
                $skor >= 60 => 'C',
                $skor >= 45 => 'D',
                default => 'E',
            },
            'tahap_risiko' => match (true) {
                $skor >= (int) config('sppt.credit_scoring.auto_approve_min_score', 80) => 'rendah',
                $skor >= (int) config('sppt.credit_scoring.officer_review_min_score', 60) => 'sederhana',
                default => 'tinggi',
            },
            'keyakinan' => $this->confidence($ewallet, $qr, $utility, $platform, $hasCcris, $hasBank, $declared),
            'fail_nipis' => $thinFile,
            'sumber_data' => $this->sources($ewallet, $qr, $utility, $platform, $hasCcris, $hasBank, $declared),
            'faktor' => $faktor,
            'skor_asas' => (int) round($base),
            'pendapatan_disahkan_bulanan' => round($verified, 2),
            'ansuran_dicadang' => round($instalment, 2),
            'nisbah_khidmat_hutang' => round($dsr, 3),
            'cadangan_had' => $jumlah > 0 ? min($jumlah, $maxAmount) : $maxAmount,
            'permohonan_melebihi_had' => $exceeds,
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{atribut_dikecualikan: list<array{kod: string, label: string}>, ujian_kontrafaktual: list<array<string, mixed>>, lulus: bool, nota: string}
     */
    protected function biasCheck(array $details, float $jumlah, int $skor, ?string $columnNegeri): array
    {
        $tests = [];
        $current = [
            'jantina' => (string) ($details['jantina'] ?? ''),
            'bangsa' => (string) ($details['bangsa'] ?? ''),
            'agama' => (string) ($details['agama'] ?? ''),
            'negeri' => (string) ($details['negeri'] ?? $columnNegeri ?? ''),
        ];

        foreach ($current as $attr => $value) {
            $flipped = $this->flip($attr, $value);
            $variant = $details;
            $variant[$attr] = $flipped;
            $testScore = $this->evaluate($variant, $jumlah)['skor'];
            $tests[] = [
                'atribut' => $attr,
                'nilai_asal' => $value !== '' ? $value : 'Tidak dinyatakan',
                'nilai_uji' => $flipped,
                'skor_asal' => $skor,
                'skor_uji' => $testScore,
                'beza' => $testScore - $skor,
            ];
        }

        $pass = collect($tests)->every(fn (array $t) => $t['beza'] === 0);
        $excluded = [];
        foreach (self::PROTECTED_ATTRIBUTES as $kod => $label) {
            $excluded[] = ['kod' => $kod, 'label' => $label];
        }

        return [
            'atribut_dikecualikan' => $excluded,
            'ujian_kontrafaktual' => $tests,
            'lulus' => $pass,
            'nota' => $pass
                ? 'Atribut dilindungi tidak digunakan sebagai ciri model; ujian kontrafaktual menukar setiap atribut dan skor kekal sama.'
                : 'Amaran: skor berubah apabila atribut dilindungi ditukar — model perlu disemak untuk berat sebelah.',
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function aiNarrative(array $result): ?string
    {
        $facts = [
            'skor' => $result['skor'],
            'gred' => $result['gred'],
            'tahap_risiko' => $result['tahap_risiko'],
            'keyakinan_data' => $result['keyakinan'],
            'fail_nipis' => $result['fail_nipis'],
            'pendapatan_disahkan_bulanan' => $result['pendapatan_disahkan_bulanan'],
            'ansuran_dicadang' => $result['ansuran_dicadang'],
            'nisbah_khidmat_hutang' => $result['nisbah_khidmat_hutang'],
            'cadangan_had' => $result['cadangan_had'],
            'faktor' => array_map(fn (array $f) => [
                'label' => $f['label'], 'nilai' => $f['nilai'], 'sumbangan' => $f['sumbangan'], 'arah' => $f['arah'],
            ], $result['faktor']),
            'sumber_data' => array_values(array_map(fn (array $s) => $s['label'].': '.$s['nilai_ringkas'], array_filter($result['sumber_data'], fn (array $s) => $s['tersedia']))),
        ];

        $system = 'Anda pembantu pegawai pembiayaan TEKUN. Terangkan keputusan scorecard kredit dalam Bahasa Melayu yang mudah difahami. '
            .'Gunakan HANYA fakta yang diberi. Jangan ubah, kira semula atau mempersoalkan skor. Jangan sebut jantina, bangsa, agama, negeri atau umur.';
        $prompt = "Fakta scorecard (JSON):\n".json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nTulis 3-5 ayat untuk pegawai: skor dan tahap risiko, faktor utama yang menaikkan dan menurunkan skor, dan cadangan tindakan (cth. had pembiayaan atau dokumen tambahan)."
            ."\nFormat JSON: {\"penjelasan\": \"...\"}";

        try {
            $reply = $this->anthropic->json($system, $prompt, [], 800);
        } catch (Throwable) {
            return null;
        }

        $text = trim((string) ($reply['data']['penjelasan'] ?? ''));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function templateNarrative(array $result): string
    {
        $up = array_slice(array_values(array_filter($result['faktor'], fn (array $f) => $f['arah'] === 'naik')), 0, 2);
        $down = array_slice(array_values(array_filter($result['faktor'], fn (array $f) => $f['arah'] === 'turun')), 0, 2);
        $describe = fn (array $list) => implode(' dan ', array_map(fn (array $f) => lcfirst($f['label']).' ('.$f['nilai'].')', $list));

        $text = 'Skor kredit alternatif ialah '.$result['skor'].'/100 (Gred '.$result['gred'].', risiko '.$result['tahap_risiko'].').';
        if ($up !== []) {
            $text .= ' Faktor yang menaikkan skor: '.$describe($up).'.';
        }
        if ($down !== []) {
            $text .= ' Faktor yang menurunkan skor: '.$describe($down).'.';
        }
        if ($result['fail_nipis']) {
            $text .= $result['pendapatan_disahkan_bulanan'] > 0
                ? ' Pemohon berfail nipis (tiada CCRIS/penyata bank) tetapi data alternatif digunakan sebagai pengganti tanpa penalti.'
                : ' Pemohon berfail nipis dan tiada data alternatif — minta rekod e-wallet, DuitNow QR atau bil utiliti.';
        }
        $text .= ' Had pembiayaan dicadang: '.$this->rm((float) $result['cadangan_had']).'. Skor ini sokongan keputusan; keputusan muktamad oleh pegawai.';

        return $text;
    }

    /**
     * @param  array<string, mixed>  $ewallet
     * @param  array<string, mixed>  $qr
     * @param  array<string, mixed>  $utility
     * @param  array<string, mixed>  $platform
     */
    protected function confidence(array $ewallet, array $qr, array $utility, array $platform, bool $ccris, bool $bank, float $declared): int
    {
        $depth = fn (array $section) => isset($section['bulan_aktif']) ? min(1.0, max(0, (int) $section['bulan_aktif']) / 6) : 0.5;

        $total = 0.0;
        if ((float) ($ewallet['purata_masuk_bulanan'] ?? 0) > 0) {
            $total += 15 * $depth($ewallet);
        }
        if ((float) ($qr['purata_jualan_bulanan'] ?? 0) > 0) {
            $total += 20 * $depth($qr);
        }
        if ((int) ($utility['bulan_dinilai'] ?? 0) > 0) {
            $total += 15 * min(1.0, (int) $utility['bulan_dinilai'] / 6);
        }
        if ((float) ($platform['purata_jualan_bulanan'] ?? 0) > 0) {
            $total += 10 * $depth($platform);
        }
        $total += ($ccris ? 15 : 0) + ($bank ? 15 : 0) + ($declared > 0 ? 10 : 0);

        return max(0, min(100, (int) round($total)));
    }

    /**
     * @param  array<string, mixed>  $ewallet
     * @param  array<string, mixed>  $qr
     * @param  array<string, mixed>  $utility
     * @param  array<string, mixed>  $platform
     * @return list<array{kod: string, label: string, tersedia: bool, nilai_ringkas: string}>
     */
    protected function sources(array $ewallet, array $qr, array $utility, array $platform, bool $ccris, bool $bank, float $declared): array
    {
        $ew = (float) ($ewallet['purata_masuk_bulanan'] ?? 0);
        $q = (float) ($qr['purata_jualan_bulanan'] ?? 0);
        $pl = (float) ($platform['purata_jualan_bulanan'] ?? 0);
        $ut = (int) ($utility['bulan_dinilai'] ?? 0);
        $join = fn (array $parts) => implode(' · ', array_filter($parts, fn ($p) => $p !== null && $p !== ''));

        return [
            [
                'kod' => 'ewallet', 'label' => $this->sourceLabel('ewallet'), 'tersedia' => $ew > 0,
                'nilai_ringkas' => $ew > 0 ? $join([
                    (string) ($ewallet['penyedia'] ?? ''),
                    $this->rm($ew).'/bulan',
                    isset($ewallet['bilangan_transaksi_bulanan']) ? (int) $ewallet['bilangan_transaksi_bulanan'].' transaksi/bulan' : null,
                    isset($ewallet['bulan_aktif']) ? (int) $ewallet['bulan_aktif'].' bulan aktif' : null,
                ]) : 'Tiada data',
            ],
            [
                'kod' => 'duitnow_qr', 'label' => $this->sourceLabel('duitnow_qr'), 'tersedia' => $q > 0,
                'nilai_ringkas' => $q > 0 ? $join([
                    $this->rm($q).'/bulan',
                    isset($qr['bilangan_transaksi_bulanan']) ? (int) $qr['bilangan_transaksi_bulanan'].' transaksi/bulan' : null,
                    isset($qr['bulan_aktif']) ? (int) $qr['bulan_aktif'].' bulan aktif' : null,
                    isset($qr['trend_pertumbuhan_peratus']) && is_numeric($qr['trend_pertumbuhan_peratus']) ? sprintf('trend %+.1f%%', (float) $qr['trend_pertumbuhan_peratus']) : null,
                ]) : 'Tiada data',
            ],
            [
                'kod' => 'bil_utiliti', 'label' => $this->sourceLabel('bil_utiliti'), 'tersedia' => $ut > 0,
                'nilai_ringkas' => $ut > 0 ? min($ut, (int) ($utility['bulan_dibayar_tepat'] ?? 0)).'/'.$ut.' bulan dibayar tepat masa' : 'Tiada data',
            ],
            [
                'kod' => 'platform', 'label' => $this->sourceLabel('platform'), 'tersedia' => $pl > 0,
                'nilai_ringkas' => $pl > 0 ? $join([
                    (string) ($platform['nama'] ?? ''),
                    $this->rm($pl).'/bulan',
                    isset($platform['penarafan']) && is_numeric($platform['penarafan']) ? number_format((float) $platform['penarafan'], 1).'★' : null,
                    isset($platform['bulan_aktif']) ? (int) $platform['bulan_aktif'].' bulan aktif' : null,
                ]) : 'Tiada data',
            ],
            [
                'kod' => 'ccris', 'label' => 'Rekod CCRIS / CTOS', 'tersedia' => $ccris,
                'nilai_ringkas' => $ccris ? 'Tersedia' : 'Tiada / tidak lengkap',
            ],
            [
                'kod' => 'penyata_bank', 'label' => 'Penyata bank', 'tersedia' => $bank,
                'nilai_ringkas' => $bank ? 'Tersedia' : 'Tiada',
            ],
            [
                'kod' => 'borang', 'label' => 'Borang permohonan (pendapatan diisytihar)', 'tersedia' => $declared > 0,
                'nilai_ringkas' => $declared > 0 ? $this->rm($declared).'/bulan diisytihar' : 'Tidak diisytihar',
            ],
        ];
    }

    /**
     * @return array{nama: string, jenis: string, ciri_utama: list<string>, versi_data: string}
     */
    protected function modelInfo(): array
    {
        return [
            'nama' => self::MODEL_NAME,
            'jenis' => 'Scorecard logistik boleh-jelas (explainable)',
            'ciri_utama' => [
                'Pendapatan bulanan disahkan (e-wallet, DuitNow QR, platform — tanpa kiraan berganda)',
                'Konsistensi pendapatan diisytihar berbanding disahkan',
                'Tempoh aktif & kekerapan transaksi',
                'Trend pertumbuhan jualan',
                'Disiplin bayaran bil utiliti',
                'Nisbah khidmat hutang (ansuran + komitmen / pendapatan)',
                'Tempoh berniaga & penarafan platform',
            ],
            'versi_data' => '2026-10',
        ];
    }

    /**
     * @param  array<string, float>  $rates
     * @return array{atribut: string, kumpulan_terendah: string, kumpulan_tertinggi: string, nisbah: float, lulus_peraturan_4_5: bool, catatan: string}
     */
    protected function disparateImpact(string $attr, array $rates): array
    {
        if (count($rates) < 2) {
            return [
                'atribut' => $attr,
                'kumpulan_terendah' => '-',
                'kumpulan_tertinggi' => '-',
                'nisbah' => 1.0,
                'lulus_peraturan_4_5' => true,
                'catatan' => 'Kurang daripada dua kumpulan dengan sekurang-kurangnya 5 permohonan — nisbah tidak dapat dinilai.',
            ];
        }

        asort($rates);
        $lowest = array_key_first($rates);
        $highest = array_key_last($rates);
        $ratio = $rates[$highest] > 0 ? $rates[$lowest] / $rates[$highest] : 1.0;

        return [
            'atribut' => $attr,
            'kumpulan_terendah' => (string) $lowest,
            'kumpulan_tertinggi' => (string) $highest,
            'nisbah' => round($ratio, 3),
            'lulus_peraturan_4_5' => $ratio >= 0.8,
            'catatan' => $ratio >= 0.8 ? 'Memenuhi peraturan 4/5.' : 'Di bawah 0.8 — kemungkinan impak buruk; perlu siasatan.',
        ];
    }

    /**
     * @return array{kod: string, label: string, nilai: string, w: float, penerangan: string}
     */
    protected function feature(string $kod, string $label, string $nilai, float $w, string $penerangan): array
    {
        return compact('kod', 'label', 'nilai', 'w', 'penerangan');
    }

    protected function maxFinancing(float $income, float $commitments, int $tenure, float $rate): float
    {
        $room = $income * self::MAX_DSR - $commitments;
        if ($room <= 0 || $tenure <= 0) {
            return 0.0;
        }

        $principal = $room * $tenure / (1 + ($rate / 100) * ($tenure / 12));

        return (float) (floor($principal / 500) * 500);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function requestedAmount(Permohonan $permohonan, array $details): float
    {
        $amount = (float) ($permohonan->jumlah_permohonan ?? 0);

        return $amount > 0 ? $amount : max(0.0, (float) ($details['jumlah_permohonan'] ?? 0));
    }

    protected function flip(string $attr, string $value): string
    {
        $v = strtolower(trim($value));

        return match ($attr) {
            'jantina' => in_array($v, ['p', 'perempuan', 'wanita'], true) ? 'L' : 'P',
            'bangsa' => $v === 'melayu' ? 'Cina' : 'Melayu',
            'agama' => $v === 'islam' ? 'Buddha' : 'Islam',
            'negeri' => $v === 'selangor' ? 'Sabah' : 'Selangor',
            default => $value,
        };
    }

    protected function genderLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'l', 'lelaki' => 'Lelaki',
            'p', 'perempuan', 'wanita' => 'Perempuan',
            default => trim($value),
        };
    }

    protected function sourceLabel(string $jenis): string
    {
        return match ($jenis) {
            'ewallet' => 'E-wallet',
            'duitnow_qr' => 'Jualan DuitNow QR',
            'bil_utiliti' => 'Bil utiliti',
            'platform' => 'Jualan platform e-dagang',
            default => $jenis,
        };
    }

    protected function months(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $digits = (int) preg_replace('/\D/', '', (string) $raw);

        return $digits > 0 ? $digits : null;
    }

    protected function years(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! preg_match('/\d+(?:[.,]\d+)?/', (string) $raw, $m)) {
            return null;
        }
        $value = (float) str_replace(',', '.', $m[0]);

        return str_contains(strtolower((string) $raw), 'bulan') ? $value / 12 : $value;
    }

    protected function coerce(mixed $value, string $type): string|int|float|null
    {
        if ($type === 'string') {
            $text = is_scalar($value) ? trim((string) $value) : '';

            return $text !== '' ? mb_substr($text, 0, 100) : null;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;

        return match ($type) {
            'amount' => round(max(0.0, $number), 2),
            'count' => max(0, (int) round($number)),
            'months' => max(0, min(120, (int) round($number))),
            'percent' => round(max(-100.0, min(1000.0, $number)), 1),
            'rating' => round(max(0.0, min(5.0, $number)), 1),
            default => null,
        };
    }

    protected function sigmoid(float $x): float
    {
        return 1 / (1 + exp(-$x));
    }

    protected function rm(float $amount, int $decimals = 0): string
    {
        return 'RM '.number_format($amount, $decimals);
    }
}
