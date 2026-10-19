<?php

namespace App\Models\SetaraKas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonfirmasiBankSetaraKas extends Model
{
    protected $table = 'KonfirmasiBankSetaraKas';

    protected $primaryKey = 'KonfirmasiBankSetaraKasID';

    protected $fillable = [
        'SetaraKasID',
        'NamaFile',
        'NamaFileUpload',
        'MimeType',
        'File',
    ];

    protected $casts = [
        'KonfirmasiBankSetaraKasID' => 'integer',
        'SetaraKasID' => 'integer',
    ];

    /**
     * A konfirmasi bank document belongs to one setara kas.
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