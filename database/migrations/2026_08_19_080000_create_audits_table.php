<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    /*
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id('AuditID');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('KasusID');
            $table->string('jenis_perusahaan');
            $table->date('periode_audit');
            $table->date('waktu_mulai');
            $table->date('batas_waktu');
            $table->timestamps();

            $table->foreign('KasusID')
                ->references('KasusID')
                ->on('kasus')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'KasusID']);
        });
    }
    */

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};