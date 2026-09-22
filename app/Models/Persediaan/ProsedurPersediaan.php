<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurPersediaan extends Model
{
    protected $table = 'prosedur_persediaan';

    protected $fillable = [
        'persediaan_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(Persediaan::class,'persediaan_id','PersediaanID');
    }
}