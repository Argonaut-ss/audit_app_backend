<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurUtangUsaha extends Model
{
    protected $table = 'prosedur_utang_usaha';

    protected $fillable = [
        'utang_usaha_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function utangUsaha(): BelongsTo
    {
        return $this->belongsTo(UtangUsaha::class, 'utang_usaha_id', 'UtangUsahaID');
    }
}
