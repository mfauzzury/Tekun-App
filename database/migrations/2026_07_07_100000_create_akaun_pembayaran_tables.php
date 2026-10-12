<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akaun_pembiayaan', function (Blueprint $table) {
            $table->decimal('tawidh', 15, 2)->default(0)->after('penalti');
            $table->unsignedSmallInteger('hari_lewat')->default(0)->after('tawidh');
            $table->unsignedSmallInteger('tempoh_bulan')->nullable()->after('bayaran_bulanan');
            $table->date('moratorium_tamat')->nullable()->after('tarikh_tamat');
        });

        Schema::create('jadual_ansuran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akaun_id')->constrained('akaun_pembiayaan')->cascadeOnDelete();
            $table->unsignedSmallInteger('no_ansuran');
            $table->date('tarikh_bayaran');
            $table->decimal('jumlah_ansuran', 15, 2);
            $table->decimal('pokok', 15, 2)->default(0);
            $table->decimal('keuntungan', 15, 2)->default(0);
            $table->decimal('baki', 15, 2)->default(0);
            $table->string('status', 30)->default('menunggu');
            $table->timestamps();
            $table->unique(['akaun_id', 'no_ansuran']);
            $table->index(['akaun_id', 'status']);
        });

        Schema::create('permohonan_penjadualan', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 30)->unique();
            $table->foreignId('akaun_id')->constrained('akaun_pembiayaan')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->string('status', 30)->default('menunggu');
            $table->unsignedSmallInteger('tempoh_bulan')->nullable();
            $table->date('tarikh_mula')->nullable();
            $table->date('tarikh_kuat_kuasa')->nullable();
            $table->decimal('bayaran_baharu', 15, 2)->nullable();
            $table->text('sebab')->nullable();
            $table->text('catatan_pegawai')->nullable();
            $table->foreignId('diluluskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('tarikh_kelulusan')->nullable();
            $table->timestamps();
            $table->index(['akaun_id', 'status']);
            $table->index(['jenis', 'status']);
        });

        Schema::create('batch_bayaran', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 30)->unique();
            $table->string('saluran', 30);
            $table->string('status', 30)->default('menunggu');
            $table->unsignedInteger('jumlah_rekod')->default(0);
            $table->unsignedInteger('jumlah_berjaya')->default(0);
            $table->unsignedInteger('jumlah_gagal')->default(0);
            $table->decimal('jumlah_keseluruhan', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_bayaran', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 40)->unique();
            $table->foreignId('akaun_id')->constrained('akaun_pembiayaan')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batch_bayaran')->nullOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->decimal('kepada_tawidh', 15, 2)->default(0);
            $table->decimal('kepada_tunggakan', 15, 2)->default(0);
            $table->decimal('kepada_ansuran', 15, 2)->default(0);
            $table->string('saluran', 30);
            $table->string('mod', 20)->default('real_time');
            $table->string('status', 30)->default('berjaya');
            $table->string('no_rujukan_luar', 80)->nullable();
            $table->dateTime('tarikh_bayaran');
            $table->timestamps();
            $table->index(['akaun_id', 'tarikh_bayaran']);
            $table->index(['saluran', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_bayaran');
        Schema::dropIfExists('batch_bayaran');
        Schema::dropIfExists('permohonan_penjadualan');
        Schema::dropIfExists('jadual_ansuran');
        Schema::table('akaun_pembiayaan', function (Blueprint $table) {
            $table->dropColumn(['tawidh', 'hari_lewat', 'tempoh_bulan', 'moratorium_tamat']);
        });
    }
};
