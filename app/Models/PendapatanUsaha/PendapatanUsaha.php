<?php

namespace App\Models\PendapatanUsaha;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PendapatanUsaha extends Model
{
    protected $table = 'pendapatan_usaha';

    protected $primaryKey = 'PendapatanUsahaID';

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
            DokumenPendapatanUsaha::class,
            'PendapatanUsahaID',
            'PendapatanUsahaID'
        );
    }

    public function prosedurs()
    {
        return $this->hasMany(
            ProsedurPendapatanUsaha::class,
            'pendapatan_usaha_id',
            'PendapatanUsahaID'
        );
    }

    public function jurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            JurnalKoreksiPendapatanUsaha::class,
            'PendapatanUsahaID',
            'PendapatanUsahaID'
        );
    }
}