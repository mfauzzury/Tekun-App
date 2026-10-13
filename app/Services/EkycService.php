<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POC C3 — e-KYC: MyKad number validation, MyKad OCR, form cross-check, face match and liveness.
 */
class EkycService
{
    protected const NOT_CONFIGURED = 'API key AI belum dikonfigurasi';

    protected const PB_STATES = [
        '01' => 'Johor', '21' => 'Johor', '22' => 'Johor', '23' => 'Johor', '24' => 'Johor',
        '02' => 'Kedah', '25' => 'Kedah', '26' => 'Kedah', '27' => 'Kedah',
        '03' => 'Kelantan', '28' => 'Kelantan', '29' => 'Kelantan',
        '04' => 'Melaka', '30' => 'Melaka',
        '05' => 'Negeri Sembilan', '31' => 'Negeri Sembilan', '59' => 'Negeri Sembilan',
        '06' => 'Pahang', '32' => 'Pahang', '33' => 'Pahang',
        '07' => 'Pulau Pinang', '34' => 'Pulau Pinang', '35' => 'Pulau Pinang',
        '08' => 'Perak', '36' => 'Perak', '37' => 'Perak', '38' => 'Perak', '39' => 'Perak',
        '09' => 'Perlis', '40' => 'Perlis',
        '10' => 'Selangor', '41' => 'Selangor', '42' => 'Selangor', '43' => 'Selangor', '44' => 'Selangor',
        '11' => 'Terengganu', '45' => 'Terengganu', '46' => 'Terengganu',
        '12' => 'Sabah', '47' => 'Sabah', '48' => 'Sabah', '49' => 'Sabah',
        '13' => 'Sarawak', '50' => 'Sarawak', '51' => 'Sarawak', '52' => 'Sarawak', '53' => 'Sarawak',
        '14' => 'WP Kuala Lumpur', '54' => 'WP Kuala Lumpur', '55' => 'WP Kuala Lumpur', '56' => 'WP Kuala Lumpur', '57' => 'WP Kuala Lumpur',
        '15' => 'WP Labuan', '58' => 'WP Labuan',
        '16' => 'WP Putrajaya',
        '82' => 'Negeri tidak diketahui',
    ];

    protected const NAME_FILLERS = ['BIN', 'BINTI', 'BTE', 'BT', 'BNT', 'B'];

    protected const ADDRESS_ALIASES = [
        'JLN' => 'JALAN', 'JL' => 'JALAN', 'TMN' => 'TAMAN', 'KG' => 'KAMPUNG', 'KAMPONG' => 'KAMPUNG',
        'LRG' => 'LORONG', 'BDR' => 'BANDAR', 'SG' => 'SUNGAI',
        'KPG' => 'KAMPUNG', 'BT' => 'BATU', 'PRSN' => 'PERSIARAN', 'APT' => 'APARTMENT',
    ];

    protected const ADDRESS_FILLERS = ['NO', 'DAN'];

    public function __construct(protected AiClient $claude) {}

