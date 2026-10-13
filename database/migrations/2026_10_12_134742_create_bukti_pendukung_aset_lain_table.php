<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('BuktiPendukungAsetLain', function (Blueprint $table) {
            $table->id('BuktiPendukungAsetLainID');

            $table->foreignId('AsetLainID')
                ->constrained('aset_lain', 'AsetLainID')
                ->cascadeOnDelete();

            $table->string('NamaFile');
            $table->string('NamaFileUpload')->nullable();
            $table->string('MimeType', 100)->nullable();
            $table->binary('File')->nullable();

            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE `BuktiPendukungAsetLain` MODIFY `File` MEDIUMBLOB NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('BuktiPendukungAsetLain');
    }
};