<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\NotisKutipan;
use Illuminate\Support\Str;

class DunningService
{
    public function __construct(
        protected CollectionSmsService $smsService,
        protected NplClassificationService $nplService,
    ) {}

    /**
     * @return array{notisDijana: int, notisDihantar: int, gagal: int}
     */
    public function processAll(): array
    {
        $stats = ['notisDijana' => 0, 'notisDihantar' => 0, 'gagal' => 0];

        AkaunPembiayaan::query()
            ->where('tunggakan', '>', 0)
            ->whereNotIn('status', ['Selesai', 'Batal'])
            ->with(['usahawan', 'permohonan'])
            ->each(function (AkaunPembiayaan $akaun) use (&$stats) {
                $result = $this->processAccount($akaun);
                $stats['notisDijana'] += $result['notisDijana'];
                $stats['notisDihantar'] += $result['notisDihantar'];
                $stats['gagal'] += $result['gagal'];
            });

        return $stats;
    }

    /**
     * @return array{notisDijana: int, notisDihantar: int, gagal: int}
     */
    public function processAccount(AkaunPembiayaan $akaun): array
    {
        $stats = ['notisDijana' => 0, 'notisDihantar' => 0, 'gagal' => 0];
        $hariLewat = (int) ($akaun->hari_lewat ?? 0);

        if ($hariLewat <= 0 || (float) $akaun->tunggakan <= 0) {
            return $stats;
        }

        foreach (config('sppt-collection.dunning', []) as $rule) {
            if (! $this->matchesRule($hariLewat, $rule)) {
                continue;
            }

            $tahap = (string) $rule['tahap'];
            $alreadySent = NotisKutipan::query()
                ->where('akaun_id', $akaun->id)
                ->where('tahap', $tahap)
                ->whereIn('status', ['dijadual', 'dihantar'])
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $notis = $this->createNotice($akaun, $rule);
            $stats['notisDijana']++;

            if ($this->dispatchNotice($notis, $akaun)) {
                $stats['notisDihantar']++;
            } else {
                $stats['gagal']++;
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function matchesRule(int $hariLewat, array $rule): bool
    {
        $min = (int) ($rule['min_hari'] ?? 0);
        $max = $rule['max_hari'] ?? null;

        if ($hariLewat < $min) {
            return false;
        }

        if ($max !== null && $hariLewat > (int) $max) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function createNotice(AkaunPembiayaan $akaun, array $rule): NotisKutipan
    {
        $jenis = (string) $rule['jenis'];
        $mesej = $jenis === 'sms'
            ? $this->buildSmsMessage($akaun, $rule)
            : $this->buildLetterSummary($akaun, $rule);

        return NotisKutipan::create([
            'rujukan' => $this->generateRujukan((string) $rule['tahap']),
            'akaun_id' => $akaun->id,
            'tahap' => (string) $rule['tahap'],
            'jenis' => $jenis,
            'status' => 'dijadual',
            'hari_lewat' => (int) $akaun->hari_lewat,
            'jumlah_tunggakan' => (float) $akaun->tunggakan,
            'mesej' => $mesej,
            'tarikh_dijadual' => now(),
            'meta' => [
                'label' => $rule['label'] ?? $rule['tahap'],
                'nama' => $akaun->nama,
                'noAkaun' => $akaun->no_akaun,
                'cawangan' => $akaun->cawangan,
            ],
        ]);
    }

    private function dispatchNotice(NotisKutipan $notis, AkaunPembiayaan $akaun): bool
    {
        $success = true;
        $meta = $notis->meta ?? [];

        if ($notis->jenis === 'sms') {
            $phone = $akaun->usahawan?->no_telefon
                ?? $akaun->permohonan?->pemohon_telefon
                ?? null;

            if (! $phone) {
                $notis->update([
                    'status' => 'gagal',
                    'meta' => array_merge($meta, ['error' => 'Tiada nombor telefon']),
                ]);

                return false;
            }

            $success = $this->smsService->send($phone, (string) $notis->mesej);
            $meta['telefon'] = $phone;
        }

        $notis->update([
            'status' => $success ? 'dihantar' : 'gagal',
            'tarikh_dihantar' => $success ? now() : null,
            'meta' => $meta,
        ]);

        return $success;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function buildSmsMessage(AkaunPembiayaan $akaun, array $rule): string
    {
        $template = (string) ($rule['sms_template'] ?? 'TEKUN: Akaun {no_akaun} tertunggak.');

        return str_replace(
            ['{no_akaun}', '{tunggakan}', '{cawangan}', '{nama}', '{hari_lewat}'],
            [
                $akaun->no_akaun,
                number_format((float) $akaun->tunggakan, 2),
                $akaun->cawangan ?? 'TEKUN',
                $akaun->nama,
                (string) $akaun->hari_lewat,
            ],
            $template
        );
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function buildLetterSummary(AkaunPembiayaan $akaun, array $rule): string
    {
        $label = (string) ($rule['label'] ?? $rule['tahap']);

        return sprintf(
            '%s — Akaun %s (%s), tunggakan RM%s, %d hari lewat.',
            $label,
            $akaun->no_akaun,
            $akaun->nama,
            number_format((float) $akaun->tunggakan, 2),
            (int) $akaun->hari_lewat
        );
    }

    private function generateRujukan(string $tahap): string
    {
        $prefix = strtoupper(str_replace('_', '-', $tahap));

        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildLetterData(NotisKutipan $notis): array
    {
        $akaun = $notis->akaun;
        $meta = $notis->meta ?? [];
        $rule = collect(config('sppt-collection.dunning', []))
            ->firstWhere('tahap', $notis->tahap);

        return [
            'rujukan' => $notis->rujukan,
            'tahap' => $notis->tahap,
            'label' => $meta['label'] ?? ($rule['label'] ?? $notis->tahap),
            'tarikh' => ($notis->tarikh_dihantar ?? $notis->tarikh_dijadual)->format('d/m/Y'),
            'nama' => $akaun?->nama ?? ($meta['nama'] ?? ''),
            'noAkaun' => $akaun?->no_akaun ?? ($meta['noAkaun'] ?? ''),
            'ic' => $akaun?->ic ?? '',
            'cawangan' => $akaun?->cawangan ?? ($meta['cawangan'] ?? 'TEKUN'),
            'tunggakan' => number_format((float) $notis->jumlah_tunggakan, 2),
            'hariLewat' => (int) $notis->hari_lewat,
            'klasifikasi' => $this->nplService->label((string) ($akaun?->klasifikasi_npl ?? 'lancar')),
        ];
    }
}
