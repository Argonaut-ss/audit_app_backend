<?php

namespace App\Models\BukuBesar;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BukuBesar extends Model
{
    public const TOP_MAPPINGS = [
        'A' => 'Kas dan Setara Kas',
        'B' => 'Piutang',
        'C' => 'Persediaan',
        'D' => 'Aset Lain-Lain',
        'E' => 'Aset Tetap',
        'F' => 'Utang Usaha',
        'G' => 'Utang Bank',
        'H' => 'Utang Pajak',
        'I' => 'Ekuitas',
        'J' => 'Penjualan',
        'K' => 'Beban Pokok Penjualan',
        'L' => 'Beban Usaha',
        'M' => 'Pendapatan (Beban) Lain-lain',
        'N' => 'Beban Pajak Penghasilan',
    ];

    protected $table = 'buku_besar';

    protected $primaryKey = 'BukuBesarID';

    public $timestamps = false;

    protected $fillable = [
        'JwbKasusID',
        'Mapping',
        'TanggalAuditSblm',
    ];

    protected $casts = [
        'JwbKasusID' => 'integer',
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
