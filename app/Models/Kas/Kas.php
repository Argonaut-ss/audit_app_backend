<?php

namespace App\Models\Kas;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kas extends Model
{
    protected $table = 'kas';

    protected $primaryKey = 'KasID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'AsetLamaCheck',
        'AsetBaruCheck',
        'UjiPenyusutanCheck',
        'JurnalCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'AsetLamaCheck' => 'boolean',
        'AsetBaruCheck' => 'boolean',
        'UjiPenyusutanCheck' => 'boolean',
        'JurnalCheck' => 'boolean',
    ];

    public function JwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function dokumen()
    {
        return $this->hasMany(
            DokumenBebanUsaha::class,
            'BebanUsahaID',
            'BebanUsahaID'
        );
    }

    public function prosedurs()
    {
        return $this->hasMany(
            ProsedurBebanUsaha::class,
            'beban_usaha_id',
            'BebanUsahaID'
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