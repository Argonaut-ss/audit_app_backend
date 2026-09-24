<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestPricingPersediaan extends Model
{
    protected $table = 'test_pricing_persediaan';

    protected $primaryKey = 'TestPricingID';

    protected $fillable = [
        'PersediaanID',
        'StokOpnameID',
        'HargaAudit',
        'KuantitasAudit',
        'JumlahAudit',
        'HargaPerusahaan',
        'KuantitasPerusahaan',
        'JumlahPerusahaan',
        'Selisih',
    ];

    protected $casts = [
        'PersediaanID' => 'integer',
        'StokOpnameID' => 'integer',
        'HargaAudit' => 'integer',
        'KuantitasAudit' => 'integer',
        'JumlahAudit' => 'integer',
        'HargaPerusahaan' => 'integer',
        'KuantitasPerusahaan' => 'integer',
        'JumlahPerusahaan' => 'integer',
        'Selisih' => 'integer',
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
