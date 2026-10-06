<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashCountDanaLain extends Model
{
    protected $table = 'cash_count_dana_lain';

    protected $primaryKey = 'DanaLainID';

    protected $fillable = [
        'CashCountID',
        'Keterangan',
        'Jumlah',
    ];

    protected $casts = [
        'CashCountID' => 'integer',
        'Jumlah' => 'integer',
    ];

    public function cashCount(): BelongsTo
    {
        return $this->belongsTo(CashCount::class, 'CashCountID', 'CashCountID');
    }
}
