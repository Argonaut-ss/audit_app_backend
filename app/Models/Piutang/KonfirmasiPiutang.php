<?php

namespace App\Models\Piutang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KonfirmasiPiutang extends Model
{
    use HasFactory;

    protected $table = 'KonfirmasiPiutang';

    protected $primaryKey = 'KonfirmasiPiutangID';

    protected $fillable = [
        'PiutangID',
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
        'PiutangID' => 'integer',
        'Jumlah' => 'integer',
    ];

    public function rekonsiliasiPiutang(): HasMany
    {
        return $this->hasMany(
            RekonsiliasiPiutang::class,
            'KonfirmasiPiutangID',
            'KonfirmasiPiutangID'
        );
    }

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(
            Piutang::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function rekapBalasan(): HasMany
    {
        return $this->hasMany(
            RekapBalasan::class,
            'KonfirmasiPiutangID',
            'KonfirmasiPiutangID'
        );
    }

    public function prosedurAlternatif(): HasMany
    {
        return $this->hasMany(
            ProsedurAlternatif::class,
            'KonfirmasiPiutangID',
            'KonfirmasiPiutangID'
        );
    }
}