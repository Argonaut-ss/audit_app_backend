<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekeningKoranSetaraKas extends Model
{
    protected $table = 'RekeningKoranSetaraKas';

    protected $primaryKey = 'RekeningKoranSetaraKasID';

    protected $fillable = [
        'SetaraKasID',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'RekeningKoranSetaraKasID' => 'integer',
        'SetaraKasID' => 'integer',
    ];

    /**
     * A rekening koran document belongs to one setara kas.
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