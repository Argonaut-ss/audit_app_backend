<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KonfirmasiUtangUsaha', function (Blueprint $table) {
            $table->id('KonfirmasiUtangUsahaID');
            $table->unsignedBigInteger('UtangUsahaID');
            $table->string('NamaCustomer');
            $table->string('KotaCustomer');
            $table->unsignedBigInteger('Jumlah');
            $table->string('NamaFile')->nullable();
            $table->string('TipeFile')->nullable();
            $table->timestamps();

            $table->foreign('UtangUsahaID')
                ->references('UtangUsahaID')
                ->on('utang_usaha')
                ->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE KonfirmasiUtangUsaha ADD File MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        Schema::table('KonfirmasiUtangUsaha', function (Blueprint $table) {
            $table->dropForeign(['UtangUsahaID']);
        });

        Schema::dropIfExists('KonfirmasiUtangUsaha');
    }
};