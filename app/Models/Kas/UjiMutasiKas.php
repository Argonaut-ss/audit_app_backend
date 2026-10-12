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
        'Penjelasan',
    ];

    protected $casts = [
        'KasID' => 'integer',
    ];

    public function kas(): BelongsTo
    {
        return $this->belongsTo(Kas::class, 'KasID', 'KasID');
    }

}
