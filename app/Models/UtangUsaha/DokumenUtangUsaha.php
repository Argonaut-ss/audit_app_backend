<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenUtangUsaha extends Model
{
    protected $table = 'DokumenUtangUsaha';

    protected $primaryKey = 'DokumenUtangUsahaID';

    protected $fillable = [
        'UtangUsahaID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'File',
    ];

    protected $casts = [
        'DokumenUtangUsahaID' => 'integer',
        'UtangUsahaID' => 'integer',
    ];

    /**
     * A document belongs to one utang usaha.
     */
    public function utangUsaha(): BelongsTo
    {
        return $this->belongsTo(
            UtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }
}