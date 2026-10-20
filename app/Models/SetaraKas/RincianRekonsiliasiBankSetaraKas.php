<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RincianRekonsiliasiBankSetaraKas extends Model
{
    protected $table = 'rincian_rekonsiliasi_bank_setara_kas';

    protected $primaryKey = 'RincianRekonsiliasiBankID';

    protected $fillable = [
        'RekonsilasiBankID',
        'Kategori',
        'Keterangan',
        'Nominal',
    ];

    protected $casts = [
        'RekonsilasiBankID' => 'integer',
        'Nominal' => 'integer',
    ];

    public function rekonsilasiBank(): BelongsTo
    {
        return $this->belongsTo(
            RekonsilasiBankSetaraKas::class,
            'RekonsilasiBankID',
            'RekonsilasiBankID'
        );
    }
}

