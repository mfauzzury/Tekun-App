<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$noRujukan = $argv[1] ?? null;
if (! $noRujukan) {
    fwrite(STDERR, "Usage: php scripts/revert-permohonan-draf.php PM-2026-0010\n");
    exit(1);
}

$permohonan = App\Models\Permohonan::where('no_rujukan', $noRujukan)->first();
if (! $permohonan) {
    fwrite(STDERR, "NOT_FOUND: {$noRujukan}\n");
    exit(1);
}

$previous = $permohonan->status;
$permohonan->update(['status' => 'Draf']);

echo "id={$permohonan->id} no_rujukan={$permohonan->no_rujukan} {$previous} -> {$permohonan->fresh()->status}\n";
