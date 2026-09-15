<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AnalisisUmur', function (Blueprint $table) {
            $table->id('AnalisisUmurID');
            $table->unsignedBigInteger('PiutangID');
            $table->bigInteger('SaldoAuditor');
            $table->bigInteger('SaldoBB');
            $table->bigInteger('Selisih');
            $table->timestamps();

            $table->unique('PiutangID', 'analisis_umur_piutang_id_unique');
            $table->foreign('PiutangID', 'analisis_umur_piutang_id_fk')
                ->references('PiutangID')
                ->on('Piutang')
                ->cascadeOnDelete();
        });

        Schema::create('HasilAnalisisUmur', function (Blueprint $table) {
            $table->id('HasilAnalisisUmurID');
            $table->unsignedBigInteger('AnalisisUmurID');
            $table->enum('KelompokUmur', ['1-30', '31-60', '61-90', '>90']);
            $table->bigInteger('Jumlah')->default(0);
            $table->integer('Kerugian')->default(0);
            $table->timestamps();

            $table->foreign('AnalisisUmurID', 'hasil_analisis_umur_id_fk')
                ->references('AnalisisUmurID')
                ->on('AnalisisUmur')
                ->cascadeOnDelete();
        });

        if (! Schema::hasTable('analisis_umur_piutang')) {
            return;
        }

        $legacyGroups = DB::table('analisis_umur_piutang')
            ->orderBy('AnalisisUmurPiutangID')
            ->get()
            ->groupBy('PiutangID');

        foreach ($legacyGroups as $piutangId => $legacyRows) {
            $firstWithSaldoBB = $legacyRows->first(
                fn (object $row) => $row->SaldoBB !== null
            );
            $saldoBB = $firstWithSaldoBB?->SaldoBB ?? 0;
            $saldoAuditor = $legacyRows->sum(
                fn (object $row) => (int) round($row->Jumlah * $row->Kerugian / 100)
            );
            $timestamps = [
                'created_at' => $legacyRows->first()->created_at,
                'updated_at' => $legacyRows->last()->updated_at,
            ];

            $analisisUmurId = DB::table('AnalisisUmur')->insertGetId([
                'PiutangID' => $piutangId,
                'SaldoAuditor' => $saldoAuditor,
                'SaldoBB' => $saldoBB,
                'Selisih' => $saldoAuditor - $saldoBB,
                ...$timestamps,
            ], 'AnalisisUmurID');

            foreach ($legacyRows as $legacyRow) {
                DB::table('HasilAnalisisUmur')->insert([
                    'AnalisisUmurID' => $analisisUmurId,
                    'KelompokUmur' => $legacyRow->KelompokUmur,
                    'Jumlah' => $legacyRow->Jumlah,
                    'Kerugian' => $legacyRow->Kerugian,
                    'created_at' => $legacyRow->created_at,
                    'updated_at' => $legacyRow->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('HasilAnalisisUmur');
        Schema::dropIfExists('AnalisisUmur');
    }
};
