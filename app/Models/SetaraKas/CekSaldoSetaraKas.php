<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CekSaldoSetaraKas extends Model
{
    protected $table = 'cek_saldo_setara_kas';

    protected $primaryKey = 'CekSaldoID';

    protected $fillable = [
        'SetaraKasID',
        'NamaBank',
        'AkunCOA',
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
}
