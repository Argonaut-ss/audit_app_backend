<?php

namespace App\Models\LaporanHasilAudit;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanHasilAudit extends Model
{
    protected $fillable = [
        'JwbKasusID',
        'opini_audit',
    ];

    public const OPINI_AUDIT = [
        'Wajar Tanpa Pengecualian',
        'Wajar Dengan Pengecualian',
        'Tidak Wajar',
        'Tidak Menyatakan Pendapat',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function files(): HasMany
    {
        return $this->hasMany(
            LaporanHasilAuditFile::class,
            'laporan_hasil_audit_id',
            'id'
        );
    }
}