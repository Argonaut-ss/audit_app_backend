<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjiMutasiKas extends Model
{
    protected $table = 'uji_mutasi_kas';

    protected $primaryKey = 'UjiMutasiID';

    protected $fillable = [
        'KasID',
        'CashCountID',
        'RekapMutasiID',
        'Penjelasan',
    ];

    protected $casts = [
        'KasID' => 'integer',
        'CashCountID' => 'integer',
        'RekapMutasiID' => 'integer',
    ];

    public function kas(): BelongsTo
    {
        return $this->belongsTo(Kas::class, 'KasID', 'KasID');
    }

    public function cashCount(): BelongsTo
    {
        return $this->belongsTo(CashCountKas::class, 'CashCountID', 'CashCountID');
    }

    public function rekapMutasi(): BelongsTo
    {
        return $this->belongsTo(RekapMutasiKas::class, 'RekapMutasiID', 'RekapMutasiID');
    }
}
