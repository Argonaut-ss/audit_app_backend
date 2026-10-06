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

    public function rekapMutasi(): HasMany
    {
        return $this->hasMany(
            RekapMutasiKas::class,
            'KasID',
            'KasID'
        );
    }

    public function cashCount(): HasOne
    {
        return $this->hasOne(
            CashCountKas::class,
            'KasID',
            'KasID'
        );
    }

    public function dokumen()
    {
        return $this->hasMany(
            DokumenKas::class,
            'KasID',
            'KasID'
        );
    }


    public function prosedurs()
    {
        return $this->hasMany(
            ProsedurKas::class,
            'kas_id',
            'KasID'
        );
    }

    public function jurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            JurnalKoreksiBebanUsaha::class,
            'BebanUsahaID',
            'BebanUsahaID'
        );
    }

}
