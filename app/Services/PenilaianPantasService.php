<?php

namespace App\Services;

use App\Models\Permohonan;
use App\Models\PenilaianPantas;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * POC 1 — fast assessment pipeline: Hantar → e-KYC → hard rules → fraud network → alt-data credit score → routing.
 *
 * Steps run one request at a time so the officer screen can show each step's latency live.
 * The AI only recommends; approval/rejection is always an officer action (human-in-the-loop).
 */
class PenilaianPantasService
{
    public const LANGKAH = [
        'ekyc' => 'e-KYC & pengesahan identiti',
        'peraturan' => 'Semakan peraturan kelayakan',
        'penipuan' => 'Analisis rangkaian penipuan',
        'skor_kredit' => 'Skor kredit data alternatif',
        'cadangan' => 'Penghalaan & cadangan',
    ];

    public const SASARAN_MS = 7 * 60 * 1000;

    public function __construct(
        protected EkycService $ekyc,
        protected HardRuleCheckService $hardRules,
        protected FraudNetworkService $fraud,
        protected AlternativeCreditScoringService $creditScoring,
        protected CreditBureauSimulatorService $creditBureau,
    ) {}

    public function start(Permohonan $permohonan, ?int $userId, string $sumber = 'pegawai'): PenilaianPantas
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $dihantar = isset($details['dihantar_pada'])
            ? CarbonImmutable::parse((string) $details['dihantar_pada'])
            : null;

        $langkah = [];
        foreach (self::LANGKAH as $kod => $label) {
            $langkah[$kod] = ['kod' => $kod, 'label' => $label, 'status' => 'menunggu'];
        }

