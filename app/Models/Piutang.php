<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Piutang extends Model
{
    protected $table = 'Piutang';

    protected $primaryKey = 'PiutangID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'KonfirmasiCheck',
        'RekapCheck',
        'JurnalCheck',
        'RekonsiliasiCheck',
        'UmurCheck',
        'ProsedurAltCheck',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'KonfirmasiCheck' => 'boolean',
        'RekapCheck' => 'boolean',
        'JurnalCheck' => 'boolean',
        'RekonsiliasiCheck' => 'boolean',
        'UmurCheck' => 'boolean',
        'ProsedurAltCheck' => 'boolean',
    ];

    public function JwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function rekonsiliasiPiutang()
    {
        return $this->hasMany(
            RekonsiliasiPiutang::class,
            'PiutangID',
            'PiutangID'
        );
    }
}