<?php

namespace App\Models\Piutang;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalisisUmur extends Model
{
    protected $table = 'AnalisisUmur';

    protected $primaryKey = 'AnalisisUmurID';

    protected $fillable = [
        'PiutangID',
        'SaldoAuditor',
        'SaldoBB',
        'Selisih',
    ];

    protected $casts = [
        'PiutangID' => 'integer',
        'SaldoAuditor' => 'integer',
        'SaldoBB' => 'integer',
        'Selisih' => 'integer',
    ];

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(Piutang::class, 'PiutangID', 'PiutangID');
    }

    public function hasilAnalisisUmur(): HasMany
    {
        return $this->hasMany(HasilAnalisisUmur::class, 'AnalisisUmurID', 'AnalisisUmurID');
    }
}
