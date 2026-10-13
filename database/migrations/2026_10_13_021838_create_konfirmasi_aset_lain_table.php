<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('konfirmasi_aset_lain', function (Blueprint $table) {
            $table->id('KonfirmasiAsetLainID');
            $table->unsignedBigInteger('AsetLainID');
            $table->string('NamaCustomer');
            $table->string('KotaCustomer');
            $table->unsignedBigInteger('Jumlah');
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->timestamps();

            $table->foreign('AsetLainID')
                ->references('AsetLainID')
                ->on('aset_lain')
                ->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE konfirmasi_aset_lain ADD File MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('konfirmasi_aset_lain', function (Blueprint $table) {
            $table->dropForeign(['AsetLainID']);
        });

        Schema::dropIfExists('konfirmasi_aset_lain');
    }
};
