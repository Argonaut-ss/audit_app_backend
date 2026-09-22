<?php

namespace App\Models\UtangUsaha;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtangUsaha extends Model
{
    protected $table = 'utang_usaha';

    protected $primaryKey = 'UtangUsahaID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'KonfirmasiCheck',
        'RekapCheck',
        'JurnalCheck',
        'RekonsiliasiCheck',
        'ProsedurAltCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'KonfirmasiCheck' => 'boolean',
        'RekapCheck' => 'boolean',
        'JurnalCheck' => 'boolean',
        'RekonsiliasiCheck' => 'boolean',
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

    public function prosedurs(): HasMany
    {
        return $this->hasMany(
            ProsedurUtangUsaha::class,
            'utang_usaha_id',
            'UtangUsahaID'
        );
    }

    public function dokumens(): HasMany
    {
        return $this->hasMany(
            DokumenUtangUsaha::class,
            'utang_usaha_id',
            'UtangUsahaID'
        );
    }

    public function konfirmasiUtangUsaha(): HasMany
    {
        return $this->hasMany(
            KonfirmasiUtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }

    public function rekapBalasan(): HasMany
    {
        return $this->hasMany(
            RekapBalasanUtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }

    public function prosedurAlternatif(): HasMany
    {
        return $this->hasMany(
            ProsedurAlternatifUtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }

    public function rekonsiliasiUtangUsaha(): HasMany
    {
        return $this->hasMany(
            RekonsiliasiUtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }

    public function jurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            JurnalKoreksiUtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }
}
