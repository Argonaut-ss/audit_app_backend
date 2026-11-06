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
        Schema::create('laporan_hasil_audit', function (Blueprint $table) {
            $table->id('LaporanHasilAuditID');
            $table->unsignedBigInteger('JwbKasusID')->unique();
            $table->enum('OpiniAudit', [
                'Wajar Tanpa Pengecualian',
                'Wajar Dengan Pengecualian',
                'Tidak Wajar',
                'Tidak Menyatakan Pendapat',
            ])->nullable();

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_hasil_audit', function (Blueprint $table) {
            $table->dropForeign(['JwbKasusID']);
        });

        Schema::dropIfExists('laporan_hasil_audit');
    }
};
