<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UjiMutasiPersediaan extends Model
{
    protected $table = 'uji_mutasi_persediaan';

    protected $primaryKey = 'UjiMutasiID';

    protected $fillable = [
        'StokOpnameID',
        'SaldoStokOpname',
        'Keluar',
        'Rusak',
        'Masuk',
        'SaldoAuditSblm',
        'SaldoAkhirSblm',
        'SaldoAuditSdh',
        'SaldoAkhirSdh',
    ];

    protected $casts = [
        'StokOpnameID' => 'integer',
        'SaldoStokOpname' => 'integer',
        'Keluar' => 'integer',
        'Rusak' => 'integer',
        'Masuk' => 'integer',
        'SaldoAuditSblm' => 'integer',
        'SaldoAkhirSblm' => 'integer',
        'SaldoAuditSdh' => 'integer',
        'SaldoAkhirSdh' => 'integer',
    ];

    public function ujiMutasi(): HasMany
    {
        return $this->hasMany(
            UjiMutasiPersediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function stokOpname(): BelongsTo
    {
        return $this->belongsTo(
            StokOpnamePersediaan::class,
            'StokOpnameID',
            'StokOpnameID'
        );
    }
}
