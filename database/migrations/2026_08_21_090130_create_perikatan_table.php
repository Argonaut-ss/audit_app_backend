<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perikatan', function (Blueprint $table) {
            $table->id('PerikatanID');
            $table->unsignedBigInteger('JwbKasusID')->unique();

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });

        DB::statement('
            ALTER TABLE perikatan
            ADD FileProposal MEDIUMBLOB NULL,
            ADD FileSPK MEDIUMBLOB NULL,
            ADD FileSuratTugas MEDIUMBLOB NULL,
            ADD FilePenugasan MEDIUMBLOB NULL,
            ADD FileIndependensi MEDIUMBLOB NULL
        ');
    }

    public function down(): void
    {
        Schema::table('perikatan', function (Blueprint $table) {
            $table->dropForeign([
                'JwbKasusID',
            ]);
        });

        Schema::dropIfExists('perikatan');
    }
};