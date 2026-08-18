<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kasus extends Model
{
    use HasFactory;

    protected $table = 'kasus';

    protected $primaryKey = 'KasusID';

    public $timestamps = false;

    protected $fillable = [
        'KelasID',
        'TipeKelas',
        'ClientID',
        'NamaTugas',
        'NamaFile',
        'File',
    ];

    /**
     * File binary jangan dikirim
     * ketika model diubah menjadi JSON.
     */
    protected $hidden = [
        'File',
    ];


    /**
     * =====================================================
     * RELATIONSHIP KE KELAS
     * =====================================================
     */
    public function kelas()
    {
        return $this->belongsTo(
            Kelas::class,
            'KelasID',
            'kode_kelas'
        );
    }


    /*
     * Kasus.ClientID
     *       ↓
     * DataClient.ClientID
     */
    public function client()
    {
        return $this->belongsTo(
            DataClient::class,
            'ClientID',
            'ClientID'
        );
    }
}