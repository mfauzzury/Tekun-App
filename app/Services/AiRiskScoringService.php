<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\Permohonan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Spec 1.6.3 — AI Loan Risk Scoring (decision-support for officers).
 */
class AiRiskScoringService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     risk_score: int,
     *     risk_category: string,
     *     recommended_limit: float,
     *     factors: list<array{factor: string, impact: string, description: string}>,
     *     confidence: int,
     *     source: string,
     *     scored_at: string,
     *     message: string
     * }
     */
    public function score(array $input): array
    {
        $normalized = $this->normalizeInput($input);
        $enriched = $this->enrichWithHistoricalData($normalized);

        $apiKey = (string) config('services.anthropic.key', '');
        if ($apiKey !== '') {
            try {
                return $this->scoreWithAi($apiKey, $enriched);
            } catch (\Throwable) {
                // Fall back to deterministic heuristic when AI is unavailable.
            }
        }

        return $this->scoreWithHeuristic($enriched);
    }

    /**
     * Build scoring input from a stored permohonan record.
     *
     * @return array<string, mixed>
     */
    public function inputFromPermohonan(Permohonan $permohonan): array
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];

        $pendapatan = (float) ($details['pendapatan'] ?? 0);
        $pendapatanBulan = max(1, (int) ($details['pendapatan_bulan'] ?? 1));
        $pendapatanPasangan = (float) ($details['pendapatan_pasangan'] ?? 0);
        $pendapatanPasanganBulan = max(1, (int) ($details['pendapatan_pasangan_bulan'] ?? 1));

        return [
            'umur' => isset($details['umur']) ? (int) $details['umur'] : null,
            'no_kp' => (string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? ''),
            'kategori_pembiayaan' => (string) ($permohonan->kategori_pembiayaan ?? $details['kategori_pembiayaan'] ?? ''),
            'sektor_perniagaan' => (string) ($details['sektor_perniagaan'] ?? ''),
            'tempoh_perniagaan_tahun' => isset($details['tempoh_perniagaan_tahun'])
                ? (int) $details['tempoh_perniagaan_tahun']
                : (isset($details['tempoh_perniagaan']) ? (int) $details['tempoh_perniagaan'] : null),
            'pendapatan_bulanan' => ($pendapatan / $pendapatanBulan) + ($pendapatanPasangan / $pendapatanPasanganBulan),
            'jumlah_komitmen_sedia_ada' => (float) ($details['jumlah_komitmen_sedia_ada'] ?? $details['komitmen_bulanan'] ?? 0),
            'jumlah_permohonan' => (float) ($permohonan->jumlah_permohonan ?? $details['jumlah_permohonan'] ?? 0),
            'negeri' => (string) ($details['negeri'] ?? ''),
            'muflis' => (bool) ($details['muflis'] ?? false),
            'permohonan_id' => $permohonan->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        return [
            'umur' => isset($input['umur']) ? (int) $input['umur'] : null,
            'no_kp' => $this->normalizeIc((string) ($input['no_kp'] ?? $input['noKp'] ?? '')),
            'kategori_pembiayaan' => (string) ($input['kategori_pembiayaan'] ?? $input['kategoriPembiayaan'] ?? ''),
            'sektor_perniagaan' => (string) ($input['sektor_perniagaan'] ?? $input['sektorPerniagaan'] ?? ''),
            'tempoh_perniagaan_tahun' => isset($input['tempoh_perniagaan_tahun'])
                ? (int) $input['tempoh_perniagaan_tahun']
                : (isset($input['tempohPerniagaanTahun']) ? (int) $input['tempohPerniagaanTahun']
                    : (isset($input['tempoh_perniagaan']) ? (int) $input['tempoh_perniagaan']
                        : (isset($input['tempohPerniagaan']) ? (int) $input['tempohPerniagaan'] : null))),
            'pendapatan_bulanan' => (float) ($input['pendapatan_bulanan'] ?? $input['pendapatanBulanan'] ?? 0),
            'jumlah_komitmen_sedia_ada' => (float) ($input['jumlah_komitmen_sedia_ada'] ?? $input['jumlahKomitmenSediaAda'] ?? 0),
            'jumlah_permohonan' => (float) ($input['jumlah_permohonan'] ?? $input['jumlahPermohonan'] ?? 0),
            'negeri' => (string) ($input['negeri'] ?? ''),
            'muflis' => (bool) ($input['muflis'] ?? $input['isBankrupt'] ?? false),
            'permohonan_id' => isset($input['permohonan_id']) ? (int) $input['permohonan_id'] : (isset($input['permohonanId']) ? (int) $input['permohonanId'] : null),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function enrichWithHistoricalData(array $input): array
    {
        $noKp = (string) ($input['no_kp'] ?? '');

        if ($noKp === '') {
            return $input;
        }

        $activeAccounts = AkaunPembiayaan::query()
            ->where('ic', $noKp)
            ->whereRaw('LOWER(status) IN (?, ?, ?)', ['aktif', 'tunggakan', 'npf'])
            ->get();

        $input['jumlah_pembiayaan_aktif'] = $activeAccounts->count();
        $input['jumlah_pembiayaan_aktif_rm'] = (float) $activeAccounts->sum('jumlah_pembiayaan');
        $input['akaun_tunggakan'] = $activeAccounts->filter(
            fn (AkaunPembiayaan $akaun) => in_array(strtolower((string) $akaun->status), ['tunggakan', 'npf'], true)
        )->count();
        $input['sejarah_bayaran'] = $activeAccounts->map(fn (AkaunPembiayaan $akaun) => [
            'produk' => $akaun->produk,
            'status' => $akaun->status,
            'jumlah_pembiayaan' => (float) $akaun->jumlah_pembiayaan,
            'tunggakan' => (float) $akaun->tunggakan,
            'negeri' => $akaun->negeri,
        ])->values()->all();

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     risk_score: int,
     *     risk_category: string,
     *     recommended_limit: float,
     *     factors: list<array{factor: string, impact: string, description: string}>,
     *     confidence: int,
     *     source: string,
     *     scored_at: string,
     *     message: string
     * }
     */
    private function scoreWithHeuristic(array $input): array
    {
        $score = 70;
        $factors = [];

        $umur = isset($input['umur']) ? (int) $input['umur'] : null;
        if ($umur !== null) {
            if ($umur >= 25 && $umur <= 55) {
                $score += 8;
                $factors[] = $this->factor('Umur', 'positif', 'Umur pemohon dalam julat optimum (25–55 tahun).');
            } elseif ($umur < 21 || $umur > 60) {
                $score -= 12;
                $factors[] = $this->factor('Umur', 'negatif', 'Umur pemohon di luar julat optimum risiko.');
            }
        }

        $pendapatan = max(0.0, (float) ($input['pendapatan_bulanan'] ?? 0));
        $komitmen = max(0.0, (float) ($input['jumlah_komitmen_sedia_ada'] ?? 0));
        $ratio = $pendapatan > 0 ? $komitmen / $pendapatan : ($komitmen > 0 ? 1.0 : 0.0);

        if ($ratio <= 0.3) {
            $score += 10;
            $factors[] = $this->factor('Nisbah Komitmen', 'positif', 'Komitmen kewangan rendah berbanding pendapatan.');
        } elseif ($ratio <= 0.5) {
            $score += 4;
            $factors[] = $this->factor('Nisbah Komitmen', 'neutral', 'Nisbah komitmen berada dalam paras sederhana.');
        } elseif ($ratio <= 0.7) {
            $score -= 4;
            $factors[] = $this->factor('Nisbah Komitmen', 'negatif', 'Komitmen kewangan agak tinggi berbanding pendapatan.');
        } else {
            $score -= 15;
            $factors[] = $this->factor('Nisbah Komitmen', 'negatif', 'Komitmen kewangan melebihi 70% pendapatan bulanan.');
        }

        if ($pendapatan >= 5000) {
            $score += 5;
            $factors[] = $this->factor('Pendapatan', 'positif', 'Pendapatan bulanan melebihi RM 5,000.');
        } elseif ($pendapatan < 1500 && $pendapatan > 0) {
            $score -= 10;
            $factors[] = $this->factor('Pendapatan', 'negatif', 'Pendapatan bulanan rendah (kurang RM 1,500).');
        }

        $tempoh = isset($input['tempoh_perniagaan_tahun']) ? (int) $input['tempoh_perniagaan_tahun'] : null;
        if ($tempoh !== null) {
            if ($tempoh >= 3) {
                $score += 8;
                $factors[] = $this->factor('Tempoh Perniagaan', 'positif', 'Perniagaan beroperasi lebih 3 tahun.');
            } elseif ($tempoh < 1) {
                $score -= 6;
                $factors[] = $this->factor('Tempoh Perniagaan', 'negatif', 'Perniagaan masih baru (kurang 1 tahun).');
            }
        }

        $activeCount = (int) ($input['jumlah_pembiayaan_aktif'] ?? 0);
        $tunggakanCount = (int) ($input['akaun_tunggakan'] ?? 0);

        if ($activeCount === 0) {
            $score += 4;
            $factors[] = $this->factor('Sejarah Pembiayaan', 'positif', 'Tiada pembiayaan aktif sedia ada.');
        } elseif ($activeCount > 1) {
            $score -= 8;
            $factors[] = $this->factor('Sejarah Pembiayaan', 'negatif', "Pemohon mempunyai {$activeCount} pembiayaan aktif.");
        }

        if ($tunggakanCount > 0) {
            $score -= min(25, $tunggakanCount * 15);
            $factors[] = $this->factor('Tunggakan', 'negatif', "Rekod tunggakan/NPF dikesan ({$tunggakanCount} akaun).");
        }

        if ((bool) ($input['muflis'] ?? false)) {
            $score -= 30;
            $factors[] = $this->factor('Insolvensi', 'negatif', 'Pemohon disenaraikan sebagai muflis / insolvensi.');
        }

        $jumlahPermohonan = max(0.0, (float) ($input['jumlah_permohonan'] ?? 0));
        if ($pendapatan > 0 && $jumlahPermohonan > 0) {
            $annualIncome = $pendapatan * 12;
            $leverage = $jumlahPermohonan / $annualIncome;
            if ($leverage > 3) {
                $score -= 12;
                $factors[] = $this->factor('Leverage', 'negatif', 'Jumlah dipohon melebihi 3x pendapatan tahunan.');
            } elseif ($leverage > 2) {
                $score -= 6;
                $factors[] = $this->factor('Leverage', 'neutral', 'Jumlah dipohon melebihi 2x pendapatan tahunan.');
            }
        }

        $score = max(0, min(100, $score));
        $category = $this->categoryFromScore($score);
        $recommended = $this->recommendedLimit($score, $pendapatan, $jumlahPermohonan);

        return $this->buildResult(
            $score,
            $category,
            $recommended,
            $factors,
            75,
            'heuristic',
            'Skor risiko dikira menggunakan model heuristik SPPT (decision-support).'
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     risk_score: int,
     *     risk_category: string,
     *     recommended_limit: float,
     *     factors: list<array{factor: string, impact: string, description: string}>,
     *     confidence: int,
     *     source: string,
     *     scored_at: string,
     *     message: string
     * }
     */
    private function scoreWithAi(string $apiKey, array $input): array
    {
        $prompt = $this->buildAiPrompt($input);
        $rawJson = $this->callAnthropic($apiKey, $prompt);
        $parsed = $this->decodeModelJson($rawJson);

        $score = max(0, min(100, (int) ($parsed['risk_score'] ?? 0)));
        $category = (string) ($parsed['risk_category'] ?? $this->categoryFromScore($score));
        $recommended = (float) ($parsed['recommended_limit'] ?? $this->recommendedLimit(
            $score,
            (float) ($input['pendapatan_bulanan'] ?? 0),
            (float) ($input['jumlah_permohonan'] ?? 0),
        ));

        $factors = [];
        foreach ($parsed['factors'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $factors[] = $this->factor(
                (string) ($item['factor'] ?? 'Faktor'),
                (string) ($item['impact'] ?? 'neutral'),
                (string) ($item['description'] ?? ''),
            );
        }

        if ($factors === []) {
            $factors[] = $this->factor('AI', 'neutral', (string) ($parsed['summary'] ?? 'Penilaian risiko AI.'));
        }

        return $this->buildResult(
            $score,
            $category,
            $recommended,
            $factors,
            max(0, min(100, (int) ($parsed['confidence'] ?? 80))),
            'ai',
            'Skor risiko dikira menggunakan model AI Claude (decision-support).'
        );
    }

    private function buildAiPrompt(array $input): string
    {
        $payload = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Anda adalah pakar penilaian risiko pembiayaan mikro TEKUN Nasional (Malaysia).
Analisis data pemohon berikut dan kembalikan SATU objek JSON sahaja (tiada markdown).

Skor risiko 0–100: lebih tinggi = risiko kredit lebih RENDAH (lebih selamat).
Kategori risiko: "Risiko Rendah" (80–100), "Risiko Sederhana" (60–79), "Risiko Tinggi" (0–59).

Data pemohon:
{$payload}

Format JSON wajib:
{
  "risk_score": 72,
  "risk_category": "Risiko Sederhana",
  "recommended_limit": 40000,
  "confidence": 85,
  "summary": "Ringkasan 1 ayat dalam BM",
  "factors": [
    {"factor": "Nama faktor", "impact": "positif|negatif|neutral", "description": "Penerangan BM"}
  ]
}

Peraturan:
- recommended_limit dalam RM (nombor sahaja, tanpa simbol)
- Cadangan had pembiayaan mesti realistik berdasarkan pendapatan dan sejarah
- Gunakan faktor sejarah bayaran jika ada
- Output BM untuk kategori, summary, dan factors
PROMPT;
    }

    private function callAnthropic(string $apiKey, string $prompt): string
    {
        $model = (string) config('services.anthropic.model', 'claude-haiku-4-5');
        $timeout = (int) config('services.anthropic.timeout', 300);

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout($timeout)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 2048,
            'temperature' => 0.2,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('AI Risk Scoring gagal: '.Str::limit((string) $error, 500));
        }

        $content = $response->json('content.0.text');
        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('AI Risk Scoring tidak mengembalikan data.');
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeModelJson(string $rawJson): array
    {
        $trimmed = trim($rawJson);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $trimmed, $matches)) {
            $trimmed = trim($matches[1]);
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $trimmed = substr($trimmed, $start, $end - $start + 1);
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('AI Risk Scoring mengembalikan JSON tidak sah.');
        }

        return $decoded;
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

    private function recommendedLimit(int $score, float $pendapatanBulanan, float $jumlahPermohonan): float
    {
        $annualCap = max(5000.0, $pendapatanBulanan * 12 * 0.5);
        $scoreFactor = $score / 100;
        $fromRequest = $jumlahPermohonan > 0 ? $jumlahPermohonan * $scoreFactor : $annualCap * $scoreFactor;
        $limit = min($annualCap, max(5000.0, $fromRequest));

        return round(min(300000.0, $limit), -2);
    }

    /**
     * @param  list<array{factor: string, impact: string, description: string}>  $factors
     * @return array{
     *     risk_score: int,
     *     risk_category: string,
     *     recommended_limit: float,
     *     factors: list<array{factor: string, impact: string, description: string}>,
     *     confidence: int,
     *     source: string,
     *     scored_at: string,
     *     message: string
     * }
     */
    private function buildResult(
        int $score,
        string $category,
        float $recommendedLimit,
        array $factors,
        int $confidence,
        string $source,
        string $message,
    ): array {
        return [
            'risk_score' => $score,
            'risk_category' => $category,
            'recommended_limit' => round($recommendedLimit, 2),
            'factors' => $factors,
            'confidence' => max(0, min(100, $confidence)),
            'source' => $source,
            'scored_at' => now()->toIso8601String(),
            'message' => $message,
        ];
    }

    /**
     * @return array{factor: string, impact: string, description: string}
     */
    private function factor(string $name, string $impact, string $description): array
    {
        return [
            'factor' => $name,
            'impact' => $impact,
            'description' => $description,
        ];
    }

    private function normalizeIc(string $ic): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', trim($ic)) ?? '');
    }
}
