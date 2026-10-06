<?php

namespace App\Models\Kas;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiKas extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_kas';

    protected $primaryKey = 'PembayaranJurnalKoreksiKasID';

    protected $fillable = [
        'JurnalKoreksiKasID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiKasID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiKas::class,
            'JurnalKoreksiKasID',
            'JurnalKoreksiKasID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
