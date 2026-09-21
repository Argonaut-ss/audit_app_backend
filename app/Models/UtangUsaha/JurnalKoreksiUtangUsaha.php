<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiUtangUsaha extends Model
{
    protected $table = 'jurnal_koreksi_utang_usaha';

    protected $primaryKey = 'JurnalKoreksiUtangUsahaID';

    protected $fillable = [
        'UtangUsahaID',
        'Keterangan',
    ];

    protected $casts = [
        'UtangUsahaID' => 'integer',
    ];

    public function utangUsaha(): BelongsTo
    {
        return $this->belongsTo(UtangUsaha::class, 'UtangUsahaID', 'UtangUsahaID');
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiUtangUsaha::class,
            'JurnalKoreksiUtangUsahaID',
            'JurnalKoreksiUtangUsahaID'
        );
    }
}
