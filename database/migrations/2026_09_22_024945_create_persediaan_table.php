<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persediaan', function (Blueprint $table) {
            $table->id('PersediaanID');

            $table->unsignedBigInteger('JwbKasusID')->unique();

            $table->boolean('ProsedurCheck')->default(false);
            $table->boolean('DokumenCheck')->default(false);
            $table->boolean('StockCheck')->default(false);
            $table->boolean('MutasiStockCheck')->default(false);
            $table->boolean('UjiMutasiCheck')->default(false);
            $table->boolean('TestPricingCheck')->default(false);
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
        Schema::dropIfExists('persediaan');
    }
};