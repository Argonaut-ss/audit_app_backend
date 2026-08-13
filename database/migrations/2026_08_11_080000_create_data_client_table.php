<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_client', function (Blueprint $table) {
            $table->id('ClientID');
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_client');
    }
};