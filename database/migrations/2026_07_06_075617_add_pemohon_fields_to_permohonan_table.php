<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->string('pemohon_email')->nullable()->after('details');
            $table->string('pemohon_telefon')->nullable()->after('pemohon_email');
            $table->string('pemohon_access_token', 64)->nullable()->unique()->after('pemohon_telefon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropColumn(['pemohon_email', 'pemohon_telefon', 'pemohon_access_token']);
        });
    }
};
