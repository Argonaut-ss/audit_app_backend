<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmpj_risk_rows', function (Blueprint $table) {
            $table->id('PmpjRiskRowID');
            $table->unsignedBigInteger('PmpjID');

            $table->string('profile_name')->nullable();
            $table->string('profile_type')->nullable();
            $table->text('selected_category')->nullable();
            $table->string('risk_level')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->foreign('PmpjID')
                ->references('PmpjID')
                ->on('pmpj')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('pmpj_risk_rows', function (Blueprint $table) {
            $table->dropForeign(['PmpjID']);
        });

        Schema::dropIfExists('pmpj_risk_rows');
    }
};
