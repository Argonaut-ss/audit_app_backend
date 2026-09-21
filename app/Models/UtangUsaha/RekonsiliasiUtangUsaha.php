<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekonsiliasiUtangUsaha extends Model
{
    use HasFactory;

    protected $table = 'rekonsiliasi_utang_usaha';

    protected $primaryKey = 'RekonsiliasiUtangUsahaID';

    public $timestamps = true;

    protected $fillable = [
        'UtangUsahaID',
        'KonfirmasiUtangUsahaID',
        'NomorFaktur',
        'TanggalFaktur',
        'SaldoBuku',
        'SaldoCustomer',
        'Selisih',
        'Keterangan',
    ];

    protected $casts = [
        'UtangUsahaID' => 'integer',
        'KonfirmasiUtangUsahaID' => 'integer',
        'SaldoBuku' => 'integer',
        'SaldoCustomer' => 'integer',
        'Selisih' => 'integer',
        'TanggalFaktur' => 'date',
    ];

    public function utangUsaha(): BelongsTo
    {
        return $this->belongsTo(
            UtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }
}
