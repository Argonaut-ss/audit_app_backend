<?php

namespace App\Models\AsetTetap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjiPenyusutanAsetTetap extends Model
{
    protected $table = 'UjiPenyusutanAsetTetap';

    protected $primaryKey = 'UjiPenyusutanAsetTetapID';

    protected $fillable = [
        'AsetTetapID',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'UjiPenyusutanAsetTetapID' => 'integer',
        'AsetTetapID' => 'integer',
    ];

    /**
     * A uji penyusutan document belongs to one aset tetap.
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