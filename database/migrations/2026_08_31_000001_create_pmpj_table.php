<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmpj', function (Blueprint $table) {
            $table->id('PmpjID');
            $table->unsignedBigInteger('JwbKasusID');

            $table->string('Nama')->nullable();
            $table->string('Jabatan')->nullable();
            $table->text('Alamat')->nullable();
            $table->string('NamaPerusahaan')->nullable();
            $table->text('AlamatPerusahaan')->nullable();
            $table->string('TahunPeriode')->nullable();
            $table->string('NamaFileKTP')->nullable();
            $table->binary('FileKTP')->nullable();

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE pmpj MODIFY FileKTP MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('pmpj', function (Blueprint $table) {
            $table->dropForeign(['JwbKasusID']);
        });

        Schema::dropIfExists('pmpj');
    }
};
