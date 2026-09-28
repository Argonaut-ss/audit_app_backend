<?php

namespace App\Models\PendapatanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiPendapatanUsaha extends Model
{
    protected $table = 'jurnal_koreksi_pendapatan_usaha';

    protected $primaryKey = 'JurnalKoreksiPendapatanUsahaID';

    protected $fillable = [
        'PendapatanUsahaID',
        'Keterangan',
    ];

    protected $casts = [
        'PendapatanUsahaID' => 'integer',
    ];

    public function pendapatanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            PendapatanUsaha::class,
            'PendapatanUsahaID',
            'PendapatanUsahaID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiPendapatanUsaha::class,
            'JurnalKoreksiPendapatanUsahaID',
            'JurnalKoreksiPendapatanUsahaID'
        );
    }
}
