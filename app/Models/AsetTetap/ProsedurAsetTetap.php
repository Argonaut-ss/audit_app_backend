<?php

namespace App\Models\AsetTetap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurAsetTetap extends Model
{
    protected $table = 'prosedur_aset_tetap';

    protected $fillable = [
        'aset_tetap_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function asetTetap(): BelongsTo
    {
        return $this->belongsTo(
            AsetTetap::class,
            'aset_tetap_id',
            'AsetTetapID'
        );
    }
}