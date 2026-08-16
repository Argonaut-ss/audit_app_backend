<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'dosen', 'mahasiswa'])->default('mahasiswa')->after('password');
        });

        // Backfill existing users based on mahasiswas/dosens relationships
        DB::statement("
            UPDATE users
            SET role = 'mahasiswa'
            WHERE EXISTS (SELECT 1 FROM mahasiswas WHERE mahasiswas.user_id = users.id)
        ");

        DB::statement("
            UPDATE users
            SET role = 'dosen'
            WHERE EXISTS (SELECT 1 FROM dosens WHERE dosens.user_id = users.id)
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};