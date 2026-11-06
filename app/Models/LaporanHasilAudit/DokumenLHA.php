<?php

namespace App\Models\LaporanHasilAudit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenLHA extends Model
{
    protected $table = 'dokumen_lha';

    protected $primaryKey = 'DokumenLHA';

    public $timestamps = false;

    protected $fillable = [
        'LaporanHasilAuditID',
        'NamaLHA',
        'FileLHA',
        'TipeLHA',
        'LinkLHA',
        'NamaLAI',
        'FileLAI',
        'TipeLAI',
        'LinkLAI',
        'NamaSRM',
        'FileSRM',
        'TipeSRM',
        'LinkSRM',
        'NamaSPMPJ',
        'FileSPMPJ',
        'TipeSPMPJ',
        'LinkSPMPJ',
        'NamaPJKA',
        'FilePJKA',
        'TipePJKA',
        'LinkPJKA',
        'NamaPD',
        'FilePD',
        'TipePD',
        'LinkPD',
        'NamaPPLKA',
        'FilePPLKA',
        'TipePPLKA',
        'LinkPPLKA',
        'NamaPLKAPB',
        'FilePLKAPB',
        'TipePLKAPB',
        'LinkPLKAPB',
    ];

    protected $hidden = [
        'FileLHA',
        'FileLAI',
        'FileSRM',
        'FileSPMPJ',
        'FilePJKA',
        'FilePD',
        'FilePPLKA',
        'FilePLKAPB',
    ];

    public function laporanHasilAudit(): BelongsTo
    {
        return $this->belongsTo(
            LaporanHasilAudit::class,
            'LaporanHasilAuditID',
            'LaporanHasilAuditID'
        );
    }
}
