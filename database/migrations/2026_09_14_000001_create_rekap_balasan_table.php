<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_balasan', function (Blueprint $table) {
            $table->id('RekapBalasanID');
            $table->unsignedBigInteger('PiutangID');
            $table->unsignedBigInteger('KonfirmasiPiutangID')->nullable();
            $table->bigInteger('SaldoBB')->default(0);
            $table->date('TanggalKirim')->nullable();
            $table->string('MetodeKirim')->nullable();
            $table->date('TanggalJawab')->nullable();
            $table->bigInteger('SaldoJawab')->default(0);
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->enum('Status', ['terbalas', 'tidak terbalas'])->nullable();
            $table->timestamps();

            $table->foreign('PiutangID')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();

            $table->foreign('KonfirmasiPiutangID')
                ->references('KonfirmasiPiutangID')
                ->on('KonfirmasiPiutang')
                ->nullOnDelete();

            $table->index('PiutangID');
            $table->index('KonfirmasiPiutangID');
        });

        DB::statement('ALTER TABLE rekap_balasan ADD FileBukti MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('rekap_balasan', function (Blueprint $table) {
            $table->dropForeign(['PiutangID']);
            $table->dropForeign(['KonfirmasiPiutangID']);
        });

        Schema::dropIfExists('rekap_balasan');
    }
};
