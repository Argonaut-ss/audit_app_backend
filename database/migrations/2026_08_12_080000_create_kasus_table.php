<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasus', function (Blueprint $table) {
            $table->id('KasusID');
            $table->unsignedBigInteger('ClientID');
            $table->string('NamaTugas');
            $table->string('NamaFile');
            $table->binary('File');
        });


        Schema::table('kasus', function (Blueprint $table) {
            $table->foreign('ClientID')
                ->references('ClientID')
                ->on('data_client')
                ->restrictOnDelete();
        });
    }


    public function down(): void
    {
        Schema::table('kasus', function (Blueprint $table) {
            $table->dropForeign([
                'ClientID',
            ]);
        });
        Schema::dropIfExists('kasus');
    }
};