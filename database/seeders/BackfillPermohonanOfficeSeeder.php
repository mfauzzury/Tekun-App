<?php

namespace Database\Seeders;

use App\Models\Permohonan;
use App\Models\SpptCawangan;
use App\Models\Usahawan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BackfillPermohonanOfficeSeeder extends Seeder
{
    /** @var array<string, array{negeri?: string, cawangan?: string}> */
    private array $akaunByNama = [];

    public function run(): void
    {
        $this->loadAkaunLookup();

        $updated = 0;

        Permohonan::query()
            ->where(function ($query) {
                $query->whereNull('negeri')
                    ->orWhereNull('cawangan')
                    ->orWhereNull('wf_workflow_code');
            })
            ->each(function (Permohonan $permohonan) use (&$updated) {
                $updates = $this->resolveMissingFields($permohonan);

                if ($updates === []) {
                    return;
                }

                $permohonan->update($updates);
                $updated++;
            });

        $this->command?->info("Backfilled office/workflow fields on {$updated} permohonan record(s).");
    }

    /**
     * @return array<string, string>
     */
    private function resolveMissingFields(Permohonan $permohonan): array
    {
        $updates = [];
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $office = $this->resolveOffice($permohonan->nama, $details);

        if (! filled($permohonan->negeri) && filled($office['negeri'])) {
            $updates['negeri'] = $office['negeri'];
        }

        if (! filled($permohonan->cawangan) && filled($office['cawangan'])) {
            $updates['cawangan'] = $office['cawangan'];
        }

        if (! filled($permohonan->wf_workflow_code)) {
            $updates['wf_workflow_code'] = $this->resolveWorkflowCode($permohonan->kategori_pembiayaan);
        }

        return $updates;
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{negeri: ?string, cawangan: ?string}
     */
    private function resolveOffice(string $nama, array $details): array
    {
        $akaun = $this->akaunByNama[$nama] ?? null;
        $negeri = $akaun['negeri'] ?? ($details['negeri'] ?? null);
        $locality = $akaun['cawangan'] ?? null;

        if ($locality) {
            $branch = SpptCawangan::query()
                ->where('branch_type', 'cawangan')
                ->where('locality', $locality)
                ->when($negeri, fn ($query) => $query->where('negeri', $negeri))
                ->orderBy('sort_order')
                ->first()
                ?? SpptCawangan::query()
                    ->where('branch_type', 'cawangan')
                    ->where('locality', $locality)
                    ->orderBy('sort_order')
                    ->first();

            if ($branch) {
                return [
                    'negeri' => $negeri ?? $branch->negeri,
                    'cawangan' => $branch->name,
                ];
            }
        }

        if (! $negeri) {
            $negeri = Usahawan::query()->where('nama', $nama)->value('negeri');
        }

        $defaultBranch = SpptCawangan::query()->where('code', 'hulu-langat')->first();

        return [
            'negeri' => $negeri ?? $defaultBranch?->negeri ?? 'Selangor',
            'cawangan' => $defaultBranch?->name ?? 'TEKUN Nasional Cawangan Hulu Langat',
        ];
    }

    private function resolveWorkflowCode(?string $kategori): string
    {
        $scheme = trim((string) ($kategori ?? ''));
        $map = config('sppt-workflow.scheme_workflows', []);

        return $map[$scheme] ?? (string) config('sppt-workflow.default_workflow_code', 'TEKUN_NIAGA');
    }

    private function loadAkaunLookup(): void
    {
        $path = database_path('seeders/data/pengurusan-akaun.json');
        if (! File::exists($path)) {
            return;
        }

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($data['items'] ?? [] as $item) {
            $nama = $item['nama'] ?? null;
            if (! is_string($nama) || $nama === '') {
                continue;
            }

            $this->akaunByNama[$nama] = [
                'negeri' => $item['negeri'] ?? null,
                'cawangan' => $item['cawangan'] ?? null,
            ];
        }
    }
}
