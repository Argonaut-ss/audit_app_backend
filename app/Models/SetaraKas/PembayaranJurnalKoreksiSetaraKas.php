<?php

namespace App\Models\SetaraKas;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksiSetaraKas extends Model
{
    protected $table = 'pembayaran_jurnal_koreksi_setara_kas';

    protected $primaryKey = 'PembayaranJurnalKoreksiSetaraKasID';

    protected $fillable = [
        'JurnalKoreksiSetaraKasID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiSetaraKasID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksiSetaraKas::class,
            'JurnalKoreksiSetaraKasID',
            'JurnalKoreksiSetaraKasID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}

