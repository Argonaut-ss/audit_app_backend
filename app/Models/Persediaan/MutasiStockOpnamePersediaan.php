<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiStockOpnamePersediaan extends Model
{
    protected $table = 'MutasiStockOpnamePersediaan';

    protected $primaryKey = 'MutasiStockOpnamePersediaanID';

    protected $fillable = [
        'PersediaanID',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'MutasiStockOpnamePersediaanID' => 'integer',
        'PersediaanID' => 'integer',
    ];

    /**
     * A mutasi stock opname document belongs to one persediaan.
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