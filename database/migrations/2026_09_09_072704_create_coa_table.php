<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa', function (Blueprint $table) {
            $table->id('COAID');

            $table->unsignedBigInteger('JwbKasusID');

            $table->string('NoAkun')->nullable();
            $table->string('NamaAkun')->nullable();
            $table->string('NamaLain')->nullable();
            $table->string('MappingGroup')->nullable();
            $table->string('MapKelompok')->nullable();
            $table->string('MappingTop')->nullable();
            $table->string('SubMappingTop')->nullable();

            $table->enum('Saldo', [
                'Debit',
                'Kredit',
            ])->nullable();

            $table->decimal('PerBook', 20, 2)->nullable();
            $table->decimal('AuditSebelum', 20, 2)->nullable();

            $table->foreign('JwbKasusID')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coa', function (Blueprint $table) {
            $table->dropForeign(['JwbKasusID']);
        });

        Schema::dropIfExists('coa');
    }
};