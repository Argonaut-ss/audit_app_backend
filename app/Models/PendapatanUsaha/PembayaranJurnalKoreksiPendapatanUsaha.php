<?php

namespace App\Models\PendapatanUsaha;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiPendapatanUsaha extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_pendapatan_usaha';

    protected $primaryKey = 'PembayaranJurnalKoreksiPendapatanUsahaID';

    protected $fillable = [
        'JurnalKoreksiPendapatanUsahaID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiPendapatanUsahaID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiPendapatanUsaha::class,
            'JurnalKoreksiPendapatanUsahaID',
            'JurnalKoreksiPendapatanUsahaID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
