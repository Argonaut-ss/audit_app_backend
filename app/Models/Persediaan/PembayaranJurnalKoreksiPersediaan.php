<?php

namespace App\Models\Persediaan;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiPersediaan extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_persediaan';

    protected $primaryKey = 'PembayaranJurnalKoreksiPersediaanID';

    protected $fillable = [
        'JurnalKoreksiPersediaanID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiPersediaanID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiPersediaan::class,
            'JurnalKoreksiPersediaanID',
            'JurnalKoreksiPersediaanID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
