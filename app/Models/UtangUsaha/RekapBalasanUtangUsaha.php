<?php

namespace App\Models\UtangUsaha;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapBalasanUtangUsaha extends Model
{
    use HasFactory;

    protected $table = 'rekap_balasan_utang_usaha';

    protected $primaryKey = 'RekapBalasanUtangUsahaID';

    protected $fillable = [
        'UtangUsahaID',
        'KonfirmasiUtangUsahaID',
        'SaldoBB',
        'TanggalKirim',
        'MetodeKirim',
        'TanggalJawab',
        'SaldoJawab',
        'Selisih',
        'FileBukti',
        'NamaFile',
        'TipeFile',
        'Status',
    ];

    protected $hidden = [
        'FileBukti',
    ];

    protected $casts = [
        'UtangUsahaID' => 'integer',
        'KonfirmasiUtangUsahaID' => 'integer',
        'SaldoBB' => 'integer',
        'TanggalKirim' => 'date',
        'TanggalJawab' => 'date',
        'SaldoJawab' => 'integer',
        'Selisih' => 'integer',
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
