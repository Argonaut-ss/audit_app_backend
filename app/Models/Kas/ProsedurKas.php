<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurKas extends Model
{
    protected $table = 'prosedur_kas';

    protected $fillable = [
        'kas_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function kas(): BelongsTo
    {
        return $this->belongsTo(
            Kas::class,
            'kas_id',
            'KasID'
        );
    }
}