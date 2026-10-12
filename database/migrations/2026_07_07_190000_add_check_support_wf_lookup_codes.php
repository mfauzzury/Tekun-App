<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wf_lookup')->updateOrInsert(
            ['wfl_code' => 'CHECK'],
            [
                'wfl_desc' => 'Check',
                'wfl_isPositive' => '1',
                'wfl_order' => 6,
            ],
        );

        DB::table('wf_lookup')->updateOrInsert(
            ['wfl_code' => 'SUPPORT'],
            [
                'wfl_desc' => 'Support',
                'wfl_isPositive' => '1',
                'wfl_order' => 7,
            ],
        );
    }

    public function down(): void
    {
        DB::table('wf_lookup')->whereIn('wfl_code', ['CHECK', 'SUPPORT'])->delete();
    }
};
