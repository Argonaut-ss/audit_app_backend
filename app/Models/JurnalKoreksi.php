<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKoreksi extends Model
{
    protected $table = 'JurnalKoreksi';

    protected $primaryKey = 'JurnalKoreksiID';

    protected $fillable = [
        'PiutangID',
        'Keterangan',
    ];

    protected $casts = [
        'PiutangID' => 'integer',
    ];

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(Piutang::class, 'PiutangID', 'PiutangID');
    }

    public function pembayaranJurnalKoreksi(): HasMany
    {
        return $this->hasMany(
            PembayaranJurnalKoreksi::class,
            'JurnalKoreksiID',
            'JurnalKoreksiID'
        );
    }
}
