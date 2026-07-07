<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wf_workflow_name')) {
            return;
        }

        Schema::create('wf_workflow_name', function (Blueprint $table) {
            $table->string('wfa_workflow_code', 20)->primary();
            $table->string('wfa_workflow_title', 1000);
            $table->tinyInteger('wfa_prevent_self_process')->nullable();
            $table->tinyInteger('wfa_involve_posting')->default(1);
            $table->dateTime('createddate')->nullable()->useCurrent();
            $table->string('createdby', 100)->nullable();
            $table->dateTime('updateddate')->nullable();
            $table->string('updatedby', 100)->nullable();
        });

        Schema::create('wf_process', function (Blueprint $table) {
            $table->id('wfp_process_id');
            $table->string('wfp_workflow_code', 20);
            $table->string('wfp_process_name', 500);
            $table->string('wfp_process_desc_bm', 1000)->nullable();
            $table->string('wfp_process_desc_bi', 1000)->nullable();
            $table->integer('wfp_sequence');
            $table->string('wfp_status', 20)->default('1');
            $table->integer('wfp_duration_kpi')->nullable();
            $table->integer('wfp_duration_kpi_withquery')->nullable();
            $table->tinyInteger('wfp_is_email_notification')->default(1);
            $table->tinyInteger('wfp_is_todo_notification')->default(1);
            $table->tinyInteger('wfp_is_by_unit')->nullable();
            $table->tinyInteger('wfp_is_by_ptj')->nullable();
            $table->tinyInteger('wfp_is_allow_query')->nullable();
            $table->json('wfp_extended_field')->nullable();
            $table->string('wfp_processedby_desc', 250)->nullable();
            $table->dateTime('createddate')->nullable()->useCurrent();
            $table->string('createdby', 100)->nullable();
            $table->dateTime('updateddate')->nullable();
            $table->string('updatedby', 100)->nullable();

            $table->foreign('wfp_workflow_code')
                ->references('wfa_workflow_code')
                ->on('wf_workflow_name')
                ->cascadeOnDelete();
            $table->index('wfp_workflow_code');
        });

        Schema::create('wf_process_details', function (Blueprint $table) {
            $table->id('wpd_process_details_id');
            $table->unsignedBigInteger('wpd_process_id');
            $table->string('wpd_status_code', 20);
            $table->unsignedBigInteger('wpd_reroute_process')->nullable();
            $table->string('wpd_proc_to_exec', 255)->nullable();
            $table->integer('wpd_order');
            $table->json('wpd_extended_field')->nullable();
            $table->dateTime('createddate')->nullable()->useCurrent();
            $table->string('createdby', 100)->nullable();
            $table->dateTime('updateddate')->nullable();
            $table->string('updatedby', 100)->nullable();

            $table->foreign('wpd_process_id')
                ->references('wfp_process_id')
                ->on('wf_process')
                ->cascadeOnDelete();
            $table->foreign('wpd_reroute_process')
                ->references('wfp_process_id')
                ->on('wf_process')
                ->nullOnDelete();
        });

        Schema::create('wf_authorized_role', function (Blueprint $table) {
            $table->id('war_authorized_role_id');
            $table->unsignedBigInteger('war_process_id');
            $table->string('war_group_code', 100);
            $table->decimal('war_limit_min', 15, 2)->nullable();
            $table->decimal('war_limit_max', 15, 2)->nullable();
            $table->dateTime('createddate')->nullable()->useCurrent();
            $table->string('createdby', 100)->nullable();
            $table->dateTime('updateddate')->nullable()->useCurrent();
            $table->string('updatedby', 100)->nullable();

            $table->foreign('war_process_id')
                ->references('wfp_process_id')
                ->on('wf_process')
                ->cascadeOnDelete();
        });

        Schema::create('wf_lookup', function (Blueprint $table) {
            $table->string('wfl_code', 45)->primary();
            $table->string('wfl_desc', 45);
            $table->string('wfl_isPositive', 1)->default('1');
            $table->integer('wfl_order')->nullable();
            $table->dateTime('createddate')->nullable()->useCurrent();
            $table->string('createdby', 100)->nullable();
            $table->dateTime('updateddate')->nullable();
            $table->string('updatedby', 100)->nullable();
        });

        DB::table('wf_lookup')->insert([
            ['wfl_code' => 'APPROVE', 'wfl_desc' => 'Approve', 'wfl_isPositive' => '1', 'wfl_order' => 1],
            ['wfl_code' => 'REJECT', 'wfl_desc' => 'Reject', 'wfl_isPositive' => '0', 'wfl_order' => 2],
            ['wfl_code' => 'VERIFY', 'wfl_desc' => 'Verify', 'wfl_isPositive' => '1', 'wfl_order' => 3],
            ['wfl_code' => 'SUBMIT', 'wfl_desc' => 'Submit', 'wfl_isPositive' => '1', 'wfl_order' => 4],
            ['wfl_code' => 'RETURN', 'wfl_desc' => 'Return', 'wfl_isPositive' => '0', 'wfl_order' => 5],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_authorized_role');
        Schema::dropIfExists('wf_process_details');
        Schema::dropIfExists('wf_process');
        Schema::dropIfExists('wf_workflow_name');
        Schema::dropIfExists('wf_lookup');
    }
};
