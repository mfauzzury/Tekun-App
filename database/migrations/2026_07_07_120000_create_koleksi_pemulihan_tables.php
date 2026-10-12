<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akaun_pembiayaan', function (Blueprint $table) {
            $table->string('klasifikasi_npl', 30)->default('lancar')->after('status');
            $table->timestamp('klasifikasi_dikemaskini_pada')->nullable()->after('klasifikasi_npl');
            $table->index('klasifikasi_npl');
        });

        Schema::create('notis_kutipan', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 40)->unique();
            $table->foreignId('akaun_id')->constrained('akaun_pembiayaan')->cascadeOnDelete();
            $table->string('tahap', 30);
            $table->string('jenis', 20);
            $table->string('status', 30)->default('dijadual');
            $table->unsignedSmallInteger('hari_lewat')->default(0);
            $table->decimal('jumlah_tunggakan', 15, 2)->default(0);
            $table->text('mesej')->nullable();
            $table->dateTime('tarikh_dijadual');
            $table->dateTime('tarikh_dihantar')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['akaun_id', 'tahap']);
            $table->index(['status', 'tarikh_dijadual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notis_kutipan');

        Schema::table('akaun_pembiayaan', function (Blueprint $table) {
            $table->dropIndex(['klasifikasi_npl']);
            $table->dropColumn(['klasifikasi_npl', 'klasifikasi_dikemaskini_pada']);
        });
    }
};
