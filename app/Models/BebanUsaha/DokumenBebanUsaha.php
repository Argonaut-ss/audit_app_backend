<?php

namespace App\Models\BebanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenBebanUsaha extends Model
{
    protected $table = 'DokumenBebanUsaha';

    protected $primaryKey = 'DokumenBebanUsahaID';

    protected $fillable = [
        'BebanUsahaID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'DokumenBebanUsahaID' => 'integer',
        'BebanUsahaID' => 'integer',
    ];

    /**
     * A document belongs to one beban usaha.
     */
    public function bebanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            BebanUsaha::class,
            'BebanUsahaID',
            'BebanUsahaID'
        );
    }
}