<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jwb_kasus', function (Blueprint $table) {
            $table->id('JwbKasusID');
            $table->unsignedBigInteger('MahasiswasID');
            $table->unsignedBigInteger('KasusID');
            $table->enum('JenisPerusahaan', [
                'Manufaktur',
                'Dagang',
                'Jasa',
            ]);
            $table->date('Periode');
            $table->date('WaktuMulai');
            $table->date('BatasWaktu');
            $table->integer('Nilai')->nullable();

            // Satu mahasiswa hanya boleh menjawab
            // satu kasus satu kali
            $table->unique(
                ['KasusID', 'MahasiswasID'],
                'jwb_kasus_kasus_mahasiswa_unique'
            );

            $table->foreign('MahasiswasID')
                ->references('id')
                ->on('mahasiswas')
                ->cascadeOnDelete();

            $table->foreign('KasusID')
                ->references('KasusID')
                ->on('kasus')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jwb_kasus', function (Blueprint $table) {

            $table->dropForeign([
                'MahasiswasID',
            ]);

            $table->dropForeign([
                'KasusID',
            ]);
        });

        Schema::dropIfExists('jwb_kasus');
    }
};