<?php

namespace App\Models\UtangUsaha;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiUtangUsaha extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_utang_usaha';

    protected $primaryKey = 'PembayaranJurnalKoreksiUtangUsahaID';

    protected $fillable = [
        'JurnalKoreksiUtangUsahaID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiUtangUsahaID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiUtangUsaha::class,
            'JurnalKoreksiUtangUsahaID',
            'JurnalKoreksiUtangUsahaID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
