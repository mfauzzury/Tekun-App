<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usahawan', function (Blueprint $table) {
            $table->id();
            $table->string('no_usahawan')->unique();
            $table->string('nama');
            $table->string('no_ic', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('poskod', 10)->nullable();
            $table->string('negeri', 100)->nullable();
            $table->string('no_telefon', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('jenis_perniagaan', 100)->nullable();
            $table->string('status', 50)->default('Aktif');
            $table->timestamps();
            $table->index(['status', 'negeri']);
        });

        Schema::create('permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('no_rujukan', 30)->unique();
            $table->foreignId('usahawan_id')->nullable()->constrained('usahawan')->nullOnDelete();
            $table->string('nama');
            $table->string('kategori_pembiayaan', 50)->nullable();
            $table->string('status', 50)->default('Dalam Proses');
            $table->decimal('jumlah_permohonan', 15, 2)->default(0);
            $table->date('tarikh_permohonan')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('akaun_pembiayaan', function (Blueprint $table) {
            $table->id();
            $table->string('no_akaun', 30)->unique();
            $table->foreignId('permohonan_id')->nullable()->constrained('permohonan')->nullOnDelete();
            $table->foreignId('usahawan_id')->nullable()->constrained('usahawan')->nullOnDelete();
            $table->string('ic', 20)->nullable();
            $table->string('nama');
            $table->string('nama_syarikat')->nullable();
            $table->string('ssm', 30)->nullable();
            $table->string('pukonsa', 30)->nullable();
            $table->string('cawangan', 100)->nullable();
            $table->string('negeri', 100)->nullable();
            $table->string('produk', 100)->nullable();
            $table->date('tarikh_mula')->nullable();
            $table->date('tarikh_tamat')->nullable();
            $table->decimal('jumlah_pembiayaan', 15, 2)->default(0);
            $table->decimal('baki_pokok', 15, 2)->default(0);
            $table->decimal('baki_keuntungan', 15, 2)->default(0);
            $table->decimal('baki_simpanan', 15, 2)->default(0);
            $table->decimal('penalti', 15, 2)->default(0);
            $table->decimal('tunggakan', 15, 2)->default(0);
            $table->decimal('baki_akhir', 15, 2)->default(0);
            $table->decimal('bayaran_bulanan', 15, 2)->default(0);
            $table->string('status', 50)->default('Aktif');
            $table->string('risiko', 50)->default('Normal');
            $table->string('no_bsas', 50)->nullable();
            $table->boolean('snc')->default(false);
            $table->timestamps();
            $table->index(['status', 'cawangan']);
        });

        Schema::create('pengeluaran_dana', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 30)->unique();
            $table->foreignId('akaun_id')->nullable()->constrained('akaun_pembiayaan')->nullOnDelete();
            $table->string('id_pembiayaan', 30);
            $table->string('nama');
            $table->decimal('jumlah', 15, 2);
            $table->string('jenis', 20)->default('Penuh');
            $table->unsignedTinyInteger('fasa')->nullable();
            $table->decimal('peratus_fasa', 5, 2)->nullable();
            $table->string('bank', 100)->nullable();
            $table->string('no_akaun_bank', 30)->nullable();
            $table->string('status', 50)->default('Menunggu');
            $table->string('no_rujukan_bank', 50)->nullable();
            $table->string('fraud_risk', 20)->nullable();
            $table->boolean('bsas_verified')->default(false);
            $table->boolean('legal_docs_complete')->default(false);
            $table->date('tarikh_pengeluaran')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('jaminan', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 30)->unique();
            $table->string('nama');
            $table->string('jenis', 50);
            $table->decimal('nilai', 15, 2)->default(0);
            $table->string('status', 50)->default('Aktif');
            $table->string('risiko', 20)->default('Rendah');
            $table->string('no_pinjaman', 30)->nullable();
            $table->date('tarikh_mula')->nullable();
            $table->date('tarikh_tamat')->nullable();
            $table->string('dokumen')->nullable();
            $table->timestamps();
            $table->index(['status', 'tarikh_tamat']);
        });

        Schema::create('kutipan', function (Blueprint $table) {
            $table->id();
            $table->string('rujukan', 30)->unique();
            $table->foreignId('akaun_id')->nullable()->constrained('akaun_pembiayaan')->nullOnDelete();
            $table->foreignId('usahawan_id')->nullable()->constrained('usahawan')->nullOnDelete();
            $table->string('nama');
            $table->string('no_akaun', 30)->nullable();
            $table->string('cawangan', 100)->nullable();
            $table->string('zon', 50)->nullable();
            $table->string('pegawai', 100)->nullable();
            $table->decimal('tunggakan', 15, 2)->default(0);
            $table->decimal('hasil_kutipan', 15, 2)->nullable();
            $table->date('janji_bayar')->nullable();
            $table->string('status', 50)->default('Belum Dikunjungi');
            $table->text('catatan')->nullable();
            $table->dateTime('tarikh_lawatan')->nullable();
            $table->string('lokasi_gps')->nullable();
            $table->timestamps();
            $table->index(['status', 'cawangan']);
        });

        Schema::create('sppt_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);
            $table->string('dataset_key', 100);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['module', 'dataset_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sppt_datasets');
        Schema::dropIfExists('kutipan');
        Schema::dropIfExists('jaminan');
        Schema::dropIfExists('pengeluaran_dana');
        Schema::dropIfExists('akaun_pembiayaan');
        Schema::dropIfExists('permohonan');
        Schema::dropIfExists('usahawan');
    }
};
