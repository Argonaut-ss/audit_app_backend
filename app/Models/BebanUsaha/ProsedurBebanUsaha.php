<?php

namespace App\Models\BebanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurBebanUsaha extends Model
{
    protected $table = 'prosedur_beban_usaha';

    protected $fillable = [
        'beban_usaha_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function bebanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            BebanUsaha::class,
            'beban_usaha_id',
            'BebanUsahaID'
        );
    }
}