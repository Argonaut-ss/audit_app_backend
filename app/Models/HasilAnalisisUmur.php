<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilAnalisisUmur extends Model
{
    protected $table = 'HasilAnalisisUmur';

    protected $primaryKey = 'HasilAnalisisUmurID';

    protected $fillable = [
        'AnalisisUmurID',
        'KelompokUmur',
        'Jumlah',
        'Kerugian',
    ];

    protected $casts = [
        'AnalisisUmurID' => 'integer',
        'Jumlah' => 'integer',
        'Kerugian' => 'integer',
    ];

    public function analisisUmur(): BelongsTo
    {
        return $this->belongsTo(AnalisisUmur::class, 'AnalisisUmurID', 'AnalisisUmurID');
    }
}
