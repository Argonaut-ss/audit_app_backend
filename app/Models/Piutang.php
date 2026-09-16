<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'Kesimpulan',
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

    public function konfirmasiPiutang()
    {
        return $this->hasMany(
            KonfirmasiPiutang::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function rekapBalasan()
    {
        return $this->hasMany(
            RekapBalasan::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function prosedurAlternatif(): HasMany
    {
        return $this->hasMany(
            ProsedurAlternatif::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function analisisUmur(): HasOne
    {
        return $this->hasOne(
            AnalisisUmur::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function jurnalKoreksi()
    {
        return $this->hasMany(
            JurnalKoreksi::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function prosedurs(): HasMany
    {
        return $this->hasMany(
            Prosedur::class,
            'piutang_id',
            'PiutangID'
        );
    }
}