<?php

namespace App\Models\Persediaan;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persediaan extends Model
{
    protected $table = 'persediaan';

    protected $primaryKey = 'PersediaanID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'StockCheck',
        'MutasiStockCheck',
        'UjiMutasiCheck',
        'TestPricingCheck',
        'JurnalCheck',
        'ProsedurAltCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'StockCheck' => 'boolean',
        'MutasiStockCheck' => 'boolean',
        'UjiMutasiCheck' => 'boolean',
        'TestPricingCheck' => 'boolean',
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

    public function rekonsiliasiPersediaan()
    {
        return $this->hasMany(
            RekonsiliasiPersediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function konfirmasiPersediaan()
    {
        return $this->hasMany(
            KonfirmasiPersediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function rekapBalasan()
    {
        return $this->hasMany(
            RekapBalasanPersediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function prosedurAlternatif(): HasMany
    {
        return $this->hasMany(
            ProsedurAlternatifPersediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function analisisUmur(): HasOne
    {
        return $this->hasOne(
            AnalisisUmur::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function jurnalKoreksi()
    {
        return $this->hasMany(
            JurnalKoreksiPersediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function prosedurs(): HasMany
    {
        return $this->hasMany(
            ProsedurPersediaan::class,
            'persediaan_id',
            'PersediaanID'
        );
    }
}