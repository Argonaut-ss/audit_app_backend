<?php

namespace App\Models\BukuBesar;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IsiBukuBesar extends Model
{
    protected $table = 'isi_buku_besar';

    protected $primaryKey = 'IsiBukuBesarID';

    public $timestamps = false;

    protected $fillable = [
        'BukuBesarID',
        'COAID',
        'SubMapping',
    ];

    protected $casts = [
        'BukuBesarID' => 'integer',
        'COAID' => 'integer',
    ];

    public function bukuBesar(): BelongsTo
    {
        return $this->belongsTo(
            BukuBesar::class,
            'BukuBesarID',
            'BukuBesarID'
        );
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(
            COA::class,
            'COAID',
            'COAID'
        );
    }
}
