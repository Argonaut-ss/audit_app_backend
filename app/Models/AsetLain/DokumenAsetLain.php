<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenAsetLain extends Model
{
    protected $table = 'DokumenAsetLain';

    protected $primaryKey = 'DokumenAsetLainID';

    protected $fillable = [
        'AsetLainID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'DokumenAsetLainID' => 'integer',
        'AsetLainID' => 'integer',
    ];

    /**
     * A document belongs to one aset lain.
     */
    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }
}