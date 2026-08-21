<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_client', function (Blueprint $table) {
            $table->id('ClientID');
            $table->string('NPWP')->nullable();
            $table->string('NamaClient');
            $table->string('NamaKantor')->nullable();
            $table->string('JenisClient')->nullable();
            $table->string('AlamatClient')->nullable();
            $table->string('AlamatKantor')->nullable();
            $table->string('HPClient')->nullable();
            $table->string('HPKantor')->nullable();
            $table->string('EmailClient')->nullable();
            $table->string('EmailKantor')->nullable();
            $table->string('URLClient')->nullable();
            $table->string('URLKantor')->nullable();
            $table->string('NamaLogoKantor')->nullable();
            $table->string('NamaLogoPerusahaan')->nullable();
        });

        DB::statement(
            'ALTER TABLE data_client ADD LogoKantor MEDIUMBLOB NULL'
        );

        DB::statement(
            'ALTER TABLE data_client ADD LogoPerusahaan MEDIUMBLOB NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('data_client');
    }
};