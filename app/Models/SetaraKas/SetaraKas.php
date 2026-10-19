<?php

namespace App\Models\SetaraKas;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SetaraKas extends Model
{
    protected $table = 'setara_kas';

    protected $primaryKey = 'SetaraKasID';

    protected $fillable = [
        'JwbKasusID',
        'ProsedurCheck',
        'DokumenCheck',
        'RekeningCheck',
        'CekSaldoCheck',
        'RekonsilasiCheck',
        'KonfirmasiCheck',
        'RekapCheck',
        'JurnalCheck',
        'Kesimpulan',
    ];

    protected $casts = [
        'ProsedurCheck' => 'boolean',
        'DokumenCheck' => 'boolean',
        'RekeningCheck' => 'boolean',
        'CekSaldoCheck' => 'boolean',
        'RekonsilasiCheck' => 'boolean',
        'KonfirmasiCheck' => 'boolean',
        'RekapCheck' => 'boolean',
        'JurnalCheck' => 'boolean',
    ];

    public function JwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }
}
