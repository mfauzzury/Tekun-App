<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akaun_pembiayaan', function (Blueprint $table) {
            $table->string('kaedah_pengiraan', 30)->nullable()->after('bayaran_bulanan');
            $table->decimal('kadar_keuntungan_tahunan', 5, 2)->nullable()->after('kaedah_pengiraan');
        });
    }

    public function down(): void
    {
        Schema::table('akaun_pembiayaan', function (Blueprint $table) {
            $table->dropColumn(['kaedah_pengiraan', 'kadar_keuntungan_tahunan']);
        });
    }
};
