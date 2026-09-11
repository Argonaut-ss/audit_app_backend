<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekonsiliasiPiutang extends Model
{
    use HasFactory;

    protected $table = 'rekonsiliasi_piutang';

    protected $primaryKey = 'RekonsiliasiPiutangID';

    public $timestamps = true;

    protected $fillable = [
        'PiutangID',
        'KonfirmasiPiutangID',
        'NomorFaktur',
        'TanggalFaktur',
        'SaldoBuku',
        'SaldoCustomer',
        'Selisih',
        'Keterangan',
    ];

    protected $casts = [
        'PiutangID' => 'integer',
        'KonfirmasiPiutangID' => 'integer',
        'SaldoBuku' => 'integer',
        'SaldoCustomer' => 'integer',
        'Selisih' => 'integer',
        'TanggalFaktur' => 'date',
    ];

    public function konfirmasiPiutang(): BelongsTo
    {
        return $this->belongsTo(
            KonfirmasiPiutang::class,
            'KonfirmasiPiutangID',
            'KonfirmasiPiutangID'
        );
    }

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(
            Piutang::class,
            'PiutangID',
            'PiutangID'
        );
    }
}
