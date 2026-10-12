<?php

namespace App\Models\AsetTetap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetLamaAsetTetap extends Model
{
    protected $table = 'aset_lama_aset_tetap';

    protected $primaryKey = 'AsetLamaID';

    protected $fillable = [
        'AsetTetapID',
        'NamaAset',
        'KodeAset',
        'Tanggal',
        'HargaPerolehan',
        'FotoAset',
        'TipeFile',
    ];

    protected $hidden = [
        'FotoAset',
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
