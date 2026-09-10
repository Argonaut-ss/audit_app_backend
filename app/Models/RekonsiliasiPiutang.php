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
        'NamaCustomer',
        'NomorFaktur',
        'TanggalFaktur',
        'SaldoBuku',
        'SaldoCustomer',
        'Selisih',
        'Keterangan',
    ];

    protected $casts = [
        'PiutangID' => 'integer',
        'SaldoBuku' => 'decimal:2',
        'SaldoCustomer' => 'decimal:2',
        'Selisih' => 'decimal:2',
        'TanggalFaktur' => 'date',
    ];

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(
            Piutang::class,
            'PiutangID',
            'PiutangID'
        );
    }
}
