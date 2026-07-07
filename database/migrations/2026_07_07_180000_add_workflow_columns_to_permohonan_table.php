<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->string('wf_workflow_code', 20)->nullable()->after('cawangan');
            $table->unsignedBigInteger('wf_current_process_id')->nullable()->after('wf_workflow_code');
            $table->index(['wf_workflow_code', 'wf_current_process_id']);
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropIndex(['wf_workflow_code', 'wf_current_process_id']);
            $table->dropColumn(['wf_workflow_code', 'wf_current_process_id']);
        });
    }
};
