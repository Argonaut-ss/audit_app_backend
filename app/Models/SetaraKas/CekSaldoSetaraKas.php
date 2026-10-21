<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CekSaldoSetaraKas extends Model
{
    protected $table = 'cek_saldo_setara_kas';

    protected $primaryKey = 'CekSaldoID';

    protected $fillable = [
        'SetaraKasID',
        'NamaBank',
        'SaldoBukuBesar',
        'SaldoRekeningKoran',
        'Selisih',
    ];

    protected $casts = [
        'SetaraKasID' => 'integer',
        'SaldoBukuBesar' => 'integer',
        'SaldoRekeningKoran' => 'integer',
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

    public function rekonsilasiBank(): HasOne
    {
        return $this->hasOne(
            RekonsilasiBankSetaraKas::class,
            'CekSaldoID',
            'CekSaldoID'
        );
    }

    public function rekapBalasan(): HasOne
    {
        return $this->hasOne(
            RekapBalasanSetaraKas::class,
            'CekSaldoSetaraKasID',
            'CekSaldoID'
        );
    }
}
