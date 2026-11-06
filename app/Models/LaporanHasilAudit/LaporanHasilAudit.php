<?php

namespace App\Models\LaporanHasilAudit;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LaporanHasilAudit extends Model
{
    protected $table = 'laporan_hasil_audit';

    protected $primaryKey = 'LaporanHasilAuditID';

    public $timestamps = false;

    protected $fillable = [
        'JwbKasusID',
        'OpiniAudit',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function dokumenLha(): HasOne
    {
        return $this->hasOne(
            DokumenLHA::class,
            'LaporanHasilAuditID',
            'LaporanHasilAuditID'
        );
    }
}
