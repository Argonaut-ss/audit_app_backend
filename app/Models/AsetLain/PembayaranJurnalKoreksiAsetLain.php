<?php

namespace App\Models\AsetLain;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiAsetLain extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_aset_lain';

    protected $primaryKey = 'PembayaranJurnalKoreksiAsetLainID';

    protected $fillable = [
        'JurnalKoreksiAsetLainID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiAsetLainID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiAsetLain::class,
            'JurnalKoreksiAsetLainID',
            'JurnalKoreksiAsetLainID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
