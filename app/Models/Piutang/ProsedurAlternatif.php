<?php

namespace App\Models\Piutang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProsedurAlternatif extends Model
{
    use HasFactory;

    protected $table = 'ProsedurAlternatif';

    protected $primaryKey = 'ProsedurAlternatifID';

    protected $fillable = [
        'PiutangID',
        'KonfirmasiPiutangID',
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
        'PiutangID' => 'integer',
        'KonfirmasiPiutangID' => 'integer',
        'SaldoAkhir' => 'integer',
        'KonfirmasiBayar' => 'boolean',
        'SaldoBata' => 'integer',
    ];

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(
            Piutang::class,
            'PiutangID',
            'PiutangID'
        );
    }

    public function konfirmasiPiutang(): BelongsTo
    {
        return $this->belongsTo(
            KonfirmasiPiutang::class,
            'KonfirmasiPiutangID',
            'KonfirmasiPiutangID'
        );
    }

}