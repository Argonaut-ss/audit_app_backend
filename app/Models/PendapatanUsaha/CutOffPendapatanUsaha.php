<?php

namespace App\Models\PendapatanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CutOffPendapatanUsaha extends Model
{
    protected $table = 'cut_off_pendapatan_usaha';

    protected $primaryKey = 'CutOffID';

    protected $fillable = [
        'PendapatanUsahaID',
        'Periode',
        'NamaPelanggan',
        'NomorFaktur',
        'TanggalFaktur',
        'Jumlah',
        'TanggalDelivery',
        'SesuaiPeriode',
    ];

    protected $casts = [
        'PendapatanUsahaID' => 'integer',
        'Jumlah' => 'integer',
        'SesuaiPeriode' => 'boolean',
        'TanggalFaktur' => 'date:Y-m-d',
        'TanggalDelivery' => 'date:Y-m-d',
    ];

    public function pendapatanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            PendapatanUsaha::class,
            'PendapatanUsahaID',
            'PendapatanUsahaID'
        );
    }
}
