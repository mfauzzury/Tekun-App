<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POC 1 — one row per run of the fast assessment pipeline (e-KYC → rules → fraud → credit → routing).
     */
    public function up(): void
    {
        Schema::create('penilaian_pantas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->nullable()->constrained('permohonan')->nullOnDelete();
            $table->string('status')->default('berjalan');
            $table->string('laluan')->nullable();
            $table->string('tahap_risiko')->nullable();
            $table->unsignedTinyInteger('skor_kredit')->nullable();
            $table->json('langkah')->nullable();
            $table->json('cadangan')->nullable();
            $table->timestamp('dihantar_pada')->nullable();
            $table->timestamp('mula_pada')->nullable();
            $table->timestamp('siap_pada')->nullable();
            $table->unsignedInteger('jumlah_ms')->nullable();
            $table->unsignedInteger('hujung_ke_hujung_ms')->nullable();
            $table->string('sumber')->default('pegawai');
            $table->foreignId('dimulakan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('keputusan_pegawai')->nullable();
            $table->foreignId('keputusan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('keputusan_pada')->nullable();
            $table->text('catatan_pegawai')->nullable();
            $table->timestamps();

            $table->index(['permohonan_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_pantas');
    }
};
