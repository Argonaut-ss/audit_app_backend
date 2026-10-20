<?php

namespace App\Models\SetaraKas;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SetaraKas extends Model
{
    protected $table = 'setara_kas';

    protected $primaryKey = 'SetaraKasID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'RekeningCheck',
        'CekSaldoCheck',
        'RekonsilasiCheck',
        'KonfirmasiCheck',
        'RekapCheck',
        'JurnalCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'RekeningCheck' => 'boolean',
        'CekSaldoCheck' => 'boolean',
        'RekonsilasiCheck' => 'boolean',
        'KonfirmasiCheck' => 'boolean',
        'RekapCheck' => 'boolean',
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

    public function cekSaldoSetaraKas(): HasMany
    {
        return $this->hasMany(
            CekSaldoSetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    public function prosedurs(): HasMany
    {
        return $this->hasMany(
            ProsedurSetaraKas::class,
            'setara_kas_id',
            'SetaraKasID'
        );
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(
            DokumenSetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    /* A setara kas can have multiple rekening koran documents */
    public function rekeningKoranSetaraKas(): HasMany
    {
        return $this->hasMany(
            RekeningKoranSetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    public function rekonsilasiBank(): HasMany
    {
        return $this->hasMany(
            RekonsilasiBankSetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    /* A setara kas can have multiple konfirmasi bank documents */
    public function konfirmasiBank(): HasMany
    {
        return $this->hasMany(
            KonfirmasiBankSetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    public function jurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            JurnalKoreksiSetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }
}
