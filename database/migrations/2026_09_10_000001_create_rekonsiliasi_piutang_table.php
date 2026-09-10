<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_piutang', function (Blueprint $table) {
            $table->id('RekonsiliasiPiutangID');
            $table->unsignedBigInteger('PiutangID');
            $table->string('NamaCustomer')->nullable();
            $table->string('NomorFaktur');
            $table->date('TanggalFaktur');
            $table->decimal('SaldoBuku', 18, 2)->default(0);
            $table->decimal('SaldoCustomer', 18, 2)->default(0);
            $table->decimal('Selisih', 18, 2)->default(0);
            $table->string('Keterangan')->nullable();
            $table->timestamps();

            $table->foreign('PiutangID')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();

            $table->index('PiutangID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_piutang');
    }
};
