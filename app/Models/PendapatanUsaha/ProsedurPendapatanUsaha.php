<?php

namespace App\Models\PendapatanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurPendapatanUsaha extends Model
{
    protected $table = 'prosedur_pendapatan_usaha';

    protected $fillable = [
        'pendapatan_usaha_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function pendapatanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            PendapatanUsaha::class,
            'pendapatan_usaha_id',
            'PendapatanUsahaID'
        );
    }
}