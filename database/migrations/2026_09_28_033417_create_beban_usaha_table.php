<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beban_usaha', function (Blueprint $table) {
            $table->id('BebanUsahaID');
            $table->unsignedBigInteger('JwbKasusID')->unique();
            $table->boolean('ProsedurCheck')->default(false);
            $table->boolean('DokumenCheck')->default(false);
            $table->boolean('CutOffCheck')->default(false);
            $table->boolean('VouchingCheck')->default(false);
            $table->boolean('JurnalCheck')->default(false);
            $table->text('Kesimpulan')->nullable();
            $table->timestamps();

            // FK ke JwbKasus
            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beban_usaha');
    }
};
