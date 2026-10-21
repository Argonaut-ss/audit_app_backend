<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapBalasanSetaraKas extends Model
{
    use HasFactory;

    protected $table = 'rekap_balasan_setara_kas';

    protected $primaryKey = 'RekapBalasanSetaraKasID';

    protected $fillable = [
        'SetaraKasID',
        'CekSaldoSetaraKasID',
        'SaldoBB',
        'TanggalKirim',
        'MetodeKirim',
        'StatusKonfirmasi',
        'TanggalJawab',
        'SaldoJawab',
        'Selisih',
        'FileBukti',
        'NamaFile',
        'TipeFile',
    ];

    protected $hidden = [
        'FileBukti',
    ];

    protected $casts = [
        'SetaraKasID' => 'integer',
        'CekSaldoSetaraKasID' => 'integer',
        'SaldoBB' => 'integer',
        'TanggalKirim' => 'date',
        'TanggalJawab' => 'date',
        'SaldoJawab' => 'integer',
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

    public function cekSaldoSetaraKas(): BelongsTo
    {
        return $this->belongsTo(
            CekSaldoSetaraKas::class,
            'CekSaldoSetaraKasID',
            'CekSaldoID'
        );
    }
}
