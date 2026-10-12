<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurAsetLain extends Model
{
    protected $table = 'prosedur_aset_lain';

    protected $fillable = [
        'aset_lain_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'aset_lain_id' => 'integer',
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'aset_lain_id',
            'AsetLainID'
        );
    }
}