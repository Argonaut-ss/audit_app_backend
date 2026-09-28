<?php

namespace App\Models\BebanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiBebanUsaha extends Model
{
    protected $table = 'jurnal_koreksi_beban_usaha';

    protected $primaryKey = 'JurnalKoreksiBebanUsahaID';

    protected $fillable = [
        'BebanUsahaID',
        'Keterangan',
    ];

    protected $casts = [
        'BebanUsahaID' => 'integer',
    ];

    public function bebanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            BebanUsaha::class,
            'BebanUsahaID',
            'BebanUsahaID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiBebanUsaha::class,
            'JurnalKoreksiBebanUsahaID',
            'JurnalKoreksiBebanUsahaID'
        );
    }
}
