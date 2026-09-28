<?php

namespace App\Models\BebanUsaha;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiBebanUsaha extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_beban_usaha';

    protected $primaryKey = 'PembayaranJurnalKoreksiBebanUsahaID';

    protected $fillable = [
        'JurnalKoreksiBebanUsahaID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiBebanUsahaID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiBebanUsaha::class,
            'JurnalKoreksiBebanUsahaID',
            'JurnalKoreksiBebanUsahaID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
