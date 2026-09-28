<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cut_off_pendapatan_usaha', function (Blueprint $table) {
            $table->id('CutOffID');
            $table->unsignedBigInteger('PendapatanUsahaID');

            $table->enum('Periode', ['sebelum', 'sesudah'])->default('sebelum');
            $table->string('NamaPelanggan')->nullable();
            $table->string('NomorFaktur')->nullable();
            $table->date('TanggalFaktur')->nullable();
            $table->bigInteger('Jumlah')->default(0);
            $table->date('TanggalDelivery')->nullable();
            $table->boolean('SesuaiPeriode')->default(false);

            $table->timestamps();

            $table->foreign('PendapatanUsahaID', 'copu_pendapatan_usaha_id_fk')
                ->references('PendapatanUsahaID')
                ->on('pendapatan_usaha')
                ->cascadeOnDelete();

            $table->index('PendapatanUsahaID', 'copu_pendapatan_usaha_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cut_off_pendapatan_usaha');
    }
};
