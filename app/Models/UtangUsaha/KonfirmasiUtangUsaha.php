<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KonfirmasiUtangUsaha extends Model
{
    use HasFactory;

    protected $table = 'KonfirmasiUtangUsaha';

    protected $primaryKey = 'KonfirmasiUtangUsahaID';

    protected $fillable = [
        'UtangUsahaID',
        'NamaCustomer',
        'KotaCustomer',
        'Jumlah',
        'File',
        'NamaFile',
        'TipeFile',
    ];

    protected $hidden = [
        'File',
    ];

    protected $casts = [
        'UtangUsahaID' => 'integer',
        'Jumlah' => 'integer',
    ];

    public function rekonsiliasiUtangUsaha(): HasMany
    {
        return $this->hasMany(
            RekonsiliasiUtangUsaha::class,
            'KonfirmasiUtangUsahaID',
            'KonfirmasiUtangUsahaID'
        );
    }

    public function utangUsaha(): BelongsTo
    {
        return $this->belongsTo(
            UtangUsaha::class,
            'UtangUsahaID',
            'UtangUsahaID'
        );
    }

    public function rekapBalasan(): HasMany
    {
        return $this->hasMany(
            RekapBalasanUtangUsaha::class,
            'KonfirmasiUtangUsahaID',
            'KonfirmasiUtangUsahaID'
        );
    }

    public function prosedurAlternatif(): HasMany
    {
        return $this->hasMany(
            ProsedurAlternatifUtangUsaha::class,
            'KonfirmasiUtangUsahaID',
            'KonfirmasiUtangUsahaID'
        );
    }
}