<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksiPersediaan extends Model
{
    protected $table = 'jurnal_koreksi_persediaan';

    protected $primaryKey = 'JurnalKoreksiPersediaanID';

    protected $fillable = [
        'PersediaanID',
        'Keterangan',
    ];

    protected $casts = [
        'PersediaanID' => 'integer',
    ];

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(
            Persediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksiPersediaan::class,
            'JurnalKoreksiPersediaanID',
            'JurnalKoreksiPersediaanID'
        );
    }
}