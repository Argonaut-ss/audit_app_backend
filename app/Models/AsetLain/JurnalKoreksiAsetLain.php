<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiAsetLain extends Model
{
    protected $table = 'jurnal_koreksi_aset_lain';

    protected $primaryKey = 'JurnalKoreksiAsetLainID';

    protected $fillable = [
        'AsetLainID',
        'Keterangan',
    ];

    protected $casts = [
        'AsetLainID' => 'integer',
    ];

    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiAsetLain::class,
            'JurnalKoreksiAsetLainID',
            'JurnalKoreksiAsetLainID'
        );
    }
}
