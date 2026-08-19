<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataClient extends Model
{
    use HasFactory;

    protected $table = 'data_client';

    protected $primaryKey = 'ClientID';

    public $timestamps = false;

    protected $fillable = [
        'NamaClient',
        'NamaKantor',
        'JenisClient',
        'NPWP',
        'AlamatClient',
        'AlamatKantor',
        'HPClient',
        'HPKantor',
        'EmailClient',
        'EmailKantor',
        'URLClient',
        'URLKantor',
        'LogoKantor',
        'LogoPerusahaan',
    ];


    /**
     * Satu client dapat mempunyai
     * banyak kasus/tugas.
     */
    public function kasus()
    {
        return $this->hasOne(
            Kasus::class,
            'ClientID',
            'ClientID'
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
            return $query->whereHas('kasus.kelas.mahasiswas', function ($q) use ($user) {
                $q->where('mahasiswa_id', $user->mahasiswa->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }
}