<?php

namespace App\Models\BebanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VouchingBU extends Model
{
    protected $table = 'vouching_bu';

    protected $primaryKey = 'VouchingID';

    protected $fillable = [
        'BebanUsahaID',
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
        'BebanUsahaID' => 'integer',
        'Tanggal' => 'date:Y-m-d',
        'NominalInternal' => 'integer',
        'NominalEksternal' => 'integer',
        'Selisih' => 'integer',
        'PihakRelasi' => 'boolean',
        'ApprovalPO' => 'boolean',
        'ApprovalNV' => 'boolean',
        'ApprovalDO' => 'boolean',
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
