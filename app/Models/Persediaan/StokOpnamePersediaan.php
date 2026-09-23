<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpnamePersediaan extends Model
{
    protected $table = 'stok_opname_persediaan';

    protected $primaryKey = 'StokOpnameID';

    protected $fillable = [
        'PersediaanID',
        'NamaPersediaan',
        'Satuan',
        'SaldoNeraca',
        'JumlahSistem',
        'JumlahFisik',
        'SelisihFisik',
        'SelisihSistem',
        'Keterangan',
    ];

    protected $casts = [
        'PersediaanID' => 'integer',
        'SaldoNeraca' => 'integer',
        'JumlahSistem' => 'integer',
        'JumlahFisik' => 'integer',
        'SelisihFisik' => 'integer',
        'SelisihSistem' => 'integer',
    ];

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(
            Persediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }
}
