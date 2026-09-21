<?php

namespace App\Models\Piutang;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranJurnalKoreksi extends Model
{
    protected $table = 'PembayaranJurnalKoreksi';

    protected $primaryKey = 'PembayaranJurnalKoreksiID';

    protected $fillable = [
        'JurnalKoreksiID',
        'COAID',
        'Debet',
        'Kredit',
    ];

    protected $casts = [
        'JurnalKoreksiID' => 'integer',
        'COAID' => 'integer',
        'Debet' => 'integer',
        'Kredit' => 'integer',
    ];

    public function jurnalKoreksi(): BelongsTo
    {
        return $this->belongsTo(
            JurnalKoreksi::class,
            'JurnalKoreksiID',
            'JurnalKoreksiID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}
