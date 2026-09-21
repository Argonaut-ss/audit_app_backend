<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_balasan_utang_usaha', function (Blueprint $table) {
            $table->id('RekapBalasanUtangUsahaID');
            $table->unsignedBigInteger('UtangUsahaID');
            $table->unsignedBigInteger('KonfirmasiUtangUsahaID')->nullable();
            $table->bigInteger('SaldoBB')->default(0);
            $table->date('TanggalKirim')->nullable();
            $table->string('MetodeKirim')->nullable();
            $table->date('TanggalJawab')->nullable();
            $table->bigInteger('SaldoJawab')->default(0);
            $table->bigInteger('Selisih')->default(0);
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->enum('Status', ['terbalas', 'tidak terbalas'])->nullable();
            $table->timestamps();

            $table->foreign('UtangUsahaID')
                ->references('UtangUsahaID')
                ->on('utang_usaha')
                ->cascadeOnDelete();

            $table->foreign('KonfirmasiUtangUsahaID')
                ->references('KonfirmasiUtangUsahaID')
                ->on('KonfirmasiUtangUsaha')
                ->nullOnDelete();

            $table->index('UtangUsahaID');
            $table->index('KonfirmasiUtangUsahaID');
        });

        DB::statement('ALTER TABLE rekap_balasan_utang_usaha ADD FileBukti MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('rekap_balasan_utang_usaha', function (Blueprint $table) {
            $table->dropForeign(['UtangUsahaID']);
            $table->dropForeign(['KonfirmasiUtangUsahaID']);
        });

        Schema::dropIfExists('rekap_balasan_utang_usaha');
    }
};
