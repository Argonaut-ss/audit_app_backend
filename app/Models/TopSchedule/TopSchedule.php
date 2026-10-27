<?php

namespace App\Models\TopSchedule;

use App\Models\JwbKasus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TopSchedule extends Model
{
    protected $table = 'top_schedules';

    protected $primaryKey = 'TopScheduleID';

    protected $fillable = [
        'JwbKasusID',
        'Top',
        'Mapping',
        'TanggalPerBook',
        'TanggalAuditBerjalan',
        'TanggalAuditSebelum',
        'IsFinal',
    ];

    protected $casts = [
        'JwbKasusID' => 'integer',
        'TanggalPerBook' => 'date',
        'TanggalAuditBerjalan' => 'date',
        'TanggalAuditSebelum' => 'date',
        'IsFinal' => 'boolean',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(JwbKasus::class, 'JwbKasusID', 'JwbKasusID');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TopScheduleItem::class, 'TopScheduleID', 'TopScheduleID')
            ->orderBy('Urutan', 'asc');
    }
}

