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
        'ClientID',
        'NamaTugas',
        'NamaFile',
        'File',
    ];


    protected $hidden = [
        'File',
    ];


    /**
     * =====================================================
     * RELATIONSHIP KE KELAS
     * =====================================================
     *
     * Kelas.KasusID
     *       ↓
     * Kasus.KasusID
     *
     * 1 : 1
     */
    public function kelas()
    {
        return $this->hasOne(
            Kelas::class,
            'KasusID',
            'KasusID'
        );
    }


    /**
     * =====================================================
     * RELATIONSHIP KE DATA CLIENT
     * =====================================================
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