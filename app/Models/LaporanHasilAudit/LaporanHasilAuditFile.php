<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanHasilAuditFile extends Model
{
    protected $fillable = [
        'jenis_file',
        'tipe_input',
        'file_data',
        'file_name',
        'mime_type',
        'link',
    ];

    protected $hidden = [
        'file_data',
    ];

    public const JENIS_FILE = [
        'laporan_hasil_audit',
        'laporan_auditor_independen',
        'surat_representasi_manajemen',
        'surat_pmpj',
        'persetujuan_jurnal_koreksi_audit',
        'pernyataan_direksi',
        'persetujuan_penerbitan_laporan_keuangan_audit',
        'persetujuan_penyerahan_lapkeu_audit',
    ];

    public function laporanHasilAudit(): BelongsTo
    {
        return $this->belongsTo(LaporanHasilAudit::class);
    }
}