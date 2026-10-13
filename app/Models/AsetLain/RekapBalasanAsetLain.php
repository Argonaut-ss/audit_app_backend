<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapBalasanAsetLain extends Model
{
    use HasFactory;

    protected $table = 'rekap_balasan_aset_lain';

    protected $primaryKey = 'RekapBalasanAsetLainID';

    protected $fillable = [
        'AsetLainID',
        'KonfirmasiAsetLainID',
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
        'AsetLainID' => 'integer',
        'KonfirmasiAsetLainID' => 'integer',
        'SaldoBB' => 'integer',
        'TanggalKirim' => 'date',
        'TanggalJawab' => 'date',
        'SaldoJawab' => 'integer',
        'Selisih' => 'integer',
    ];

    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }

    public function konfirmasiAsetLain(): BelongsTo
    {
        return $this->belongsTo(
            KonfirmasiAsetLain::class,
            'KonfirmasiAsetLainID',
            'KonfirmasiAsetLainID'
        );
    }
}
