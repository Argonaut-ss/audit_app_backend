<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekonsiliasi_piutang', function (Blueprint $table) {
            $table->unsignedBigInteger('KonfirmasiPiutangID')
                ->nullable()
                ->after('PiutangID');

            $table->index(
                'KonfirmasiPiutangID',
                'rekonsiliasi_piutang_konfirmasi_piutang_id_idx'
            );

            $table->foreign(
                'KonfirmasiPiutangID',
                'rekonsiliasi_piutang_konfirmasi_piutang_id_fk'
            )
                ->references('KonfirmasiPiutangID')
                ->on('KonfirmasiPiutang')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rekonsiliasi_piutang', function (Blueprint $table) {
            $table->dropForeign('rekonsiliasi_piutang_konfirmasi_piutang_id_fk');
            $table->dropIndex('rekonsiliasi_piutang_konfirmasi_piutang_id_idx');
            $table->dropColumn('KonfirmasiPiutangID');
        });
    }
};
