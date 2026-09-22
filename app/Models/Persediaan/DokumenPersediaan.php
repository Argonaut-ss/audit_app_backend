<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPersediaan extends Model
{
    protected $table = 'DokumenPersediaan';

    protected $primaryKey = 'DokumenPersediaanID';

    protected $fillable = [
        'PersediaanID',
        'TipeFile',
        'NamaFile',
        'NamaFileUpload',
        'File',
    ];

    protected $casts = [
        'DokumenPersediaanID' => 'integer',
        'PersediaanID' => 'integer',
    ];

    /**
     * A document belongs to one persediaan.
     */
    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(
            Persediaan::class,
            'PersediaanID',
            'PersediaanID'
        );
    }
}