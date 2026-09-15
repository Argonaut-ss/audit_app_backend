<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prosedur extends Model
{
    protected $fillable = [
        'piutang_id',
        'nama_prosedur',
        'index',
        'tanggal',
        'checkbox',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'checkbox' => 'boolean',
    ];

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(Piutang::class, 'piutang_id', 'PiutangID');
    }
}