        return PenilaianPantas::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'berjalan',
            'langkah' => $langkah,
            'dihantar_pada' => $dihantar,
            'mula_pada' => now(),
            'sumber' => $sumber,
            'dimulakan_oleh' => $userId,
        ]);
    }

    /**
     * Execute one step and persist its timing/result. Steps must run in order.
     */
    public function runStep(PenilaianPantas $run, string $kod): PenilaianPantas
    {
        if (! array_key_exists($kod, self::LANGKAH)) {
            throw new RuntimeException("Langkah tidak dikenali: {$kod}");
        }
        if ($run->status === 'selesai') {
            throw new RuntimeException('Penilaian ini telah selesai. Mulakan penilaian baharu.');
        }

        $langkah = $run->langkah ?? [];
        foreach (array_keys(self::LANGKAH) as $sebelum) {
            if ($sebelum === $kod) {
                break;
            }
            if (($langkah[$sebelum]['status'] ?? null) !== 'selesai') {
                throw new RuntimeException('Langkah "'.self::LANGKAH[$sebelum].'" perlu dijalankan dahulu.');
            }
        }

        $permohonan = $run->permohonan()->firstOrFail();
        $mula = microtime(true);
        $mulaPada = now()->toIso8601String();

        try {
            $output = match ($kod) {
                'ekyc' => $this->stepEkyc($permohonan),
                'peraturan' => $this->stepPeraturan($permohonan, $langkah),
                'penipuan' => $this->stepPenipuan($permohonan),
                'skor_kredit' => $this->stepSkorKredit($permohonan),
                'cadangan' => $this->stepCadangan($permohonan, $langkah),
            };
            $status = 'selesai';
        } catch (\Throwable $e) {
            $output = ['keputusan' => 'gagal', 'ringkasan' => 'Ralat: '.$e->getMessage(), 'hasil' => null];
            $status = 'ralat';
        }

        $ms = (int) round((microtime(true) - $mula) * 1000);
        $langkah[$kod] = [
            'kod' => $kod,
            'label' => self::LANGKAH[$kod],
            'status' => $status,
            'keputusan' => $output['keputusan'],
            'ringkasan' => $output['ringkasan'],
            'hasil' => $output['hasil'],
            'mula_pada' => $mulaPada,
            'ms' => $ms,
        ];

        $update = ['langkah' => $langkah];

        if ($kod === 'cadangan' && $status === 'selesai') {
            $siap = now();
            $cadangan = $output['hasil'];
            $update += [
                'status' => 'selesai',
                'laluan' => $cadangan['laluan'],
                'tahap_risiko' => $cadangan['tahap_risiko'],
                'skor_kredit' => $cadangan['skor_kredit'],
                'cadangan' => $cadangan,
                'siap_pada' => $siap,
                'jumlah_ms' => (int) collect($langkah)->sum(fn ($l) => (int) ($l['ms'] ?? 0)),
                'hujung_ke_hujung_ms' => (int) (($run->dihantar_pada ?? $run->mula_pada)->diffInMilliseconds($siap, true)),
            ];
        }

        $run->update($update);

        return $run->fresh();
    }

    /**
     * Run every remaining step in one request (used by the applicant portal after Hantar).
     */
    public function runAll(PenilaianPantas $run): PenilaianPantas
    {
        foreach (array_keys(self::LANGKAH) as $kod) {
            if (($run->langkah[$kod]['status'] ?? null) === 'selesai') {
                continue;
            }
            $run = $this->runStep($run, $kod);
            if (($run->langkah[$kod]['status'] ?? null) !== 'selesai') {
                break;
            }
        }

        return $run;
    }

    /**
     * Live face match + liveness for the applicant; stored on the application for the e-KYC step.
     *
     * @param  list<array{base64: string, mime: string, arahan: string}>  $frames
     * @return array<string, mixed>
     */
    public function verifyFace(Permohonan $permohonan, array $frames): array
    {
        $mykad = $this->myKadFront($permohonan);
        $result = $this->ekyc->faceAndLiveness($mykad['path'] ?? null, $mykad['mime'] ?? null, $frames);
        $result['disemak_pada'] = now()->toIso8601String();

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $details['ekyc_wajah'] = $result;
        $permohonan->update(['details' => $details]);

        return $result;
    }

    /**
     * Re-run fraud checks right before an approval opens the financing account (Disbursement Alert).
     *
     * @return array<string, mixed>
     */
    public function disbursementCheck(Permohonan $permohonan): array
    {
        return $this->fraud->disbursementCheck($permohonan);
    }

    /**
     * @return array{keputusan: string, ringkasan: string, hasil: array<string, mixed>}
     */
    protected function stepEkyc(Permohonan $permohonan): array
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $ic = $this->ekyc->validateIc((string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? ''));

        $mykad = $this->myKadFront($permohonan);
        $ocr = null;
        $padanan = null;
        if ($mykad !== null) {
            $ocr = $this->ekyc->ocrMyKad($mykad['path'], $mykad['mime']);
            if ($ocr['ok'] ?? false) {
                $padanan = $this->ekyc->compareWithApplication($ocr['data'] ?? [], $details);
            }
        }

        $wajah = is_array($details['ekyc_wajah'] ?? null) ? $details['ekyc_wajah'] : null;

        $isu = $ic['isu'] ?? [];
        if ($mykad === null) {
            $isu[] = 'Tiada imej MyKad depan dilampirkan.';
        } elseif (! ($ocr['ok'] ?? false)) {
            $isu[] = 'OCR MyKad tidak berjaya: '.($ocr['message'] ?? '');
        }
        foreach ($ocr['tanda_palsu'] ?? [] as $tanda) {
            $isu[] = 'Tanda MyKad palsu: '.$tanda;
        }
        if ($padanan !== null && ($padanan['skor_keseluruhan'] ?? 0) < 85) {
            $isu[] = 'Maklumat MyKad tidak sepadan sepenuhnya dengan borang ('.$padanan['skor_keseluruhan'].'%).';
        }
        if ($wajah === null) {
            $isu[] = 'Pengesahan wajah & liveness belum dibuat.';
        } elseif (($wajah['keputusan'] ?? '') !== 'lulus') {
            $isu[] = 'Pengesahan wajah: '.($wajah['ringkasan'] ?? $wajah['keputusan'] ?? 'perlu semakan');
        }

        $gagal = ! ($ic['valid'] ?? false)
            || ($padanan !== null && ($padanan['skor_keseluruhan'] ?? 100) < 50)
            || (($wajah['keputusan'] ?? null) === 'gagal')
            || ! empty($ocr['tanda_palsu'] ?? []);
        $lulus = ! $gagal
            && ($padanan['skor_keseluruhan'] ?? 0) >= 85
            && (($wajah['keputusan'] ?? null) === 'lulus');

        $keputusan = $gagal ? 'gagal' : ($lulus ? 'lulus' : 'semak');

        return [
            'keputusan' => $keputusan,
            'ringkasan' => match ($keputusan) {
                'lulus' => 'Identiti disahkan: MyKad sah, sepadan dengan borang, wajah & liveness lulus.',
                'gagal' => 'Identiti gagal disahkan — '.($isu[0] ?? 'semak butiran.'),
                default => 'Identiti perlu semakan pegawai — '.($isu[0] ?? 'semak butiran.'),
            },
            'hasil' => [
                'ic' => $ic,
                'ocr' => $ocr,
                'padanan' => $padanan,
                'wajah' => $wajah,
                'isu' => array_values($isu),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $langkah
     * @return array{keputusan: string, ringkasan: string, hasil: array<string, mixed>}
     */
    protected function stepPeraturan(Permohonan $permohonan, array $langkah): array
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $pendapatan = (float) ($details['pendapatan'] ?? 0);
        $bulan = max(1, (int) ($details['pendapatan_bulan'] ?? 1));
        $umur = $langkah['ekyc']['hasil']['ic']['umur'] ?? (isset($details['umur']) ? (int) $details['umur'] : null);

        $result = $this->hardRules->evaluate([
            'umur' => $umur,
            'no_kp' => (string) ($details['no_ic_baru'] ?? $details['no_ic'] ?? ''),
            'pendapatan_bulanan' => $pendapatan / $bulan,
            'jumlah_komitmen_sedia_ada' => (float) ($details['jumlah_komitmen_sedia_ada'] ?? $details['komitmen_bulanan'] ?? 0),
            'muflis' => (bool) ($details['muflis'] ?? false),
        ]);

        return [
            'keputusan' => $result['eligible'] ? 'lulus' : 'gagal',
            'ringkasan' => $result['eligible']
                ? 'Semua peraturan kelayakan dipenuhi.'
                : 'Gagal peraturan: '.implode('; ', $result['reasons']),
            'hasil' => $result,
        ];
    }

    /**
     * @return array{keputusan: string, ringkasan: string, hasil: array<string, mixed>}
     */
    protected function stepPenipuan(Permohonan $permohonan): array
    {
        $result = $this->fraud->analyze($permohonan);
        $tahap = (string) ($result['tahap'] ?? 'rendah');

        return [
            'keputusan' => match ($tahap) {
                'tinggi' => 'gagal',
                'sederhana' => 'semak',
                default => 'lulus',
            },
            'ringkasan' => ($result['amaran'] ?? false)
                ? 'Amaran penipuan ('.$tahap.', skor '.($result['skor_risiko'] ?? 0).'): '
                    .implode('; ', array_slice(array_column($result['isyarat'] ?? [], 'tajuk'), 0, 3))
                : 'Tiada corak penipuan atau rangkaian mencurigakan dikesan.',
            'hasil' => $result,
        ];
    }

    /**
     * @return array{keputusan: string, ringkasan: string, hasil: array<string, mixed>}
     */
    protected function stepSkorKredit(Permohonan $permohonan): array
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $biro = $this->creditBureau->query($details);
        $result = $this->creditScoring->score($permohonan);
        $result['biro_kredit'] = $biro;
        $tahap = (string) ($result['tahap_risiko'] ?? 'tinggi');

        return [
            'keputusan' => match ($tahap) {
                'rendah' => 'lulus',
                'sederhana' => 'semak',
                default => 'gagal',
            },
            'ringkasan' => 'Skor '.($result['skor'] ?? 0).'/100 (gred '.($result['gred'] ?? '-').', risiko '.$tahap.')'
                .($biro['status'] === 'terputus'
                    ? ' · biro kredit terputus, dinilai dengan data alternatif sahaja'
                    : (($result['fail_nipis'] ?? false) ? ' · fail nipis, dinilai dengan data alternatif' : '')),
            'hasil' => $result,
        ];
    }

    /**
     * Routing rule: low risk with clean identity/fraud/rules → fast lane (one-click officer approval);
     * medium/high or any open issue → officer review; hard failures → recommend rejection.
     *
     * @param  array<string, mixed>  $langkah
     * @return array{keputusan: string, ringkasan: string, hasil: array<string, mixed>}
     */
    protected function stepCadangan(Permohonan $permohonan, array $langkah): array
    {
        $ekyc = $langkah['ekyc'] ?? [];
        $peraturan = $langkah['peraturan'] ?? [];
        $penipuan = $langkah['penipuan'] ?? [];
        $kredit = $langkah['skor_kredit'] ?? [];
        $skor = $kredit['hasil'] ?? [];

        $diminta = (float) ($permohonan->jumlah_permohonan ?? 0);
        $had = (float) ($skor['cadangan_had'] ?? 0);
        $dicadang = $had > 0 ? min($diminta, $had) : $diminta;

        $sebab = [];
        $syarat = [];
        foreach (['ekyc' => $ekyc, 'peraturan' => $peraturan, 'penipuan' => $penipuan, 'skor_kredit' => $kredit] as $kod => $l) {
            if (($l['keputusan'] ?? 'semak') !== 'lulus') {
                $sebab[] = self::LANGKAH[$kod].': '.($l['ringkasan'] ?? '');
            }
        }

        $tolak = ($peraturan['keputusan'] ?? null) === 'gagal'
            || ($ekyc['keputusan'] ?? null) === 'gagal'
            || ($penipuan['keputusan'] ?? null) === 'gagal';

        if ($tolak) {
            $laluan = 'cadang_tolak';
        } elseif ($sebab === [] && ($diminta <= $had || $had <= 0)) {
            $laluan = 'pantas';
        } else {
            $laluan = 'semakan_pegawai';
            if ($had > 0 && $diminta > $had) {
                $sebab[] = 'Jumlah dipohon RM '.number_format($diminta, 2).' melebihi had mampu bayar RM '.number_format($had, 2).'.';
                $syarat[] = 'Pertimbangkan jumlah RM '.number_format($had, 2).' mengikut kemampuan bayar balik.';
            }
        }

        if (($skor['biro_kredit']['status'] ?? null) === 'terputus') {
            $syarat[] = 'Sahkan rekod CCRIS / CTOS apabila sambungan biro kredit pulih, sebelum pengeluaran dana.';
        }
        if (($skor['fail_nipis'] ?? false) === true) {
            $syarat[] = 'Fail nipis: pantau 3 bulan pertama melalui aliran e-dompet/QR.';
        }
        foreach (array_slice($penipuan['hasil']['cadangan_tindakan'] ?? [], 0, 3) as $tindakan) {
            $syarat[] = $tindakan;
        }

        $cadangan = [
            'laluan' => $laluan,
            'label' => match ($laluan) {
                'pantas' => 'Laluan pantas — sedia untuk kelulusan satu klik',
                'cadang_tolak' => 'Cadangan tolak — perlu keputusan pegawai',
                default => 'Semakan pegawai',
            },
            'tahap_risiko' => match (true) {
                $tolak => 'tinggi',
                default => (string) ($skor['tahap_risiko'] ?? 'sederhana'),
            },
            'skor_kredit' => isset($skor['skor']) ? (int) $skor['skor'] : null,
            'gred' => $skor['gred'] ?? null,
            'jumlah_dipohon' => $diminta,
            'jumlah_dicadang' => round($dicadang, 2),
            'ansuran_dicadang' => (float) ($skor['ansuran_dicadang'] ?? 0),
            'tempoh_bulan' => (int) (($permohonan->details['tempoh_pembiayaan'] ?? null) ?: 24),
            'sebab' => $sebab,
            'syarat' => array_values(array_unique($syarat)),
            'amaran_penipuan' => (bool) ($penipuan['hasil']['amaran'] ?? false),
            'nota_hitl' => 'Cadangan AI sahaja. Keputusan kelulusan, penolakan dan pengeluaran dana dibuat oleh pegawai.',
        ];

        return [
            'keputusan' => match ($laluan) {
                'pantas' => 'lulus',
                'cadang_tolak' => 'gagal',
                default => 'semak',
            },
            'ringkasan' => $cadangan['label'].($sebab ? ' · '.count($sebab).' isu perlu disemak' : ''),
            'hasil' => $cadangan,
        ];
    }

    /**
     * Applicant MyKad front image on the public disk, if attached.
     *
     * @return array{path: string, mime: string}|null
     */
    public function myKadFront(Permohonan $permohonan): ?array
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $attachments = is_array($details['attachments'] ?? null) ? $details['attachments'] : [];

        $preferred = [
            DocumentClassificationService::CLASS_IC_PEMOHON_DEPAN,
            DocumentClassificationService::CLASS_IC_PEMOHON_COMBINED,
        ];

        foreach ($preferred as $class) {
            foreach ($attachments as $attachment) {
                if (($attachment['document_class'] ?? null) !== $class) {
                    continue;
                }
                $url = (string) ($attachment['url'] ?? '');
                if (! str_starts_with($url, '/storage/')) {
                    continue;
                }
                $relative = substr($url, strlen('/storage/'));
                if (! Storage::disk('public')->exists($relative)) {
                    continue;
                }

                return [
                    'path' => Storage::disk('public')->path($relative),
                    'mime' => (string) ($attachment['mime_type'] ?? 'image/jpeg'),
                ];
            }
        }

        return null;
    }
}
