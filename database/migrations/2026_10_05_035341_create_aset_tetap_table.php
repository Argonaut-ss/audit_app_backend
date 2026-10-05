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
        Schema::create('aset_tetap', function (Blueprint $table) {
            $table->id('AsetTetapID');
            $table->unsignedBigInteger('JwbKasusID')->unique();
            $table->boolean('ProsedurCheck')->default(false);
            $table->boolean('DokumenCheck')->default(false);
            $table->boolean('AsetLamaCheck')->default(false);
            $table->boolean('AsetBaruCheck')->default(false);
            $table->boolean('UjiPenyusutanCheck')->default(false);
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
        Schema::dropIfExists('aset_tetap');
    }
};
