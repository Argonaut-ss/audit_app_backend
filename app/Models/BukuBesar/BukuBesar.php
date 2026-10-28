<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class BukuBesar extends Model
{
    protected $table = 'buku_besar';

    protected $primaryKey = 'BukuBesarID';

    public $timestamps = false;

    protected $fillable = [
        'JwbKasusID',
        'Top',
        'Mapping',
        'TanggalPerBook',
        'TanggalAuditJln',
        'TanggalAuditSblm',
    ];

    protected $casts = [
        'JwbKasusID' => 'integer',
        'TanggalPerBook' => 'date',
        'TanggalAuditJln' => 'date',
        'TanggalAuditSblm' => 'date',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function isiBukuBesar(): HasMany
    {
        return $this->hasMany(
            IsiBukuBesar::class,
            'BukuBesarID',
            'BukuBesarID'
        );
    }
}
