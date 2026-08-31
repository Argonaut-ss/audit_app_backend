<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmpjRiskRow extends Model
{
    use HasFactory;

    protected $table = 'pmpj_risk_rows';
    protected $primaryKey = 'PmpjRiskRowID';

    protected $fillable = [
        'PmpjID',
        'profile_name',
        'profile_type',
        'selected_category',
        'risk_level',
        'sort_order',
    ];

    public function pmpj(): BelongsTo
    {
        return $this->belongsTo(Pmpj::class, 'PmpjID', 'PmpjID');
    }
}
