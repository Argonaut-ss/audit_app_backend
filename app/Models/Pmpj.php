<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'BeneficialOwner',
        'NamaPerusahaan',
        'AlamatPerusahaan',
        'TahunPeriode',
        'NamaFileKTP',
        'FileKTP',
        'KategoriPenggunaJasa',
        'KategoriBisnisPenggunaJasa',
        'KategoriDomisiliPenggunaJasa',
        'KategoriKhususTambahan',
    ];

    protected $hidden = [
        'FileKTP',
    ];

    public function jwbKasus(): BelongsTo
    {
        return $this->belongsTo(JwbKasus::class, 'JwbKasusID', 'JwbKasusID');
    }
}
