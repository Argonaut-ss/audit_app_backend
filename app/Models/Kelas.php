<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kelas extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_kelas',
        'hari',
        'jam',
        'ruangan',
        'periode',
        'tipe_kelas',
        'dosen_id',
        'KasusID',
    ];

    // Relasi: Kelas ini diampu oleh satu Dosen.
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    // Relasi: Kelas ini memiliki banyak Mahasiswa (Many-to-Many via pivot kelas_mahasiswa).
    public function mahasiswas(): BelongsToMany
    {
        return $this->belongsToMany(Mahasiswa::class, 'kelas_mahasiswa', 'kelas_id', 'mahasiswa_id');
    }

    // Relasi: Kelas ini memiliki satu Kasus.
    public function kasus()
    {
        return $this->belongsTo(
            Kasus::class,
            'KasusID',
            'KasusID'
        );
    }

        public function scopeForUser($query, User $user)
    {
        if ($user->isMahasiswa()) {
            return $query->whereHas('mahasiswas', function ($q) use ($user) {
                $q->where('mahasiswa_id', $user->mahasiswa->id);
            });
        }

        if ($user->isDosen()) {
            return $query->where('dosen_id', $user->dosen->id);
        }

        return $query;
    }
}