    /**
     * @return array{valid: bool, no_ic: string, tarikh_lahir: ?string, umur: ?int, jantina: ?string, kod_negeri: ?string, negeri_lahir: ?string, isu: list<string>}
     */
    public function validateIc(string $ic): array
    {
        $digits = preg_replace('/\D/', '', $ic) ?? '';
        $result = [
            'valid' => false,
            'no_ic' => $digits !== '' ? $digits : trim($ic),
            'tarikh_lahir' => null,
            'umur' => null,
            'jantina' => null,
            'kod_negeri' => null,
            'negeri_lahir' => null,
            'isu' => [],
        ];

        if (strlen($digits) !== 12 || preg_match('/^[\d\s-]+$/', trim($ic)) !== 1) {
            $result['isu'][] = 'Nombor MyKad mesti mengandungi 12 digit (format YYMMDD-PB-###G).';

            return $result;
        }

        $result['no_ic'] = $this->formatIc($digits);
        $valid = true;

        $yy = (int) substr($digits, 0, 2);
        $mm = (int) substr($digits, 2, 2);
        $dd = (int) substr($digits, 4, 2);
        $year = ($yy > (int) now()->format('y') ? 1900 : 2000) + $yy;

        if (! checkdate($mm, $dd, $year)) {
            $valid = false;
            $result['isu'][] = 'Tarikh lahir dalam nombor MyKad tidak sah.';
        } else {
            $dob = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $mm, $dd));
            $today = new DateTimeImmutable(now()->toDateString());
            if ($dob > $today) {
                $valid = false;
                $result['isu'][] = 'Tarikh lahir dalam nombor MyKad adalah pada masa hadapan.';
            } else {
                $age = $dob->diff($today)->y;
                $result['tarikh_lahir'] = $dob->format('Y-m-d');
                $result['umur'] = $age;
                if ($age < 18) {
                    $result['isu'][] = "Umur pemohon {$age} tahun — di bawah had minimum 18 tahun.";
                } elseif ($age > 75) {
                    $result['isu'][] = "Umur pemohon {$age} tahun — melebihi had maksimum 75 tahun.";
                }
            }
        }

        $pb = substr($digits, 6, 2);
        $state = $this->stateForPb($pb);
        if ($state === null) {
            $valid = false;
            $result['isu'][] = "Kod tempat lahir (PB) {$pb} tidak sah mengikut kod JPN.";
        } else {
            $result['kod_negeri'] = $pb;
            $result['negeri_lahir'] = $state;
        }

        $result['jantina'] = ((int) substr($digits, 11, 1)) % 2 === 1 ? 'L' : 'P';

        if ($this->isSuspiciousSerial(substr($digits, 8, 4))) {
            $result['isu'][] = 'Corak nombor siri luar biasa — kemungkinan identiti sintetik.';
        }

        $result['valid'] = $valid;

        return $result;
    }

    /**
     * @return array{
     *     ok: bool,
     *     sumber: 'ai'|'tiada',
     *     data: array{no_ic: ?string, nama: ?string, alamat: ?string, warganegara: ?string, agama: ?string, jantina: ?string},
     *     kualiti: array{skor: int, isu: list<string>},
     *     tanda_palsu: list<string>,
     *     adalah_mykad: bool,
     *     latency_ms: int,
     *     message: string
     * }
     */
    public function ocrMyKad(string $absolutePath, string $mime): array
    {
        $result = [
            'ok' => false,
            'sumber' => 'tiada',
            'data' => ['no_ic' => null, 'nama' => null, 'alamat' => null, 'warganegara' => null, 'agama' => null, 'jantina' => null],
            'kualiti' => ['skor' => 0, 'isu' => []],
            'tanda_palsu' => [],
            'adalah_mykad' => false,
            'latency_ms' => 0,
            'message' => '',
        ];

        if (! $this->claude->isConfigured()) {
            $result['message'] = self::NOT_CONFIGURED;

            return $result;
        }

        $system = 'Anda pakar pengesahan dokumen identiti Malaysia (e-KYC) untuk TEKUN Nasional. '
            .'Anda membaca imej bahagian DEPAN kad pengenalan MyKad dan menilai kualiti imej serta tanda pemalsuan. '
            .'Jangan sesekali mereka data: jika sesuatu medan tidak jelas atau tidak kelihatan, pulangkan null. '
            .'Semua teks penerangan mestilah dalam Bahasa Melayu.';

        $prompt = <<<'PROMPT'
Imej yang dilampirkan sepatutnya bahagian DEPAN MyKad. Baca medan yang kelihatan dan nilai keaslian imej.

Pulangkan JSON dengan bentuk TEPAT ini:
{
  "adalah_mykad": true|false,
  "data": {
    "no_ic": "YYMMDD-PB-###G atau null",
    "nama": "nama penuh seperti dicetak atau null",
    "alamat": "alamat penuh dalam satu baris atau null",
    "warganegara": "contoh WARGANEGARA atau null",
    "agama": "contoh ISLAM atau null",
    "jantina": "LELAKI atau PEREMPUAN atau null"
  },
  "kualiti": {
    "skor": 0-100,
    "isu": ["isu kualiti imej dalam Bahasa Melayu, contoh: imej kabur, silau, terpotong, gelap"]
  },
  "tanda_palsu": ["tanda pemalsuan dalam Bahasa Melayu, contoh: fon tidak konsisten, foto ditampal, ciri keselamatan/hologram tidak kelihatan, gambar skrin (corak moire), teks telah disunting"]
}

Peraturan:
- Salin teks tepat seperti pada kad. Medan yang tidak dapat dibaca = null. Jangan meneka.
- "tanda_palsu" kosong [] jika tiada tanda yang jelas. Senaraikan hanya tanda yang benar-benar kelihatan.
- Jika imej bukan MyKad, tetapkan "adalah_mykad": false dan semua medan data null.
PROMPT;

        try {
            $response = $this->claude->json($system, $prompt, [['path' => $absolutePath, 'mime' => $mime]], 2048);
        } catch (Throwable $e) {
            Log::warning('EkycService::ocrMyKad gagal', ['error' => $e->getMessage()]);
            $result['message'] = 'Analisis AI MyKad gagal: '.$e->getMessage();

            return $result;
        }

        $data = $response['data'];
        $fields = is_array($data['data'] ?? null) ? $data['data'] : [];
        foreach (array_keys($result['data']) as $key) {
            $result['data'][$key] = $this->nullableString($fields[$key] ?? null);
        }
        if ($result['data']['no_ic'] !== null) {
            $icDigits = preg_replace('/\D/', '', $result['data']['no_ic']) ?? '';
            if (strlen($icDigits) === 12) {
                $result['data']['no_ic'] = $this->formatIc($icDigits);
            }
        }

        $kualiti = is_array($data['kualiti'] ?? null) ? $data['kualiti'] : [];
        $result['kualiti'] = [
            'skor' => $this->score($kualiti['skor'] ?? 0),
            'isu' => $this->stringList($kualiti['isu'] ?? []),
        ];
        $result['tanda_palsu'] = $this->stringList($data['tanda_palsu'] ?? []);
        $result['adalah_mykad'] = (bool) ($data['adalah_mykad'] ?? false);
        $result['ok'] = true;
        $result['sumber'] = 'ai';
        $result['latency_ms'] = (int) $response['latency_ms'];
        $result['message'] = $result['adalah_mykad']
            ? 'MyKad berjaya dibaca.'
            : 'Imej tidak dikenal pasti sebagai MyKad.';

        return $result;
    }

    /**
     * @param  array<string, mixed>  $ocrData
     * @param  array<string, mixed>  $details
     * @return array{
     *     padanan: list<array{medan: string, label: string, nilai_borang: ?string, nilai_mykad: ?string, padan: bool, skor: int}>,
     *     skor_keseluruhan: int
     * }
     */
    public function compareWithApplication(array $ocrData, array $details): array
    {
        $fields = [
            ['medan' => 'no_ic', 'label' => 'No. MyKad', 'borang' => $details['no_ic_baru'] ?? null, 'weight' => 50],
            ['medan' => 'nama', 'label' => 'Nama', 'borang' => $details['nama'] ?? null, 'weight' => 35],
            ['medan' => 'alamat', 'label' => 'Alamat', 'borang' => $details['alamat'] ?? null, 'weight' => 15],
        ];

        $rows = [];
        $weighted = 0.0;
        $totalWeight = 0;

        foreach ($fields as $field) {
            $form = $this->nullableString($field['borang']);
            $card = $this->nullableString($ocrData[$field['medan']] ?? null);
            $score = 0;
            $match = false;

            if ($form !== null && $card !== null) {
                [$score, $match] = match ($field['medan']) {
                    'no_ic' => $this->compareIc($form, $card),
                    'nama' => $this->compareName($form, $card),
                    default => $this->compareAddress($form, $card),
                };
                $weighted += $score * $field['weight'];
                $totalWeight += $field['weight'];
            }

            $rows[] = [
                'medan' => $field['medan'],
                'label' => $field['label'],
                'nilai_borang' => $form,
                'nilai_mykad' => $card,
                'padan' => $match,
                'skor' => $score,
            ];
        }

        return [
            'padanan' => $rows,
            'skor_keseluruhan' => $totalWeight > 0 ? $this->score($weighted / $totalWeight) : 0,
        ];
    }

    /**
     * @param  list<array{base64: string, mime: string, arahan: string}>  $frames
     * @return array{
     *     ok: bool,
     *     padanan_wajah: int,
     *     liveness: int,
     *     tanda_deepfake: list<string>,
     *     cabaran: list<array{arahan: string, lulus: bool, catatan: string}>,
     *     keputusan: 'lulus'|'semak'|'gagal',
     *     ringkasan: string,
     *     latency_ms: int,
     *     message: string
     * }
     */
    public function faceAndLiveness(?string $mykadPath, ?string $mykadMime, array $frames): array
    {
        $frames = array_values(array_filter($frames, fn ($f) => is_array($f) && trim((string) ($f['base64'] ?? '')) !== ''));

        $result = [
            'ok' => false,
            'padanan_wajah' => 0,
            'liveness' => 0,
            'tanda_deepfake' => [],
            'cabaran' => array_map(fn (array $f) => [
                'arahan' => (string) ($f['arahan'] ?? ''),
                'lulus' => false,
                'catatan' => 'Tidak dinilai',
            ], $frames),
            'keputusan' => 'semak',
            'ringkasan' => '',
            'latency_ms' => 0,
            'message' => '',
        ];

        if ($frames === []) {
            $result['message'] = 'Tiada imej selfie';

            return $result;
        }

        if (! $this->claude->isConfigured()) {
            $result['message'] = self::NOT_CONFIGURED;

            return $result;
        }

        $hasMykad = $mykadPath !== null && $mykadPath !== '' && is_readable($mykadPath);
        $files = [];
        $labels = [];
        if ($hasMykad) {
            $files[] = ['path' => $mykadPath, 'mime' => $mykadMime ?: ((string) mime_content_type($mykadPath) ?: 'image/jpeg')];
            $labels[] = 'Imej 1: foto bahagian depan MyKad (potret rasmi pemohon).';
        }
        foreach ($frames as $i => $frame) {
            $files[] = [
                'base64' => preg_replace('/^data:[^,]*,/', '', (string) $frame['base64']) ?? '',
                'mime' => (string) ($frame['mime'] ?? 'image/jpeg'),
            ];
            $labels[] = sprintf(
                'Imej %d: bingkai selfie langsung #%d — arahan cabaran: "%s".',
                count($labels) + 1,
                $i + 1,
                (string) ($frame['arahan'] ?? ''),
            );
        }

        $system = 'Anda pakar e-KYC biometrik untuk TEKUN Nasional: padanan wajah, pengesanan liveness (anti-spoofing) dan pengesanan deepfake. '
            .'Bersikap konservatif — skor tinggi hanya jika bukti visual jelas. Semua teks penerangan dalam Bahasa Melayu.';

        $faceInstruction = $hasMykad
            ? '"padanan_wajah": 0-100, sejauh mana wajah dalam potret MyKad sepadan dengan wajah dalam bingkai selfie.'
            : '"padanan_wajah": 0 (tiada imej MyKad diberikan).';

        $prompt = "Imej dilampirkan mengikut susunan berikut:\n".implode("\n", $labels)."\n\n"
            ."Bingkai selfie dirakam secara langsung daripada kamera web semasa pemohon melaksanakan cabaran liveness aktif.\n\n"
            ."Nilai:\n"
            ."- {$faceInstruction}\n"
            ."- \"liveness\": 0-100, keyakinan bahawa bingkai ialah orang sebenar yang hadir secara langsung (bukan foto kepada foto, main semula skrin, cetakan atau topeng), dan identiti yang sama dalam semua bingkai.\n"
            ."- \"tanda_deepfake\": senarai tanda manipulasi/deepfake yang benar-benar kelihatan (contoh: tepi wajah kabur, pencahayaan tidak konsisten, corak moire skrin, bingkai telefon kelihatan, tekstur kulit tidak semula jadi). [] jika tiada.\n"
            ."- \"cabaran\": satu entri bagi setiap bingkai selfie mengikut susunan, menilai sama ada arahan dilaksanakan.\n"
            ."- \"ringkasan\": 1-2 ayat dalam Bahasa Melayu.\n\n"
            ."Pulangkan JSON dengan bentuk TEPAT ini:\n"
            .'{"padanan_wajah": 0, "liveness": 0, "tanda_deepfake": [], "cabaran": [{"arahan": "...", "lulus": true, "catatan": "..."}], "ringkasan": "..."}';

        try {
            $response = $this->claude->json($system, $prompt, $files, 2048);
        } catch (Throwable $e) {
            Log::warning('EkycService::faceAndLiveness gagal', ['error' => $e->getMessage()]);
            $result['message'] = 'Analisis AI wajah & liveness gagal: '.$e->getMessage();

            return $result;
        }

        $data = $response['data'];
        $modelChallenges = array_values(array_filter((array) ($data['cabaran'] ?? []), 'is_array'));

        $result['cabaran'] = [];
        foreach ($frames as $i => $frame) {
            $entry = $modelChallenges[$i] ?? null;
            $result['cabaran'][] = [
                'arahan' => (string) ($frame['arahan'] ?? ''),
                'lulus' => is_array($entry) && filter_var($entry['lulus'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'catatan' => is_array($entry) ? trim((string) ($entry['catatan'] ?? '')) : 'Tiada penilaian daripada model.',
            ];
        }

        $result['padanan_wajah'] = $hasMykad ? $this->score($data['padanan_wajah'] ?? 0) : 0;
        $result['liveness'] = $this->score($data['liveness'] ?? 0);
        $result['tanda_deepfake'] = $this->stringList($data['tanda_deepfake'] ?? []);
        $result['ringkasan'] = trim((string) ($data['ringkasan'] ?? ''));
        $result['keputusan'] = $this->decide($result['padanan_wajah'], $result['liveness'], $result['tanda_deepfake'], $result['cabaran'], $hasMykad);
        $result['ok'] = true;
        $result['latency_ms'] = (int) $response['latency_ms'];
        $result['message'] = $hasMykad
            ? 'Penilaian wajah & liveness selesai.'
            : 'Penilaian liveness selesai. Tiada imej MyKad — padanan wajah tidak dinilai.';

        return $result;
    }

    /**
     * @param  list<string>  $deepfakeSigns
     * @param  list<array{arahan: string, lulus: bool, catatan: string}>  $challenges
     * @return 'lulus'|'semak'|'gagal'
     */
    protected function decide(int $faceMatch, int $liveness, array $deepfakeSigns, array $challenges, bool $hasMykad): string
    {
        if ($liveness < 40 || ($hasMykad && $faceMatch < 50)) {
            return 'gagal';
        }

        $allChallengesPassed = $challenges !== [] && ! in_array(false, array_column($challenges, 'lulus'), true);

        if ($hasMykad && $faceMatch >= 80 && $liveness >= 75 && $deepfakeSigns === [] && $allChallengesPassed) {
            return 'lulus';
        }

        return 'semak';
    }

    protected function stateForPb(string $pb): ?string
    {
        if (isset(self::PB_STATES[$pb])) {
            return self::PB_STATES[$pb];
        }

        $code = (int) $pb;

        return $code >= 60 && $code <= 98 ? 'Lahir di luar negara' : null;
    }

    protected function isSuspiciousSerial(string $serial): bool
    {
        if (count(array_unique(str_split($serial))) === 1) {
            return true;
        }

        $digits = array_map('intval', str_split($serial));
        $ascending = true;
        $descending = true;
        for ($i = 1, $n = count($digits); $i < $n; $i++) {
            $ascending = $ascending && $digits[$i] === $digits[$i - 1] + 1;
            $descending = $descending && $digits[$i] === $digits[$i - 1] - 1;
        }

        return $ascending || $descending;
    }

    protected function formatIc(string $digits): string
    {
        return substr($digits, 0, 6).'-'.substr($digits, 6, 2).'-'.substr($digits, 8, 4);
    }

    /**
     * @return array{0: int, 1: bool}
     */
    protected function compareIc(string $form, string $card): array
    {
        $a = preg_replace('/\D/', '', $form) ?? '';
        $b = preg_replace('/\D/', '', $card) ?? '';
        $match = $a !== '' && $a === $b;

        return [$match ? 100 : 0, $match];
    }

    /**
     * @return array{0: int, 1: bool}
     */
    protected function compareName(string $form, string $card): array
    {
        $a = $this->normaliseName($form);
        $b = $this->normaliseName($card);
        if ($a === '' || $b === '') {
            return [0, false];
        }

        similar_text($a, $b, $percent);
        $score = $this->score($percent);

        return [$score, $score >= 85];
    }

    /**
     * @return array{0: int, 1: bool}
     */
    protected function compareAddress(string $form, string $card): array
    {
        $a = $this->addressTokens($form);
        $b = $this->addressTokens($card);
        if ($a === [] || $b === []) {
            return [0, false];
        }

        $common = count(array_intersect($a, $b));
        $score = $this->score(200 * $common / (count($a) + count($b)));

        return [$score, $score >= 60];
    }

    protected function normaliseName(string $name): string
    {
        $name = mb_strtoupper($name);
        $name = preg_replace('/\bA\s*\/\s*[LP]\b/u', ' ', $name) ?? $name;
        $name = preg_replace('/[^\p{L}\p{N}@\s]/u', ' ', $name) ?? $name;
        $tokens = preg_split('/\s+/', trim($name)) ?: [];
        $tokens = array_filter($tokens, fn (string $t) => $t !== '' && ! in_array($t, self::NAME_FILLERS, true));

        return implode(' ', $tokens);
    }

    /**
     * @return list<string>
     */
    protected function addressTokens(string $address): array
    {
        $address = mb_strtoupper($address);
        $address = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $address) ?? $address;
        $tokens = [];
        foreach (preg_split('/\s+/', trim($address)) ?: [] as $token) {
            if ($token === '' || in_array($token, self::ADDRESS_FILLERS, true)) {
                continue;
            }
            $tokens[] = self::ADDRESS_ALIASES[$token] ?? $token;
        }

        return array_values(array_unique($tokens));
    }

    protected function score(mixed $value): int
    {
        return is_numeric($value) ? max(0, min(100, (int) round((float) $value))) : 0;
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' || strtolower($value) === 'null' ? null : $value;
    }

    /**
     * @return list<string>
     */
    protected function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $value),
            fn (string $item) => $item !== '',
        ));
    }
}
