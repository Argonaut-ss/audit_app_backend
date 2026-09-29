<?php

namespace App\Models\PendapatanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VouchingPU extends Model
{
    protected $table = 'vouching_pu';

    protected $primaryKey = 'VouchingID';

    protected $fillable = [
        'PendapatanUsahaID',
        'Keterangan',
        'Tanggal',
        'NomorBukti',
        'NominalInternal',
        'BuktiInternal',
        'BuktiInternalTipeFile',
        'NominalEksternal',
        'BuktiEksternal',
        'BuktiEksternalTipeFile',
        'Selisih',
        'PihakRelasi',
        'Bukti',
        'BuktiFileTipe',
        'ApprovalPO',
        'ApprovalNV',
        'ApprovalDO',
    ];

    protected $hidden = [
        'BuktiInternal',
        'BuktiEksternal',
        'Bukti',
    ];

    protected $casts = [
        'PendapatanUsahaID' => 'integer',
        'Tanggal' => 'date:Y-m-d',
        'NominalInternal' => 'integer',
        'NominalEksternal' => 'integer',
        'Selisih' => 'integer',
        'PihakRelasi' => 'boolean',
        'ApprovalPO' => 'boolean',
        'ApprovalNV' => 'boolean',
        'ApprovalDO' => 'boolean',
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
