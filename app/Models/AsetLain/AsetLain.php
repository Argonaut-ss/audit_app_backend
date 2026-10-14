<?php

namespace App\Models\AsetLain;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetLain extends Model
{
    protected $table = 'aset_lain';

    protected $primaryKey = 'AsetLainID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'KonfirmasiCheck',
        'RekapMutasiCheck',
        'DokumentasiCheck',
        'BuktiPendukungCheck',
        'WawancaraCheck',
        'JurnalCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'KonfirmasiCheck' => 'boolean',
        'RekapMutasiCheck' => 'boolean',
        'DokumentasiCheck' => 'boolean',
        'BuktiPendukungCheck' => 'boolean',
        'WawancaraCheck' => 'boolean',
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

    public function dokumen(): HasMany
    {
        return $this->hasMany(
            DokumenAsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }

    public function prosedurs(): HasMany
    {
        return $this->hasMany(
            ProsedurAsetLain::class,
            'aset_lain_id',
            'AsetLainID'
        );
    }

    public function buktiPendukung(): HasMany
    {
        return $this->hasMany(
            BuktiPendukungAsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }

    public function konfirmasi(): HasMany
    {
        return $this->hasMany(
            KonfirmasiAsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }

    public function jurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            JurnalKoreksiAsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(
            DokumentasiAsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }
}