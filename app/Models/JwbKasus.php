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
        'SubmisID',
        'KasusID',
        'nim',
        'TanggalUpload',
        'Nilai',
        'File',
    ];

    protected $hidden = [
        'File',
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
     * jwb_kasus.nim
     *        ↓
     * mahasiswas.nim
     */

    public function mahasiswa()
    {
        return $this->belongsTo(
            Mahasiswa::class,
            'nim',
            'nim'
        );
    }
}