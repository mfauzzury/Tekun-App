<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_dana', function (Blueprint $table) {
            $table->text('fraud_alert')->nullable()->after('fraud_risk');
        });

        Schema::table('jaminan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('tarikh_tamat');
        });

        Schema::table('kutipan', function (Blueprint $table) {
            $table->date('tarikh_akhir_bayaran')->nullable()->after('tunggakan');
            $table->unsignedSmallInteger('hari_lewat')->default(0)->after('tarikh_akhir_bayaran');
            $table->string('maklumat_psat', 100)->nullable()->after('hari_lewat');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_dana', function (Blueprint $table) {
            $table->dropColumn('fraud_alert');
        });

        Schema::table('jaminan', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });

        Schema::table('kutipan', function (Blueprint $table) {
            $table->dropColumn(['tarikh_akhir_bayaran', 'hari_lewat', 'maklumat_psat']);
        });
    }
};
