<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuktiPendukungAsetLain extends Model
{
    protected $table = 'BuktiPendukungAsetLain';

    protected $primaryKey = 'BuktiPendukungAsetLainID';

    protected $fillable = [
        'AsetLainID',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'BuktiPendukungAsetLainID' => 'integer',
        'AsetLainID' => 'integer',
    ];

    /**
     * A supporting document belongs to one aset lain.
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