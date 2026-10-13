<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\Permohonan;
use App\Models\Usahawan;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * POC C4 — Disbursement Alert: link analysis across applications to detect repeat
 * applications, identity reuse and syndicate networks before funds are released.
 */
class FraudNetworkService
{
    protected const WEIGHTS = ['tinggi' => 35, 'sederhana' => 15, 'rendah' => 5];

    protected const MAX_NODES = 40;

    protected const NAME_SIMILAR_PCT = 85.0;

    protected const ADDRESS_SIMILARITY = 0.8;

    protected const INACTIVE_AKAUN = ['Selesai', 'Ditutup', 'Tamat'];

    protected const ATTR_LABELS = [
        'ic' => 'ic',
        'telefon' => 'telefon',
        'alamat' => 'alamat',
        'emel' => 'emel',
        'akaun_bank' => 'akaun bank',
    ];

    protected const ADDRESS_ABBREVIATIONS = [
        'JLN' => 'JALAN',
        'JL' => 'JALAN',
        'TMN' => 'TAMAN',
        'KG' => 'KAMPUNG',
        'KPG' => 'KAMPUNG',
        'KAMPONG' => 'KAMPUNG',
        'LRG' => 'LORONG',
        'BDR' => 'BANDAR',
        'SG' => 'SUNGAI',
        'SEK' => 'SEKSYEN',
        'PSN' => 'PERSIARAN',
        'PRSN' => 'PERSIARAN',
        'APT' => 'APARTMENT',
        'KMPLKS' => 'KOMPLEKS',
    ];

    /** @var array<string, string> raw display values per attribute key */
    protected array $displayValues = [];

    public function __construct(
        protected AiClient $claude,
    ) {}

