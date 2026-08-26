<?php

namespace App\Models;

/*
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'audits';

    protected $primaryKey = 'AuditID';

    protected $fillable = [
        'user_id',
        'KasusID',
        'jenis_perusahaan',
        'periode_audit',
        'waktu_mulai',
        'batas_waktu',
    ];

    protected $casts = [
        'periode_audit' => 'date:Y-m-d',
        'waktu_mulai' => 'date:Y-m-d',
        'batas_waktu' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(Kasus::class, 'KasusID', 'KasusID');
    }
}

*/