<?php

namespace App\Models\Kas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenKas extends Model
{
    protected $table = 'DokumenKas';

    protected $primaryKey = 'DokumenKasID';

    protected $fillable = [
        'KasID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'DokumenKasID' => 'integer',
        'KasID' => 'integer',
    ];

    /**
     * A document belongs to one kas.
     */
    public function kas(): BelongsTo
    {
        return $this->belongsTo(
            Kas::class,
            'KasID',
            'KasID'
        );
    }
}