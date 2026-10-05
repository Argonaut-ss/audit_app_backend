<?php

namespace App\Models\AsetTetap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenAsetTetap extends Model
{
    protected $table = 'DokumenAsetTetap';

    protected $primaryKey = 'DokumenAsetTetapID';

    protected $fillable = [
        'AsetTetapID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'DokumenAsetTetapID' => 'integer',
        'AsetTetapID' => 'integer',
    ];

    /**
     * A document belongs to one aset tetap.
     */
    public function asetTetap(): BelongsTo
    {
        return $this->belongsTo(
            AsetTetap::class,
            'AsetTetapID',
            'AsetTetapID'
        );
    }
}