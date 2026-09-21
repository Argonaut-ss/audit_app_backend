<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurAlternatifUtangUsaha extends Model
{
    use HasFactory;

    protected $table = 'prosedur_alt_utang';

    protected $primaryKey = 'ProsedurAlternatifUtangUsahaID';

    protected $fillable = [
        'UtangUsahaID',
        'KonfirmasiUtangUsahaID',
        'SaldoAkhir',
        'KonfirmasiBayar',
        'BuktiBayar',
        'SaldoBata',
        'FileBukti',
        'NamaFile',
        'TipeFile',
    ];

    protected $hidden = [
        'FileBukti',
    ];

    protected $casts = [
        'UtangUsahaID' => 'integer',
        'KonfirmasiUtangUsahaID' => 'integer',
        'SaldoAkhir' => 'integer',
        'KonfirmasiBayar' => 'boolean',
        'SaldoBata' => 'integer',
    ];

    public function utangUsaha(): BelongsTo
    {
        return $this->belongsTo(
            UtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }

    public function konfirmasiUtangUsaha(): BelongsTo
    {
        return $this->belongsTo(
            KonfirmasiUtangUsaha::class,
            'KonfirmasiUtangUsahaID',
            'KonfirmasiUtangUsahaID'
        );
    }

}