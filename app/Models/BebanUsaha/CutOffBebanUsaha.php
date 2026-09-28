<?php

namespace App\Models\BebanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CutOffBebanUsaha extends Model
{
    protected $table = 'cut_off_beban_usaha';

    protected $primaryKey = 'CutOffID';

    protected $fillable = [
        'BebanUsahaID',
        'Periode',
        'NamaPelanggan',
        'NomorFaktur',
        'TanggalFaktur',
        'Jumlah',
        'TanggalDelivery',
        'SesuaiPeriode',
    ];

    protected $casts = [
        'BebanUsahaID' => 'integer',
        'Jumlah' => 'integer',
        'SesuaiPeriode' => 'boolean',
        'TanggalFaktur' => 'date:Y-m-d',
        'TanggalDelivery' => 'date:Y-m-d',
    ];

    public function bebanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            BebanUsaha::class,
            'BebanUsahaID',
            'BebanUsahaID'
        );
    }
}
