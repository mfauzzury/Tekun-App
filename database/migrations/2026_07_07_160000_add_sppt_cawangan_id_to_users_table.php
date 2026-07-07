<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'sppt_cawangan_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sppt_cawangan_id')
                ->nullable()
                ->after('role_id')
                ->constrained('sppt_cawangan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'sppt_cawangan_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sppt_cawangan_id');
        });
    }
};
