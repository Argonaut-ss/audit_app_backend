<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashCount extends Model
{
    protected $table = 'cash_count';

    protected $primaryKey = 'CashCountID';

    protected $fillable = [
        'KasID',
        'JenisKas',
        'TanggalCashCount',
        'UK100k',
        'UK75k',
        'UK50k',
        'UK20k',
        'UK10k',
        'UK5k',
        'UK2k',
        'UK1k',
        'UL1k',
        'UL500',
        'UL200',
        'UL100',
        'SaldoBuku',
        'Penjelasan',
        'TotalKertas',
        'TotalLogam',
        'TotalDanaLain',
        'TotalKeseluruhan',
        'SelisihLebihKurang',
    ];

    protected $casts = [
        'KasID' => 'integer',
        'TanggalCashCount' => 'date:Y-m-d',
        'UK100k' => 'integer',
        'UK75k' => 'integer',
        'UK50k' => 'integer',
        'UK20k' => 'integer',
        'UK10k' => 'integer',
        'UK5k' => 'integer',
        'UK2k' => 'integer',
        'UK1k' => 'integer',
        'UL1k' => 'integer',
        'UL500' => 'integer',
        'UL200' => 'integer',
        'UL100' => 'integer',
        'SaldoBuku' => 'integer',
        'TotalKertas' => 'integer',
        'TotalLogam' => 'integer',
        'TotalDanaLain' => 'integer',
        'TotalKeseluruhan' => 'integer',
        'SelisihLebihKurang' => 'integer',
    ];

    public function kas(): BelongsTo
    {
        return $this->belongsTo(Kas::class, 'KasID', 'KasID');
    }

    public function danaLain(): HasMany
    {
        return $this->hasMany(
            CashCountDanaLain::class,
            'CashCountID',
            'CashCountID'
        );
    }
}
