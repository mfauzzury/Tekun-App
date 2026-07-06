<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Spec 1.7.2 — AI-OCR extraction from BPP-BORANG-01 (filled permohonan form PDF/image).
 */
class PermohonanFormOcrService
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/x-png'];

    /**
     * @return array{
     *     fields: array<string, mixed>,
     *     confidence: int,
     *     populated_count: int,
     *     field_confidence: array<string, int>,
     *     message: string
     * }
     */
    public function extractFromUpload(UploadedFile $file): array
    {
        $timeout = (int) config('services.anthropic.timeout', 300);
        set_time_limit($timeout + 120);

        $apiKey = (string) config('services.anthropic.key', '');
        if ($apiKey === '') {
            throw new \RuntimeException('ANTHROPIC_API_KEY tidak dikonfigurasi. Sila tetapkan dalam .env.');
        }

        $absolutePath = $file->getRealPath() ?: $file->getPathname();
        $mime = strtolower($file->getMimeType() ?: mime_content_type($absolutePath) ?: '');
        $originalName = $file->getClientOriginalName();
        $binary = file_get_contents($absolutePath);
        if ($binary === false) {
            throw new \RuntimeException('Gagal membaca fail borang.');
        }

        $contentParts = $this->buildVisionContentParts($binary, $mime, $originalName);
        $rawJson = $this->callAnthropic($apiKey, $contentParts);
        $parsed = $this->decodeModelJson($rawJson);

        return $this->normalizeExtraction($parsed);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildVisionContentParts(string $binary, string $mime, string $originalName): array
    {
        $base64 = base64_encode($binary);
        $instruction = $this->buildExtractionPrompt();

        if ($mime === 'application/pdf' || str_ends_with(strtolower($originalName), '.pdf')) {
            return [
                ['type' => 'text', 'text' => $instruction],
                [
                    'type' => 'document',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => 'application/pdf',
                        'data' => $base64,
                    ],
                ],
            ];
        }

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            return [
                ['type' => 'text', 'text' => $instruction],
                [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $mime,
                        'data' => $base64,
                    ],
                ],
            ];
        }

        throw new \RuntimeException('Format fail tidak disokong untuk AI-OCR. Sila gunakan PDF, JPG, atau PNG.');
    }

    private function buildExtractionPrompt(): string
    {
        $reference = config('sppt-reference-data', []);

        return <<<'PROMPT'
You are an OCR assistant for TEKUN Nasional financing application form BPP-BORANG-01 (Borang Permohonan Pembiayaan TEKUN).

Extract all readable handwritten or printed field values from the uploaded form. The form may span multiple pages — read every page.

Return ONLY valid JSON with this exact structure (no markdown fences, no commentary):
{
  "confidence": 0-100 overall extraction confidence,
  "field_confidence": { "field_key": 0-100, ... },
  "fields": {
    "kategori_pembiayaan": "TEKUN Niaga|TEMAN TEKUN|Kontrak|SPUMI|BPU|TEKUN Corp|Lain-lain",
    "status_perniagaan": "sedang_berniaga|memulakan",
    "sektor_perniagaan": string,
    "kaedah_perniagaan": "online|offline",
    "nama_bank": string,
    "no_akaun_bank": string,
    "nama": string,
    "no_ic_baru": "######-##-####",
    "no_ic_lama": string or null,
    "jantina": "L|P",
    "agama": "islam|buddha|hindu|kristian|lain_lain",
    "tarikh_lahir_hari": "1-31",
    "tarikh_lahir_bulan": "1-12",
    "tarikh_lahir_tahun": "YYYY",
    "bangsa": "Melayu|Cina|India|Lain-lain",
    "kaum": string or null,
    "umur": string,
    "taraf_perkahwinan": "Bujang|Berkahwin|Duda|Janda",
    "bilangan_tanggungan": string,
    "oku": boolean,
    "diberhentikan_pandemik": boolean,
    "asnaf_berdaftar": boolean,
    "taraf_pendidikan": string,
    "alamat": string,
    "poskod": string,
    "negeri": string,
    "no_telefon_rumah": string,
    "no_telefon_bimbit": string,
    "email": string,
    "facebook": string,
    "instagram": string,
    "status_kediaman": "Sendiri|Sewa|Keluarga",
    "status_pekerjaan": "bekerja|tidak_bekerja",
    "sektor_pekerjaan": string,
    "jawatan": string,
    "status_jawatan": string,
    "pekerjaan_sekarang": string,
    "pendapatan": numeric string without RM,
    "pendapatan_bulan": "1-12",
    "nama_majikan": string,
    "alamat_majikan": string,
    "no_telefon_majikan": string,
    "nama_pasangan": string,
    "no_ic_pasangan": string,
    "no_passport_pasangan": string,
    "pekerjaan_pasangan": string,
    "alamat_majikan_pasangan": string,
    "poskod_majikan_pasangan": string,
    "no_telefon_majikan_pasangan": string,
    "no_telefon_bimbit_pasangan": string,
    "pendapatan_pasangan": numeric string,
    "pendapatan_pasangan_bulan": "1-12",
    "jumlah_permohonan": numeric string,
    "tempoh_pembiayaan": string months,
    "kekerapan_bayaran": "Mingguan|Bulanan|Mengikut Tempoh Kontrak Kerja|Inden",
    "tujuan": string,
    "nama_perniagaan": string,
    "no_ssm": string,
    "tempoh_berniaga": string years,
    "alamat_premis": string,
    "poskod_premis": string,
    "anggaran_pendapatan": numeric string,
    "no_tel_premis": string,
    "no_tel_bimbit_premis": string,
    "status_premis": "Sendiri|Sewa|Keluarga",
    "pemilikan_perniagaan": string,
    "modal_berbayar": numeric string,
    "pemegang_saham": boolean,
    "tarikh_daftar": "YYYY-MM-DD or null",
    "tarikh_tamat_lesen": "YYYY-MM-DD or null",
    "keahlian_persatuan": boolean,
    "keahlian_persatuan_nyata": string,
    "masa_berniaga_dari": "HH:MM",
    "masa_berniaga_hingga": "HH:MM",
    "pengiktirafan": boolean,
    "pengiktirafan_nyata": string,
    "nilai_aset": numeric string,
    "sumber_modal": string,
    "kursus_agensi": "INSKEN|SME CORP|CEDAR|Lain-lain",
    "kursus_lain": string,
    "perniagaan_terdahulu": string,
    "pembiayaan_sedia_ada": boolean,
    "institusi_pembiayaan": string,
    "jumlah_pembiayaan_sedia_ada": numeric string,
    "baki_pembiayaan": numeric string,
    "takaful_pembiayaan": boolean,
    "takaful_kemalangan": boolean,
    "takaful_kemalangan_pakej": "pakej1|pakej2|pakej3",
    "perkeso": boolean,
    "perkeso_pakej": "a|b|c|d",
    "wasiat": boolean,
    "wasiat_jumlah": numeric string,
    "kebenaran_kredit": boolean
  }
}

Rules:
- Omit fields you cannot read; use null for unknown optional values.
- For ticked checkboxes use true/false based on visible ticks or "YA"/"TIDAK".
- For checkbox groups (jantina, agama, taraf_perkahwinan, status_kediaman, status_premis): detect X, tick, checkmark, or filled mark beside the selected label and return that exact option value.
- BPP-BORANG-01 section hints (do not skip these):
  - Field 14 "BANGSA / KAUM (sila nyatakan)": typed/handwritten text on the line -> bangsa (e.g. "CINA" -> "Cina", "MELAYU" -> "Melayu").
  - Field 16 "TARAF PERKAHWINAN": checkbox BUJANG|BERKAHWIN|DUDA|IBU TUNGGAL -> taraf_perkahwinan (map IBU TUNGGAL to "Janda").
  - Field 27 "STATUS KEDIAMAN": checkbox SENDIRI|SEWA|KELUARGA -> status_kediaman.
- Normalize Malaysian IC to ######-##-#### when possible.
- Strip "RM", commas and spaces from monetary amounts; return digits only.
- Map jantina: Lelaki->L, Wanita->P.
- Map agama labels to lowercase snake values.
- Use reference values where applicable:
PROMPT
            ."\n- negeri: ".implode(', ', $reference['negeriOptions'] ?? [])
            ."\n- sektor_perniagaan: ".implode(', ', $reference['sektorPerniagaanOptions'] ?? [])
            ."\n- taraf_pendidikan: ".implode(', ', $reference['tarafPendidikanOptions'] ?? [])
            ."\n- kategori_pembiayaan: ".implode(', ', $reference['kategoriPembiayaanOptions'] ?? [])
            ."\n- bangsa: ".implode(', ', $reference['bangsaOptions'] ?? [])
            ."\n- taraf_perkahwinan: ".implode(', ', $reference['tarafPerkahwinanOptions'] ?? [])
            ."\n- status_kediaman: ".implode(', ', $reference['statusKediamanOptions'] ?? []);
    }

    /**
     * @param  list<array<string, mixed>>  $contentParts
     */
    private function callAnthropic(string $apiKey, array $contentParts): string
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
                [
                    'role' => 'user',
                    'content' => $contentParts,
                ],
            ],
            'max_tokens' => 8192,
            'temperature' => 0.1,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('AI-OCR gagal: '.Str::limit((string) $error, 500));
        }

        $content = $response->json('content.0.text');
        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('AI-OCR tidak mengembalikan data.');
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeModelJson(string $rawJson): array
    {
        $payload = $this->extractJsonPayload($rawJson);
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('AI-OCR mengembalikan JSON tidak sah.');
        }

        return $decoded;
    }

    private function extractJsonPayload(string $rawJson): string
    {
        $trimmed = trim($rawJson);

        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $trimmed, $matches)) {
            $trimmed = trim($matches[1]);
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $trimmed;
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($trimmed, $start, $end - $start + 1);
        }

        return $trimmed;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{
     *     fields: array<string, mixed>,
     *     confidence: int,
     *     populated_count: int,
     *     field_confidence: array<string, int>,
     *     message: string
     * }
     */
    private function normalizeExtraction(array $parsed): array
    {
        $fields = is_array($parsed['fields'] ?? null) ? $parsed['fields'] : [];
        $fieldConfidence = is_array($parsed['field_confidence'] ?? null) ? $parsed['field_confidence'] : [];
        $overallConfidence = (int) ($parsed['confidence'] ?? 0);

        $normalized = [];
        foreach ($fields as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $clean = $this->normalizeFieldValue($key, $value);
            if ($this->isEmptyValue($clean)) {
                continue;
            }

            $normalized[$key] = $clean;
        }

        $normalizedFieldConfidence = [];
        foreach ($fieldConfidence as $key => $score) {
            if (! is_string($key) || ! array_key_exists($key, $normalized)) {
                continue;
            }
            $normalizedFieldConfidence[$key] = max(0, min(100, (int) $score));
        }

        $populatedCount = count($normalized);
        if ($populatedCount === 0) {
            return [
                'fields' => [],
                'confidence' => max(0, $overallConfidence),
                'populated_count' => 0,
                'field_confidence' => [],
                'message' => 'Tiada medan dapat diekstrak daripada borang. Sila isi borang secara manual.',
            ];
        }

        if ($overallConfidence <= 0) {
            $scores = array_values($normalizedFieldConfidence);
            $overallConfidence = $scores !== [] ? (int) round(array_sum($scores) / count($scores)) : 70;
        }

        return [
            'fields' => $normalized,
            'confidence' => max(0, min(100, $overallConfidence)),
            'populated_count' => $populatedCount,
            'field_confidence' => $normalizedFieldConfidence,
            'message' => "AI-OCR berjaya mengekstrak {$populatedCount} medan (keyakinan {$overallConfidence}%). Sila semak sebelum hantar.",
        ];
    }

    private function normalizeFieldValue(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $amountFields = [
            'jumlah_permohonan', 'modal_berbayar', 'nilai_aset', 'jumlah_pembiayaan_sedia_ada',
            'baki_pembiayaan', 'wasiat_jumlah', 'anggaran_pendapatan', 'pendapatan', 'pendapatan_pasangan',
        ];

        if (is_numeric($value) && (str_contains($key, 'pendapatan') || in_array($key, $amountFields, true))) {
            return $this->normalizeAmount($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || strcasecmp($trimmed, 'null') === 0) {
            return null;
        }

        if (str_starts_with($key, 'no_ic')) {
            return $this->normalizeIc($trimmed);
        }

        if (in_array($key, [
            'oku', 'diberhentikan_pandemik', 'asnaf_berdaftar', 'pemegang_saham', 'keahlian_persatuan',
            'pengiktirafan', 'pembiayaan_sedia_ada', 'takaful_pembiayaan', 'takaful_kemalangan',
            'perkeso', 'wasiat', 'kebenaran_kredit',
        ], true)) {
            return $this->normalizeBoolean($trimmed);
        }

        if (str_contains($key, 'pendapatan') || in_array($key, [
            'jumlah_permohonan', 'modal_berbayar', 'nilai_aset', 'jumlah_pembiayaan_sedia_ada',
            'baki_pembiayaan', 'wasiat_jumlah', 'anggaran_pendapatan',
        ], true)) {
            return $this->normalizeAmount($trimmed);
        }

        if ($key === 'jantina') {
            $lower = strtolower($trimmed);
            if (in_array($lower, ['l', 'lelaki', 'male'], true)) {
                return 'L';
            }
            if (in_array($lower, ['p', 'wanita', 'perempuan', 'female'], true)) {
                return 'P';
            }
        }

        if ($key === 'agama') {
            return strtolower(str_replace(' ', '_', $trimmed));
        }

        if ($key === 'kaedah_perniagaan') {
            return strtolower($trimmed) === 'online' ? 'online' : 'offline';
        }

        if ($key === 'status_pekerjaan') {
            $lower = strtolower(str_replace(' ', '_', $trimmed));

            return $lower === 'bekerja' ? 'bekerja' : 'tidak_bekerja';
        }

        if ($key === 'bangsa') {
            return $this->normalizeSelectOption($trimmed, config('sppt-reference-data.bangsaOptions', []));
        }

        if ($key === 'taraf_perkahwinan') {
            $mapped = str_ireplace(['ibu tunggal', 'ibu_tunggal'], 'Janda', $trimmed);

            return $this->normalizeSelectOption($mapped, config('sppt-reference-data.tarafPerkahwinanOptions', []));
        }

        if (in_array($key, ['status_kediaman', 'status_premis'], true)) {
            return $this->normalizeSelectOption($trimmed, config('sppt-reference-data.statusKediamanOptions', []));
        }

        if (in_array($key, ['tarikh_daftar', 'tarikh_tamat_lesen'], true)) {
            return $this->normalizeDate($trimmed);
        }

        return $trimmed;
    }

    private function normalizeSelectOption(string $value, array $options): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        foreach ($options as $option) {
            if (! is_string($option)) {
                continue;
            }
            if (strcasecmp($trimmed, $option) === 0) {
                return $option;
            }
        }

        return $trimmed;
    }

    private function normalizeIc(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) === 12) {
            return substr($digits, 0, 6).'-'.substr($digits, 6, 2).'-'.substr($digits, 8, 4);
        }

        return $value;
    }

    private function normalizeAmount(mixed $value): string
    {
        $raw = is_numeric($value) ? (string) $value : preg_replace('/[^\d.]/', '', (string) $value) ?? '';
        if ($raw === '' || ! is_numeric($raw)) {
            return '';
        }

        if (str_contains($raw, '.')) {
            return rtrim(rtrim(number_format((float) $raw, 2, '.', ''), '0'), '.');
        }

        return $raw;
    }

    private function normalizeBoolean(string $value): bool
    {
        $lower = strtolower(trim($value));

        return in_array($lower, ['1', 'true', 'ya', 'yes', 'y', 'checked', '✓', 'x'], true);
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return $value !== '' ? $value : null;
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_bool($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }
}
