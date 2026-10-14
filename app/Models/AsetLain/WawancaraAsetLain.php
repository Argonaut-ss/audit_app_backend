<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WawancaraAsetLain extends Model
{
    protected $table = 'wawancara_aset_lain';

    protected $primaryKey = 'WawancaraID';

    protected $fillable = [
        'AsetLainID',
        'Pertanyaan',
        'Jawaban',
    ];

    protected $casts = [
        'AsetLainID' => 'integer',
    ];

    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }
}
