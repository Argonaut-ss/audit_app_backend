<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiSetaraKas extends Model
{
    protected $table = 'jurnal_koreksi_setara_kas';

    protected $primaryKey = 'JurnalKoreksiSetaraKasID';

    protected $fillable = [
        'SetaraKasID',
        'Keterangan',
    ];

    protected $casts = [
        'SetaraKasID' => 'integer',
    ];

    public function setaraKas(): BelongsTo
    {
        return $this->belongsTo(
            SetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiSetaraKas::class,
            'JurnalKoreksiSetaraKasID',
            'JurnalKoreksiSetaraKasID'
        );
    }
}

