<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Identifikasi extends Model
{
    use HasFactory;

    protected $table = 'identifikasi';
    protected $primaryKey = 'IdentifikasiID';
    public $timestamps = false;
    protected $fillable = [
        'JwbKasusID',
        'Tahun',
        'OpiniAudit',
        'NoSuratPengesahan',
        'LaporanSPT',
        'NoSuratKeputusan',
        'LaporanKeuangan',
        'TipePerikatan',
        'SumberDana',
        'JenisPerikatan',
        'TujuanTransaksi',
        'StandardAkutansi',
        'TotalAset',
        'NamaKAP',
        'Pendapatan',
        'LabaRugi',
        'KontakNama',
        'KontakJabatan',
        'KontakNomor',
        'KontakEmail',
        'FileAkte',
        'FileNPWP',
        'FileStrukturOrg',
    ];

    protected $hidden = [
        'FileAkte',
        'FileNPWP',
        'FileStrukturOrg',
    ];

    protected $casts = [
        'Tahun' => 'integer',
        'TotalAset' => 'integer',
        'Pendapatan' => 'integer',
        'LabaRugi' => 'integer',
    ];

    public function jwbKasus()
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }
}