<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurSetaraKas extends Model
{
    protected $table = 'prosedur_setara_kas';

    protected $fillable = [
        'setara_kas_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function setaraKas(): BelongsTo
    {
        return $this->belongsTo(
            SetaraKas::class,
            'setara_kas_id',
            'SetaraKasID'
        );
    }
}