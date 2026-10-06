<?php

namespace App\Models\AsetTetap;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiAsetTetap extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_aset_tetap';

    protected $primaryKey = 'PembayaranJurnalKoreksiAsetTetapID';

    protected $fillable = [
        'JurnalKoreksiAsetTetapID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiAsetTetapID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiAsetTetap::class,
            'JurnalKoreksiAsetTetapID',
            'JurnalKoreksiAsetTetapID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
