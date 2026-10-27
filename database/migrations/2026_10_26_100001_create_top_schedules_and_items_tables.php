<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_schedules', function (Blueprint $table) {
            $table->id('TopScheduleID');
            $table->unsignedBigInteger('JwbKasusID');
            $table->string('Top', 2);
            $table->string('Mapping', 100);
            $table->date('TanggalPerBook')->nullable();
            $table->date('TanggalAuditBerjalan')->nullable();
            $table->date('TanggalAuditSebelum')->nullable();
            $table->boolean('IsFinal')->default(false);
            $table->timestamps();

            $table->foreign('JwbKasusID', 'ts_jwb_kasus_id_fk')
                ->references('JwbKasusID')
                ->on('jwb_kasus')
                ->cascadeOnDelete();

            $table->unique(['JwbKasusID', 'Top'], 'ts_kasus_top_unique');
            $table->index(['JwbKasusID', 'Top'], 'ts_kasus_top_idx');
        });

        Schema::create('top_schedule_items', function (Blueprint $table) {
            $table->id('TopScheduleItemID');
            $table->unsignedBigInteger('TopScheduleID');
            $table->string('SubMapping', 100);
            $table->unsignedBigInteger('COAID');
            $table->integer('Urutan')->default(0);
            $table->timestamps();

            $table->foreign('TopScheduleID', 'tsi_top_schedule_id_fk')
                ->references('TopScheduleID')
                ->on('top_schedules')
                ->cascadeOnDelete();

            $table->foreign('COAID', 'tsi_coa_id_fk')
                ->references('COAID')
                ->on('coa')
                ->cascadeOnDelete();

            $table->index(['TopScheduleID', 'SubMapping'], 'tsi_sched_submap_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_schedule_items');
        Schema::dropIfExists('top_schedules');
    }
};

