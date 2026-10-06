<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IsiRekapMutasiKas extends Model
{
    protected $table = 'isi_rekap_mutasi_kas';

    protected $primaryKey = 'IsiRekapMutasiID';

    protected $fillable = [
        'RekapMutasiID',
        'Tanggal',
        'Keterangan',
        'Debit',
        'Kredit',
        'Saldo',
    ];

    protected $casts = [
        'RekapMutasiID' => 'integer',
        'Tanggal' => 'date:Y-m-d',
        'Debit' => 'integer',
        'Kredit' => 'integer',
        'Saldo' => 'integer',
    ];

    public function rekapMutasi(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\RekapMutasiKas::class,
            'RekapMutasiID',
            'RekapMutasiID'
        );
    }
}
