<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pmpj extends Model
{
    use HasFactory;

    protected $table = 'pmpj';
    protected $primaryKey = 'PmpjID';

    protected $fillable = [
        'JwbKasusID',
        'Nama',
        'Jabatan',
        'Alamat',
        'NamaPerusahaan',
        'AlamatPerusahaan',
        'TahunPeriode',
        'NamaFileKTP',
        'FileKTP',
    ];

    protected $hidden = [
        'FileKTP',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(JwbKasus::class, 'JwbKasusID', 'JwbKasusID');
    }

    public function riskRows(): HasMany
    {
        return $this->hasMany(PmpjRiskRow::class, 'PmpjID', 'PmpjID');
    }
}
