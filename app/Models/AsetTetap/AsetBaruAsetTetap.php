<?php

namespace App\Models\AsetTetap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetBaruAsetTetap extends Model
{
    protected $table = 'aset_baru_aset_tetap';

    protected $primaryKey = 'AsetBaruID';

    protected $fillable = [
        'AsetTetapID',
        'NamaAset',
        'KodeAset',
        'Tanggal',
        'FotoAset',
        'HargaPerolehan',
        'FotoBukti',
        'TipeFileAset',
        'TipeFileBukti',
    ];

    protected $hidden = [
        'FotoAset',
        'FotoBukti',
    ];

    protected $casts = [
        'AsetTetapID' => 'integer',
        'Tanggal' => 'date:Y-m-d',
        'HargaPerolehan' => 'integer',
    ];

    public function asetTetap(): BelongsTo
    {
        return $this->belongsTo(
            AsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }
}
