<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JwbKasus extends Model
{
    use HasFactory;

    protected $table = 'jwb_kasus';

    protected $primaryKey = 'JwbKasusID';

    public $timestamps = false;

    protected $fillable = [
        'MahasiswasID',
        'KasusID',
        'JenisPerusahaan',
        'Periode',
        'WaktuMulai',
        'BatasWaktu',
        'Nilai',
    ];

    /*
     * jwb_kasus.KasusID
     *        ↓
     * kasus.KasusID
     */

    public function kasus()
    {
        return $this->belongsTo(
            Kasus::class,
            'KasusID',
            'KasusID'
        );
    }

    /*
     * jwb_kasus.MahasiswasID
     *        ↓
     * mahasiswas.id
     */

    public function mahasiswa()
    {
        return $this->belongsTo(
            Mahasiswa::class,
            'MahasiswasID',
            'id'
        );
    }

    public function perikatan()
    {
        return $this->hasOne(
            Perikatan::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }

    public function detilVerifikasi()
    {
        return $this->hasOne(
            DetilVerifikasi::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }
    
    public function identifikasi()
{
    return $this->hasOne(
        Identifikasi::class,
        'JwbKasusID',
        'JwbKasusID'
    );
}

    public function scopeForUser($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isDosen()) {
            return $query->whereHas('kasus.kelas', function ($q) use ($user) {
                $q->where('dosen_id', $user->dosen->id);
            });
        }

        if ($user->isMahasiswa()) {
            return $query->where('MahasiswasID', $user->mahasiswa->id);
        }

        return $query->whereRaw('1 = 0');
    }
}