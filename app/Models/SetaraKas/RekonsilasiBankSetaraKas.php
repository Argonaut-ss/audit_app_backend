<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekonsilasiBankSetaraKas extends Model
{
    protected $table = 'rekonsilasi_bank_setara_kas';

    protected $primaryKey = 'RekonsilasiBankID';

    protected $fillable = [
        'SetaraKasID',
        'CekSaldoID',
        'SaldoSetelahRekonBank',
        'SaldoSetelahRekonBuku',
        'Status',
        'Selisih',
    ];

    protected $casts = [
        'SetaraKasID' => 'integer',
        'CekSaldoID' => 'integer',
        'SaldoSetelahRekonBank' => 'integer',
        'SaldoSetelahRekonBuku' => 'integer',
        'Status' => 'boolean',
        'Selisih' => 'integer',
    ];

    public function setaraKas(): BelongsTo
    {
        return $this->belongsTo(
            SetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }

    public function cekSaldo(): BelongsTo
    {
        return $this->belongsTo(
            CekSaldoSetaraKas::class,
            'CekSaldoID',
            'CekSaldoID'
        );
    }

    public function rincian(): HasMany
    {
        return $this->hasMany(
            RincianRekonsiliasiBankSetaraKas::class,
            'RekonsilasiBankID',
            'RekonsilasiBankID'
        );
    }
}

