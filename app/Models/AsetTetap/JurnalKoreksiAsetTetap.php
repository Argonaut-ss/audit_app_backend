<?php

namespace App\Models\AsetTetap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiAsetTetap extends Model
{
    protected $table = 'jurnal_koreksi_aset_tetap';

    protected $primaryKey = 'JurnalKoreksiAsetTetapID';

    protected $fillable = [
        'AsetTetapID',
        'Keterangan',
    ];

    protected $casts = [
        'AsetTetapID' => 'integer',
    ];

    public function asetTetap(): BelongsTo
    {
        return $this->belongsTo(
            AsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiAsetTetap::class,
            'JurnalKoreksiAsetTetapID',
            'JurnalKoreksiAsetTetapID'
        );
    }
}
