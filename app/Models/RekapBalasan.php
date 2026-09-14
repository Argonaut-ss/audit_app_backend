<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapBalasan extends Model
{
    use HasFactory;

    protected $table = 'rekap_balasan';

    protected $primaryKey = 'RekapBalasanID';

    protected $fillable = [
        'PiutangID',
        'KonfirmasiPiutangID',
        'SaldoBB',
        'TanggalKirim',
        'MetodeKirim',
        'TanggalJawab',
        'SaldoJawab',
        'Selisih',
        'FileBukti',
        'NamaFile',
        'TipeFile',
        'Status',
    ];

    protected $hidden = [
        'FileBukti',
    ];

    protected $casts = [
        'PiutangID' => 'integer',
        'KonfirmasiPiutangID' => 'integer',
        'SaldoBB' => 'integer',
        'TanggalKirim' => 'date',
        'TanggalJawab' => 'date',
        'SaldoJawab' => 'integer',
        'Selisih' => 'integer',
    ];

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(
            Piutang::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function konfirmasiPiutang(): BelongsTo
    {
        return $this->belongsTo(
            KonfirmasiPiutang::class,
            'KonfirmasiPiutangID',
            'KonfirmasiPiutangID'
        );
    }
}
