<?php

namespace App\Models\PendapatanUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPendapatanUsaha extends Model
{
    protected $table = 'DokumenPendapatanUsaha';

    protected $primaryKey = 'DokumenPendapatanUsahaID';

    protected $fillable = [
        'PendapatanUsahaID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'DokumenPendapatanUsahaID' => 'integer',
        'PendapatanUsahaID' => 'integer',
    ];

    /**
     * A document belongs to one pendapatan usaha.
     */
    public function pendapatanUsaha(): BelongsTo
    {
        return $this->belongsTo(
            PendapatanUsaha::class,
            'PendapatanUsahaID',
            'PendapatanUsahaID'
        );
    }
}