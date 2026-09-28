<?php

namespace App\Models\BebanUsaha;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BebanUsaha extends Model
{
    protected $table = 'beban_usaha';

    protected $primaryKey = 'BebanUsahaID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'CutOffCheck',
        'VouchingCheck',
        'JurnalCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'CutOffCheck' => 'boolean',
        'VouchingCheck' => 'boolean',
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
}