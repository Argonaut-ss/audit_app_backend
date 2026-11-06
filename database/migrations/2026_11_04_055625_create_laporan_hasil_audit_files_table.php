<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_hasil_audit_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_hasil_audit_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('jenis_file');
            $table->enum('tipe_input', ['file', 'link']);
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('link')->nullable();
            $table->timestamps();

            $table->unique(
                ['laporan_hasil_audit_id', 'jenis_file'],
                'lha_files_audit_jenis_unique'
            );
        });

        DB::statement(
            'ALTER TABLE laporan_hasil_audit_files ADD file_data MEDIUMBLOB NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_hasil_audit_files');
    }
};