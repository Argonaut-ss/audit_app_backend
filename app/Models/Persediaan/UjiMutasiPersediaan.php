<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjiMutasiPersediaan extends Model
{
    protected $table = 'uji_mutasi_persediaan';

    protected $primaryKey = 'UjiMutasiID';

    protected $fillable = [
        'PersediaanID',
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
        'PersediaanID' => 'integer',
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

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(
            Persediaan::class,
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
