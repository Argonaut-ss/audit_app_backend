<?php

namespace App\Models\Piutang;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dokumen extends Model
{
    protected $table = 'dokumen';

    protected $primaryKey = 'DokumenID';

    protected $fillable = [
        'PiutangID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'File',
    ];

    protected $casts = [
        'DokumenID' => 'integer',
        'PiutangID' => 'integer',
    ];

    /**
     * A document belongs to one piutang.
     */
    public function piutang(): BelongsTo
    {
        return $this->belongsTo(
            Piutang::class,
            'PiutangID',
            'PiutangID'
        );
    }
}