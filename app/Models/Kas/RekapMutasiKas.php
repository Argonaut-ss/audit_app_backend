<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekapMutasiKas extends Model
{
    protected $table = 'rekap_mutasi_kas';

    protected $primaryKey = 'RekapMutasiID';

    protected $fillable = [
        'KasID',
        'SaldoAwal',
        'DebitTotal',
        'KreditTotal',
    ];

    protected $casts = [
        'KasID' => 'integer',
        'SaldoAwal' => 'integer',
        'DebitTotal' => 'integer',
        'KreditTotal' => 'integer',
    ];

    public function kas(): BelongsTo
    {
        return $this->belongsTo(Kas::class, 'KasID', 'KasID');
    }

    public function isiRekapMutasi(): HasMany
    {
        return $this->hasMany(
            IsiRekapMutasiKas::class,
            'RekapMutasiID',
            'RekapMutasiID'
        );
    }
}
