<?php

namespace App\Models\TopSchedule;

use App\Models\COA;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopScheduleItem extends Model
{
    protected $table = 'top_schedule_items';

    protected $primaryKey = 'TopScheduleItemID';

    protected $fillable = [
        'TopScheduleID',
        'SubMapping',
        'COAID',
        'Urutan',
    ];

    protected $casts = [
        'TopScheduleID' => 'integer',
        'COAID' => 'integer',
        'Urutan' => 'integer',
    ];

    public function topSchedule(): BelongsTo
    {
        return $this->belongsTo(TopSchedule::class, 'TopScheduleID', 'TopScheduleID');
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(COA::class, 'COAID', 'COAID');
    }
}

