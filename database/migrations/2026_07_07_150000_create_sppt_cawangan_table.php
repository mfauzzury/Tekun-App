<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sppt_cawangan')) {
            return;
        }

        Schema::create('sppt_cawangan', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 500);
            $table->string('branch_type', 30)->default('cawangan');
            $table->string('negeri', 100)->nullable();
            $table->string('locality', 150)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 200)->nullable();
            $table->string('fax', 100)->nullable();
            $table->string('contact_person', 200)->nullable();
            $table->string('external_id', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['negeri', 'is_active']);
            $table->index(['branch_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sppt_cawangan');
    }
};
