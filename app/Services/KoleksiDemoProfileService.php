<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class KoleksiDemoProfileService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $profiles = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function demoProfiles(): array
    {
        if ($this->profiles === null) {
            $path = database_path('seeders/data/pengurusan-akaun.json');
            $this->profiles = collect(json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR)['items'] ?? [])
                ->filter(fn (array $item) => isset($item['klasifikasiNpl']))
                ->values()
                ->all();
        }

        return $this->profiles;
    }

    public function syncOverdueJadualDates(): int
    {
        $hariTangguh = (int) config('sppt-repayment.tawidh.hari_tangguh', 7);
        $count = 0;

        foreach ($this->demoProfiles() as $item) {
            $hariLewat = (int) ($item['hariLewat'] ?? 0);
            if ($hariLewat <= 0) {
                continue;
            }

            $akaun = AkaunPembiayaan::where('no_akaun', $item['id'])->first();
            if (! $akaun) {
                continue;
            }

            $tarikhBayaran = Carbon::now()->subDays($hariLewat + $hariTangguh)->toDateString();

            $updated = $akaun->jadualAnsuran()
                ->whereIn('status', ['menunggu', 'tertunggak'])
                ->orderBy('no_ansuran')
                ->limit(1)
                ->update([
                    'tarikh_bayaran' => $tarikhBayaran,
                    'status' => 'tertunggak',
                ]);

            if ($updated) {
                $count++;
            }
        }

        return $count;
    }

    public function applyProfiles(): int
    {
        $count = 0;

        foreach ($this->demoProfiles() as $item) {
            $updated = AkaunPembiayaan::where('no_akaun', $item['id'])->update([
                'tunggakan' => $item['tunggakan'] ?? 0,
                'penalti' => $item['penalti'] ?? 0,
                'tawidh' => $item['penalti'] ?? 0,
                'hari_lewat' => $item['hariLewat'] ?? 0,
                'klasifikasi_npl' => $item['klasifikasiNpl'],
                'klasifikasi_dikemaskini_pada' => now(),
                'status' => $item['status'] ?? 'Aktif',
            ]);

            $count += $updated;
        }

        return $count;
    }
}