    /**
     * @return array{
     *     skor_risiko: int,
     *     tahap: 'rendah'|'sederhana'|'tinggi',
     *     amaran: bool,
     *     isyarat: list<array{kod: string, tahap: string, tajuk: string, penerangan: string, bukti: list<string>}>,
     *     rangkaian: array{nodes: list<array<string, mixed>>, edges: list<array{dari: string, ke: string, label: string}>},
     *     kluster: array{saiz: int, permohonan_berkaitan: list<array{id: int, no_rujukan: string, nama: string, status: string, kaitan: list<string>}>},
     *     cadangan_tindakan: list<string>,
     *     dianalisis_pada: string
     * }
     */
    public function analyze(Permohonan $permohonan): array
    {
        $this->displayValues = [];
        $self = $this->profileFromPermohonan($permohonan);
        $entities = $this->loadEntities($permohonan);
        $this->assignAddressKeys($self, $entities);

        $index = [];
        foreach ($entities as $key => $entity) {
            foreach ($entity['keys'] as $attrKey) {
                $index[$attrKey][] = $key;
            }
        }

        // Direct links (depth 1).
        $direct = [];
        foreach ($self['keys'] as $attrKey) {
            foreach ($index[$attrKey] ?? [] as $entityKey) {
                $direct[$entityKey][] = $attrKey;
            }
        }

        // Depth 2 via any attribute of direct neighbours.
        $indirect = [];
        foreach (array_keys($direct) as $entityKey) {
            foreach ($entities[$entityKey]['keys'] as $attrKey) {
                foreach ($index[$attrKey] ?? [] as $otherKey) {
                    if (! isset($direct[$otherKey])) {
                        $indirect[$otherKey][$attrKey] = $entityKey;
                    }
                }
            }
        }

        $signals = $this->buildSignals($self, $entities, $direct, $indirect, $permohonan);

        $score = 0;
        foreach ($signals as $signal) {
            $score += self::WEIGHTS[$signal['tahap']];
        }
        $score = min(100, $score);
        $tahap = $score >= 60 ? 'tinggi' : ($score >= 25 ? 'sederhana' : 'rendah');
        $hasTinggi = collect($signals)->contains(fn ($s) => $s['tahap'] === 'tinggi');
        $amaran = $tahap !== 'rendah' || $hasTinggi;

        $related = $this->relatedApplications($entities, $direct, $indirect);

        return [
            'skor_risiko' => $score,
            'tahap' => $tahap,
            'amaran' => $amaran,
            'isyarat' => $signals,
            'rangkaian' => $this->buildGraph($self, $entities, $direct, $indirect, $tahap),
            'kluster' => [
                'saiz' => 1 + count($direct) + count($indirect),
                'permohonan_berkaitan' => $related,
            ],
            'cadangan_tindakan' => $this->recommendations($signals, $related, $amaran),
            'dianalisis_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{boleh_salur: bool, amaran: bool, tahap: string, sebab: list<string>, analisis: array<string, mixed>, disemak_pada: string}
     */
    public function disbursementCheck(Permohonan $permohonan): array
    {
        $analisis = $this->analyze($permohonan);

        $sebab = [];
        foreach ($analisis['isyarat'] as $signal) {
            if (in_array($signal['tahap'], ['sederhana', 'tinggi'], true)) {
                $sebab[] = $signal['tajuk'];
            }
        }

        return [
            'boleh_salur' => ! $analisis['amaran'],
            'amaran' => $analisis['amaran'],
            'tahap' => $analisis['tahap'],
            'sebab' => $sebab,
            'analisis' => $analisis,
            'disemak_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{ok: bool, skor_keaslian: int, tanda_palsu: list<string>, semakan_aritmetik: array{lulus: bool, catatan: string}, keputusan: 'asli'|'meragukan'|'palsu', ringkasan: string, latency_ms: int, message: string}
     */
    public function analyzeSalesDocument(string $absolutePath, string $mime): array
    {
        $fallback = fn (string $message, int $latency = 0) => [
            'ok' => false,
            'skor_keaslian' => 0,
            'tanda_palsu' => [],
            'semakan_aritmetik' => ['lulus' => false, 'catatan' => 'Semakan aritmetik tidak dijalankan.'],
            'keputusan' => 'meragukan',
            'ringkasan' => 'Analisis forensik dokumen tidak dapat dijalankan. Sila semak dokumen secara manual.',
            'latency_ms' => $latency,
            'message' => $message,
        ];

        if (! $this->claude->isConfigured()) {
            return $fallback('Penyedia AI tidak dikonfigurasi. Semakan dokumen jualan perlu dibuat secara manual.');
        }

        if (! is_readable($absolutePath)) {
            return $fallback('Fail dokumen tidak dapat dibaca.');
        }

        $system = 'Anda pakar forensik dokumen untuk TEKUN Nasional (pembiayaan mikro usahawan). '
            .'Tugas anda menilai sama ada dokumen jualan (resit, invois, penyata e-wallet, penyata jualan QR) kelihatan asli atau telah dipalsukan/disunting. '
            .'Periksa: ketidakselarasan fon, saiz atau penjajaran teks; jumlah aritmetik yang tidak tepat (item x kuantiti, subjumlah, cukai, jumlah besar); '
            .'tarikh/masa mustahil atau tidak konsisten; ID transaksi berulang; kawasan yang dipotong, ditampal atau disunting; '
            .'templat atau logo yang tidak sepadan dengan penerbit sebenar; baki atau urutan transaksi yang tidak logik. '
            .'Jangan reka butiran yang tidak kelihatan dalam dokumen.';

        $prompt = 'Analisis dokumen jualan yang dilampirkan. Pulangkan JSON dengan kunci: '
            .'"skor_keaslian" (integer 0-100, 100 = kelihatan asli sepenuhnya), '
            .'"tanda_palsu" (senarai rentetan Bahasa Melayu, setiap tanda pemalsuan yang dikesan; senarai kosong jika tiada), '
            .'"semakan_aritmetik" (objek {"lulus": boolean, "catatan": rentetan Bahasa Melayu}), '
            .'"ringkasan" (1-2 ayat Bahasa Melayu untuk pegawai).';

        try {
            $result = $this->claude->json($system, $prompt, [['path' => $absolutePath, 'mime' => $mime]], 2048);
        } catch (Throwable $e) {
            return $fallback('Analisis AI gagal: '.$e->getMessage());
        }

        $data = $result['data'];
        $score = max(0, min(100, (int) round((float) ($data['skor_keaslian'] ?? 0))));
        $tanda = array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            is_array($data['tanda_palsu'] ?? null) ? $data['tanda_palsu'] : [],
        ), fn (string $item) => $item !== ''));
        $arith = is_array($data['semakan_aritmetik'] ?? null) ? $data['semakan_aritmetik'] : [];

        return [
            'ok' => true,
            'skor_keaslian' => $score,
            'tanda_palsu' => $tanda,
            'semakan_aritmetik' => [
                'lulus' => filter_var($arith['lulus'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'catatan' => (string) ($arith['catatan'] ?? ''),
            ],
            'keputusan' => $score >= 75 ? 'asli' : ($score >= 45 ? 'meragukan' : 'palsu'),
            'ringkasan' => (string) ($data['ringkasan'] ?? ''),
            'latency_ms' => (int) $result['latency_ms'],
            'message' => 'Analisis forensik dokumen selesai ('.$result['model'].').',
        ];
    }

    // ---------------------------------------------------------------------
    // Entity loading
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function loadEntities(Permohonan $self): array
    {
        $entities = [];
        $linkedUsahawan = $self->usahawan_id ? [(int) $self->usahawan_id => true] : [];

        $query = Permohonan::query()
            ->select(['id', 'no_rujukan', 'usahawan_id', 'nama', 'status', 'details', 'created_at', 'pemohon_email', 'pemohon_telefon'])
            ->where('id', '!=', $self->id)
            ->where(fn ($q) => $q->where('status', '!=', 'Draf')->orWhereNull('status'));

        foreach ($query->cursor() as $row) {
            $profile = $this->profileFromPermohonan($row);
            $entities[$profile['key']] = $profile;
            if ($row->usahawan_id) {
                $linkedUsahawan[(int) $row->usahawan_id] = true;
            }
        }

        $usahawanQuery = Usahawan::query()->select(['id', 'no_usahawan', 'nama', 'no_ic', 'alamat', 'poskod', 'no_telefon', 'email', 'status']);
        foreach ($usahawanQuery->cursor() as $row) {
            if (isset($linkedUsahawan[(int) $row->id])) {
                continue;
            }
            $profile = $this->profile(
                'u-'.$row->id,
                'usahawan',
                null,
                (string) ($row->no_usahawan ?? ''),
                (string) $row->nama,
                (string) ($row->status ?? ''),
                null,
                (string) ($row->no_ic ?? ''),
                [(string) ($row->no_telefon ?? '')],
                [(string) ($row->email ?? '')],
                (string) ($row->alamat ?? ''),
                (string) ($row->poskod ?? ''),
                '',
            );
            $entities[$profile['key']] = $profile;
        }

        return $entities;
    }

    /**
     * @return array<string, mixed>
     */
    protected function profileFromPermohonan(Permohonan $row): array
    {
        $details = is_array($row->details) ? $row->details : [];

        return $this->profile(
            'p-'.$row->id,
            'permohonan',
            (int) $row->id,
            (string) ($row->no_rujukan ?? ''),
            (string) ($details['nama'] ?? $row->nama ?? ''),
            (string) ($row->status ?? ''),
            $row->created_at ? Carbon::parse($row->created_at) : null,
            (string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? ''),
            [(string) ($details['no_telefon_bimbit'] ?? $details['no_telefon'] ?? ''), (string) ($row->pemohon_telefon ?? '')],
            [(string) ($details['email'] ?? ''), (string) ($row->pemohon_email ?? '')],
            (string) ($details['alamat'] ?? ''),
            (string) ($details['poskod'] ?? ''),
            (string) ($details['no_akaun_bank'] ?? ''),
        );
    }

    /**
     * @param  list<string>  $phones
     * @param  list<string>  $emails
     * @return array<string, mixed>
     */
    protected function profile(
        string $key,
        string $source,
        ?int $permohonanId,
        string $reference,
        string $name,
        string $status,
        ?Carbon $createdAt,
        string $ic,
        array $phones,
        array $emails,
        string $address,
        string $poskod,
        string $bank,
    ): array {
        $keys = [];

        $normIc = $this->normalizeIc($ic);
        if (strlen($normIc) >= 6) {
            $keys[] = 'ic:'.$normIc;
            $this->displayValues['ic:'.$normIc] ??= $this->maskIc($normIc);
        }

        foreach (array_unique(array_filter(array_map(fn ($p) => $this->normalizePhone($p), $phones))) as $phone) {
            if (strlen($phone) >= 7) {
                $keys[] = 'telefon:'.$phone;
                $this->displayValues['telefon:'.$phone] ??= $this->maskPhone($phone);
            }
        }

        foreach (array_unique(array_filter(array_map(fn ($e) => $this->normalizeEmail($e), $emails))) as $email) {
            if (str_contains($email, '@')) {
                $keys[] = 'emel:'.$email;
                $this->displayValues['emel:'.$email] ??= $this->maskEmail($email);
            }
        }

        $normBank = preg_replace('/\D+/', '', $bank) ?? '';
        if (strlen($normBank) >= 6) {
            $keys[] = 'akaun_bank:'.$normBank;
            $this->displayValues['akaun_bank:'.$normBank] ??= '****'.substr($normBank, -4);
        }

        $normAddress = $this->normalizeAddress($address);
        $normPoskod = preg_replace('/\D+/', '', $poskod) ?? '';

        return [
            'key' => $key,
            'source' => $source,
            'permohonan_id' => $permohonanId,
            'no_rujukan' => $reference,
            'nama' => trim($name),
            'status' => $status,
            'created_at' => $createdAt,
            'ic' => $normIc,
            'keys' => $keys,
            'address' => $normAddress,
            'address_raw' => trim($address),
            'poskod' => $normPoskod,
        ];
    }

    /**
     * Assign an 'alamat:n' key per fuzzy address group (exact match, or same poskod + similar tokens).
     *
     * @param  array<string, mixed>  $self
     * @param  array<string, array<string, mixed>>  $entities
     */
    protected function assignAddressKeys(array &$self, array &$entities): void
    {
        $reps = [];
        $assign = function (array &$profile) use (&$reps): void {
            if ($profile['address'] === '') {
                return;
            }
            foreach ($reps as $i => $rep) {
                if ($this->addressesMatch($profile, $rep)) {
                    $profile['keys'][] = 'alamat:'.$i;

                    return;
                }
            }
            $i = count($reps);
            $reps[] = $profile;
            $profile['keys'][] = 'alamat:'.$i;
            $label = $profile['address_raw'].($profile['poskod'] !== '' ? ', '.$profile['poskod'] : '');
            $this->displayValues['alamat:'.$i] = mb_strlen($label) > 40 ? mb_substr($label, 0, 39).'…' : $label;
        };

        $assign($self);
        foreach ($entities as &$entity) {
            $assign($entity);
        }
        unset($entity);
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    protected function addressesMatch(array $a, array $b): bool
    {
        $fullA = trim($a['address'].' '.$a['poskod']);
        $fullB = trim($b['address'].' '.$b['poskod']);
        if ($fullA === $fullB) {
            return true;
        }

        if ($a['poskod'] === '' || $a['poskod'] !== $b['poskod']) {
            return false;
        }

        $tokensA = array_unique(explode(' ', $a['address']));
        $tokensB = array_unique(explode(' ', $b['address']));

        // Different house/lot numbers on the same street are neighbours, not a shared address.
        $numbersA = array_values(array_filter($tokensA, 'ctype_digit'));
        $numbersB = array_values(array_filter($tokensB, 'ctype_digit'));
        sort($numbersA);
        sort($numbersB);
        if ($numbersA !== $numbersB) {
            return false;
        }

        $common = count(array_intersect($tokensA, $tokensB));
        $total = count($tokensA) + count($tokensB);

        return $total > 0 && (2 * $common / $total) >= self::ADDRESS_SIMILARITY;
    }

    // ---------------------------------------------------------------------
    // Signals
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $self
     * @param  array<string, array<string, mixed>>  $entities
     * @param  array<string, list<string>>  $direct
     * @param  array<string, array<string, string>>  $indirect
     * @return list<array{kod: string, tahap: string, tajuk: string, penerangan: string, bukti: list<string>}>
     */
    protected function buildSignals(array $self, array $entities, array $direct, array $indirect, Permohonan $permohonan): array
    {
        $signals = [];

        // Identity: IC reuse vs repeat application.
        $reused = [];
        $repeat = [];
        if ($self['ic'] !== '') {
            $since = now()->subMonths(12);
            foreach ($direct as $entityKey => $attrKeys) {
                if (! in_array('ic:'.$self['ic'], $attrKeys, true)) {
                    continue;
                }
                $other = $entities[$entityKey];
                if ($this->nameSimilarity($self['nama'], $other['nama']) < self::NAME_SIMILAR_PCT) {
                    $reused[] = $this->evidence($other);
                } elseif ($other['source'] === 'permohonan' && $other['created_at'] && $other['created_at']->gte($since)) {
                    $repeat[] = $this->evidence($other);
                }
            }

            $activeAkaun = [];
            $akaunRows = AkaunPembiayaan::query()
                ->select(['id', 'no_akaun', 'ic', 'nama', 'status', 'permohonan_id'])
                ->whereNotNull('ic')
                ->cursor();
            foreach ($akaunRows as $akaun) {
                if ($this->normalizeIc((string) $akaun->ic) !== $self['ic'] || (int) $akaun->permohonan_id === (int) $permohonan->id) {
                    continue;
                }
                $label = 'Akaun '.$akaun->no_akaun.' · '.$akaun->nama.' ('.$akaun->status.')';
                if ($this->nameSimilarity($self['nama'], (string) $akaun->nama) < self::NAME_SIMILAR_PCT) {
                    $reused[] = $label;
                } elseif (! in_array($akaun->status, self::INACTIVE_AKAUN, true)) {
                    $activeAkaun[] = $label;
                }
            }

            if ($reused !== []) {
                $signals[] = $this->signal(
                    'IC_GUNA_SEMULA',
                    'tinggi',
                    'MyKad digunakan oleh pemohon lain',
                    'No. MyKad pemohon ini pernah digunakan oleh individu dengan nama berbeza — kemungkinan kecurian identiti atau identiti sintetik.',
                    $reused,
                );
            }

            if ($repeat !== [] || $activeAkaun !== []) {
                $signals[] = $this->signal(
                    'PERMOHONAN_BERULANG',
                    (count($repeat) >= 2 || $activeAkaun !== []) ? 'tinggi' : 'sederhana',
                    'Permohonan berulang',
                    $activeAkaun !== []
                        ? 'Pemohon yang sama telah memohon dalam tempoh 12 bulan atau masih mempunyai akaun pembiayaan aktif.'
                        : 'Pemohon yang sama telah mengemukakan permohonan lain dalam tempoh 12 bulan lepas.',
                    array_merge($repeat, $activeAkaun),
                );
            }
        }

        $shared = fn (string $type) => $this->sharedWithDifferentIc($self, $entities, $direct, $type);

        $phone = $shared('telefon');
        if ($phone['count'] > 0) {
            $signals[] = $this->signal(
                'TELEFON_DIKONGSI',
                $phone['count'] >= 2 ? 'tinggi' : 'sederhana',
                'Nombor telefon dikongsi',
                sprintf('Nombor telefon pemohon turut digunakan oleh %d pemohon lain dengan MyKad berbeza.', $phone['count']),
                $phone['bukti'],
            );
        }

        $address = $shared('alamat');
        if ($address['count'] > 0) {
            $signals[] = $this->signal(
                'ALAMAT_DIKONGSI',
                $address['count'] >= 3 ? 'tinggi' : 'sederhana',
                'Alamat dikongsi',
                sprintf('Alamat kediaman pemohon sama dengan %d pemohon lain yang menggunakan MyKad berbeza.', $address['count']),
                $address['bukti'],
            );
        }

        $email = $shared('emel');
        if ($email['count'] > 0) {
            $signals[] = $this->signal(
                'EMEL_DIKONGSI',
                'sederhana',
                'E-mel dikongsi',
                sprintf('Alamat e-mel pemohon turut digunakan oleh %d pemohon lain dengan MyKad berbeza.', $email['count']),
                $email['bukti'],
            );
        }

        $bank = $shared('akaun_bank');
        if ($bank['count'] > 0) {
            $signals[] = $this->signal(
                'AKAUN_BANK_DIKONGSI',
                'tinggi',
                'Akaun bank dikongsi (money mule)',
                'Akaun bank penerima dana turut didaftarkan oleh pemohon lain dengan MyKad berbeza — kemungkinan akaun keldai (money mule).',
                $bank['bukti'],
            );
        }

        $clusterKeys = array_merge(array_keys($direct), array_keys($indirect));
        $clusterSize = 1 + count($clusterKeys);
        if ($clusterSize >= 4) {
            $signals[] = $this->signal(
                'KLUSTER_SINDIKET',
                'tinggi',
                'Corak sindiket / rangkaian dikesan',
                sprintf('Pemohon berada dalam rangkaian %d pemohon yang saling berkongsi MyKad, telefon, alamat, e-mel atau akaun bank.', $clusterSize),
                array_map(fn ($k) => $this->evidence($entities[$k]), array_slice($clusterKeys, 0, 10)),
            );
        }

        $dates = [];
        if ($permohonan->created_at) {
            $dates[] = Carbon::parse($permohonan->created_at);
        }
        foreach ($clusterKeys as $k) {
            if ($entities[$k]['source'] === 'permohonan' && $entities[$k]['created_at']) {
                $dates[] = $entities[$k]['created_at'];
            }
        }
        usort($dates, fn (Carbon $a, Carbon $b) => $a <=> $b);
        $maxInWindow = 0;
        for ($i = 0, $j = 0, $n = count($dates); $j < $n; $j++) {
            while ($dates[$i]->diffInDays($dates[$j], true) > 30) {
                $i++;
            }
            $maxInWindow = max($maxInWindow, $j - $i + 1);
        }
        if ($maxInWindow >= 3) {
            $signals[] = $this->signal(
                'HALAJU_PERMOHONAN',
                'sederhana',
                'Halaju permohonan luar biasa',
                sprintf('%d permohonan dalam rangkaian yang sama dihantar dalam tempoh 30 hari.', $maxInWindow),
                [],
            );
        }

        return $signals;
    }

    /**
     * @param  array<string, mixed>  $self
     * @param  array<string, array<string, mixed>>  $entities
     * @param  array<string, list<string>>  $direct
     * @return array{count: int, bukti: list<string>}
     */
    protected function sharedWithDifferentIc(array $self, array $entities, array $direct, string $type): array
    {
        $identities = [];
        $bukti = [];
        foreach ($direct as $entityKey => $attrKeys) {
            $match = array_filter($attrKeys, fn ($k) => str_starts_with($k, $type.':'));
            if ($match === []) {
                continue;
            }
            $other = $entities[$entityKey];
            if ($self['ic'] !== '' && $other['ic'] === $self['ic']) {
                continue;
            }
            $identities[$other['ic'] !== '' ? $other['ic'] : $entityKey] = true;
            $bukti[] = $this->evidence($other);
        }

        return ['count' => count($identities), 'bukti' => $bukti];
    }

    /**
     * @param  list<string>  $bukti
     * @return array{kod: string, tahap: string, tajuk: string, penerangan: string, bukti: list<string>}
     */
    protected function signal(string $kod, string $tahap, string $tajuk, string $penerangan, array $bukti): array
    {
        return [
            'kod' => $kod,
            'tahap' => $tahap,
            'tajuk' => $tajuk,
            'penerangan' => $penerangan,
            'bukti' => array_values(array_unique($bukti)),
        ];
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    protected function evidence(array $entity): string
    {
        $prefix = $entity['source'] === 'usahawan' ? 'Usahawan ' : '';

        return $prefix.($entity['no_rujukan'] !== '' ? $entity['no_rujukan'] : $entity['key']).' · '.$entity['nama'];
    }

    // ---------------------------------------------------------------------
    // Cluster, graph, recommendations
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, array<string, mixed>>  $entities
     * @param  array<string, list<string>>  $direct
     * @param  array<string, array<string, string>>  $indirect
     * @return list<array{id: int, no_rujukan: string, nama: string, status: string, kaitan: list<string>}>
     */
    protected function relatedApplications(array $entities, array $direct, array $indirect): array
    {
        $related = [];
        foreach ($direct as $entityKey => $attrKeys) {
            $entity = $entities[$entityKey];
            if ($entity['source'] !== 'permohonan') {
                continue;
            }
            $related[] = [
                'id' => (int) $entity['permohonan_id'],
                'no_rujukan' => $entity['no_rujukan'],
                'nama' => $entity['nama'],
                'status' => $entity['status'],
                'kaitan' => $this->attrTypes($attrKeys),
            ];
        }
        foreach ($indirect as $entityKey => $via) {
            $entity = $entities[$entityKey];
            if ($entity['source'] !== 'permohonan') {
                continue;
            }
            $related[] = [
                'id' => (int) $entity['permohonan_id'],
                'no_rujukan' => $entity['no_rujukan'],
                'nama' => $entity['nama'],
                'status' => $entity['status'],
                'kaitan' => array_map(fn ($t) => $t.' (tidak langsung)', $this->attrTypes(array_keys($via))),
            ];
        }

        return $related;
    }

    /**
     * @param  list<string>  $attrKeys
     * @return list<string>
     */
    protected function attrTypes(array $attrKeys): array
    {
        $types = [];
        foreach ($attrKeys as $key) {
            $types[self::ATTR_LABELS[strstr($key, ':', true)] ?? $key] = true;
        }

        return array_keys($types);
    }

    /**
     * @param  array<string, mixed>  $self
     * @param  array<string, array<string, mixed>>  $entities
     * @param  array<string, list<string>>  $direct
     * @param  array<string, array<string, string>>  $indirect
     * @return array{nodes: list<array<string, mixed>>, edges: list<array{dari: string, ke: string, label: string}>}
     */
    protected function buildGraph(array $self, array $entities, array $direct, array $indirect, string $selfTahap): array
    {
        if ($direct === [] && $indirect === []) {
            return [
                'nodes' => [$this->pemohonNode($self, true, $selfTahap)],
                'edges' => [],
            ];
        }

        // Members in priority order: self, direct, indirect.
        $members = ['self' => $self];
        foreach (array_keys($direct) as $k) {
            $members[$k] = $entities[$k];
        }
        foreach (array_keys($indirect) as $k) {
            $members[$k] = $entities[$k];
        }

        // Attribute keys shared by at least two members.
        $holders = [];
        foreach ($members as $memberKey => $member) {
            foreach ($member['keys'] as $attrKey) {
                $holders[$attrKey][] = $memberKey;
            }
        }
        $holders = array_filter($holders, fn ($list) => count($list) >= 2);

        $nodes = [];
        $edges = [];
        $nodeIds = [];
        $addNode = function (array $node) use (&$nodes, &$nodeIds): bool {
            if (isset($nodeIds[$node['id']])) {
                return true;
            }
            if (count($nodes) >= self::MAX_NODES) {
                return false;
            }
            $nodes[] = $node;
            $nodeIds[$node['id']] = true;

            return true;
        };

        foreach ($members as $memberKey => $member) {
            $isSelf = $memberKey === 'self';
            $risk = $isSelf ? $selfTahap : $this->memberRisk($direct[$memberKey] ?? null);
            if (! $addNode($this->pemohonNode($member, $isSelf, $risk))) {
                break;
            }
        }

        foreach ($holders as $attrKey => $memberKeys) {
            $type = (string) strstr($attrKey, ':', true);
            $attrId = $type.'-'.substr(md5($attrKey), 0, 10);
            $present = array_filter($memberKeys, fn ($m) => isset($nodeIds[$this->memberNodeId($members[$m])]));
            if (count($present) < 2) {
                continue;
            }
            if (! $addNode([
                'id' => $attrId,
                'jenis' => $type,
                'label' => $this->displayValues[$attrKey] ?? $type,
                'utama' => false,
                'risiko' => null,
                'permohonan_id' => null,
                'status' => null,
            ])) {
                break;
            }
            foreach ($present as $m) {
                $edges[] = [
                    'dari' => $this->memberNodeId($members[$m]),
                    'ke' => $attrId,
                    'label' => self::ATTR_LABELS[$type] ?? $type,
                ];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * @param  list<string>|null  $directKeys
     */
    protected function memberRisk(?array $directKeys): string
    {
        if ($directKeys === null) {
            return 'rendah';
        }
        $types = $this->attrTypes($directKeys);

        return (count($types) >= 2 || array_intersect($types, ['ic', 'akaun bank']) !== []) ? 'tinggi' : 'sederhana';
    }

    /**
     * @param  array<string, mixed>  $member
     */
    protected function memberNodeId(array $member): string
    {
        return 'pemohon-'.$member['key'];
    }

    /**
     * @param  array<string, mixed>  $member
     * @return array<string, mixed>
     */
    protected function pemohonNode(array $member, bool $isSelf, ?string $risk): array
    {
        $label = $member['nama'] !== '' ? $member['nama'] : '(tiada nama)';
        if ($member['no_rujukan'] !== '') {
            $label .= ' ('.$member['no_rujukan'].')';
        }

        return [
            'id' => $this->memberNodeId($member),
            'jenis' => 'pemohon',
            'label' => $label,
            'utama' => $isSelf,
            'risiko' => $risk,
            'permohonan_id' => $member['permohonan_id'],
            'status' => $member['status'] !== '' ? $member['status'] : null,
        ];
    }

    /**
     * @param  list<array{kod: string, tahap: string}>  $signals
     * @param  list<array{no_rujukan: string}>  $related
     * @return list<string>
     */
    protected function recommendations(array $signals, array $related, bool $amaran): array
    {
        $codes = array_column($signals, 'kod');
        $has = fn (string $code) => in_array($code, $codes, true);

        if (! $amaran && $signals === []) {
            return ['Tiada corak mencurigakan dikesan — teruskan proses penilaian biasa.'];
        }

        $actions = [];
        if ($amaran) {
            $actions[] = 'Tahan pengeluaran dana sehingga pengesahan identiti bersemuka';
        }
        if ($has('IC_GUNA_SEMULA')) {
            $actions[] = 'Sahkan MyKad asal secara bersemuka (pembaca cip MyKad) dan semak dengan rekod JPN';
        }
        if ($has('ALAMAT_DIKONGSI')) {
            $actions[] = 'Sahkan alamat melalui lawatan pegawai lapangan';
        }
        if ($has('TELEFON_DIKONGSI') || $has('EMEL_DIKONGSI')) {
            $actions[] = 'Hubungi pemohon dan sahkan pemilikan nombor telefon / e-mel yang didaftarkan';
        }
        if ($has('AKAUN_BANK_DIKONGSI')) {
            $actions[] = 'Sahkan pemilikan akaun bank melalui penyata bank asal sebelum pembayaran';
        }
        if ($has('PERMOHONAN_BERULANG')) {
            $actions[] = 'Semak status permohonan dan akaun pembiayaan terdahulu sebelum meneruskan';
        }
        if ($has('KLUSTER_SINDIKET') || $has('IC_GUNA_SEMULA') || $has('AKAUN_BANK_DIKONGSI')) {
            $actions[] = 'Rujuk kepada Unit Integriti';
        }
        if ($related !== []) {
            $refs = array_slice(array_column($related, 'no_rujukan'), 0, 8);
            $actions[] = 'Semak permohonan berkaitan: '.implode(', ', $refs).(count($related) > 8 ? ' dan lain-lain' : '');
        }
        if (! $amaran) {
            $actions[] = 'Risiko rendah — teruskan proses dengan pemerhatian pegawai.';
        }

        return $actions;
    }

    // ---------------------------------------------------------------------
    // Normalisers & masking
    // ---------------------------------------------------------------------

    protected function normalizeIc(string $ic): string
    {
        return preg_replace('/\D+/', '', $ic) ?? '';
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '60') && strlen($digits) > 10) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    protected function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    protected function normalizeAddress(string $address): string
    {
        $text = strtoupper(trim($address));
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\bNO\s*\.?\s*(?=\d)/', 'NO ', $text) ?? $text;
        $text = preg_replace('/[^A-Z0-9]+/', ' ', $text) ?? $text;
        $tokens = array_filter(explode(' ', $text), fn ($t) => $t !== '');
        $tokens = array_map(fn ($t) => self::ADDRESS_ABBREVIATIONS[$t] ?? $t, $tokens);

        return implode(' ', $tokens);
    }

    protected function nameSimilarity(string $a, string $b): float
    {
        $norm = function (string $name): string {
            $name = strtoupper($name);
            $name = preg_replace('/\b(BIN|BINTI|BT|BTE|BN|A\/L|A\/P|AL|AP)\b/', ' ', $name) ?? $name;
            $name = preg_replace('/[^A-Z]+/', ' ', $name) ?? $name;

            return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        };

        $a = $norm($a);
        $b = $norm($b);
        if ($a === '' || $b === '') {
            return 100.0;
        }

        similar_text($a, $b, $pct);

        return (float) $pct;
    }

    protected function maskIc(string $ic): string
    {
        if (strlen($ic) === 12) {
            return substr($ic, 0, 6).'-**-**'.substr($ic, -2);
        }

        return substr($ic, 0, 2).str_repeat('*', max(0, strlen($ic) - 4)).substr($ic, -2);
    }

    protected function maskPhone(string $phone): string
    {
        return substr($phone, 0, 3).'-***'.substr($phone, -4);
    }

    protected function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return substr($local, 0, 2).'***@'.$domain;
    }
}
