<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenSetaraKas extends Model
{
    protected $table = 'DokumenSetaraKas';

    protected $primaryKey = 'DokumenSetaraKasID';

    protected $fillable = [
        'SetaraKasID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'DokumenSetaraKasID' => 'integer',
        'SetaraKasID' => 'integer',
    ];

    /**
     * A document belongs to one setara kas.
     */
    public function setaraKas(): BelongsTo
    {
        return $this->belongsTo(
            SetaraKas::class,
            'SetaraKasID',
            'SetaraKasID'
        );
    }
}