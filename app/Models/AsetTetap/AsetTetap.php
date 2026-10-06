<?php

namespace App\Models\AsetTetap;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsetTetap extends Model
{
    protected $table = 'aset_tetap';

    protected $primaryKey = 'AsetTetapID';

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

    public function asetBaru(): HasMany
    {
        return $this->hasMany(
            AsetBaruAsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(
            DokumenAsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }

    public function prosedurs()
    {
        return $this->hasMany(
            ProsedurAsetTetap::class,
            'aset_tetap_id',
            'AsetTetapID'
        );
    }   

    public function jurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            JurnalKoreksiAsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }

    public function ujiPenyusutan(): HasMany
    {
        return $this->hasMany(
            UjiPenyusutanAsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }
}