<?php

namespace App\Models\Kas;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kas extends Model
{
    protected $table = 'kas';

    protected $primaryKey = 'KasID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'CashCountCheck',
        'RekapMutasiCheck',
        'UjiMutasiCheck',
        'JurnalCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'CashCountCheck' => 'boolean',
        'RekapMutasiCheck' => 'boolean',
        'UjiMutasiCheck' => 'boolean',
        'JurnalCheck' => 'boolean',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function cashCount(): HasOne
    {
        return $this->hasOne(
            CashCount::class,
            'KasID',
            'KasID'
        );
    }
}
