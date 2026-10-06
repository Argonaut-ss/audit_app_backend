<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiKas extends Model
{
    protected $table = 'jurnal_koreksi_kas';

    protected $primaryKey = 'JurnalKoreksiKasID';

    protected $fillable = [
        'KasID',
        'Keterangan',
    ];

    protected $casts = [
        'KasID' => 'integer',
    ];

    public function kas(): BelongsTo
    {
        return $this->belongsTo(
            Kas::class,
            'KasID',
            'KasID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiKas::class,
            'JurnalKoreksiKasID',
            'JurnalKoreksiKasID'
        );
    }
}
