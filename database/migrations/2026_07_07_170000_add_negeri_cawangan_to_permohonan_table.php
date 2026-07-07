<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->string('negeri', 100)->nullable()->after('status');
            $table->string('cawangan', 100)->nullable()->after('negeri');
            $table->index(['negeri', 'cawangan']);
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropIndex(['negeri', 'cawangan']);
            $table->dropColumn(['negeri', 'cawangan']);
        });
    }
};
