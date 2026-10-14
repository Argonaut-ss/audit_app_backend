<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumentasiAsetLain extends Model
{
    protected $table = 'dokumentasi_aset_lain';

    protected $primaryKey = 'DokumentasiID';

    protected $fillable = [
        'AsetLainID',
        'NomorPerjanjian',
        'Objek',
        'TanggalPerjanjian',
        'TanggalMulai',
        'TanggalAkhir',
        'Nominal',
        'JangkaWaktu',
        'MasaPemakaian',
        'AmortisasiBulan',
        'AmortisasiAkhirTahun',
        'Audit',
        'Perusahaan',
        'Selisih',
    ];

    protected $casts = [
        'AsetLainID' => 'integer',
        'TanggalPerjanjian' => 'date:Y-m-d',
        'TanggalMulai' => 'date:Y-m-d',
        'TanggalAkhir' => 'date:Y-m-d',
        'Nominal' => 'decimal:2',
        'JangkaWaktu' => 'integer',
        'MasaPemakaian' => 'integer',
        'AmortisasiBulan' => 'decimal:2',
        'AmortisasiAkhirTahun' => 'decimal:2',
        'Audit' => 'decimal:2',
        'Perusahaan' => 'decimal:2',
        'Selisih' => 'decimal:2',
    ];

    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }
}

