<?php

namespace App\Console\Commands;

use App\Services\DunningService;
use App\Services\NplClassificationService;
use App\Services\TawidhCalculationService;
use App\Models\AkaunPembiayaan;
use Illuminate\Console\Command;

class ProcessCollectionDunning extends Command
{
    protected $signature = 'sppt:process-dunning';

    protected $description = 'Klasifikasi NPL dan proses tindakan kutipan automatik (SMS / Notis Peringatan)';

    public function handle(
        TawidhCalculationService $tawidhService,
        NplClassificationService $nplService,
        DunningService $dunningService,
    ): int {
        $this->info('Mengemaskini hari lewat & klasifikasi NPL...');

        AkaunPembiayaan::query()
            ->where('tunggakan', '>', 0)
            ->whereNotIn('status', ['Selesai', 'Batal'])
            ->each(function (AkaunPembiayaan $akaun) use ($tawidhService, $nplService) {
                $tawidhService->recalculate($akaun);
                $nplService->classify($akaun->fresh());
            });

        $nplService->classifyAll();

        $this->info('Memproses tindakan kutipan automatik...');
        $stats = $dunningService->processAll();

        $this->info(sprintf(
            'Selesai — Notis dijana: %d, dihantar: %d, gagal: %d',
            $stats['notisDijana'],
            $stats['notisDihantar'],
            $stats['gagal'],
        ));

        return self::SUCCESS;
    }
}
