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
        'NamaTugas',
        'NamaFile',
        'File',
    ];

    protected $hidden = [
        'File',
    ];

    public function kelas()
    {
        return $this->belongsTo(
            Kelas::class,
            'KelasID',
            'kode_kelas'
        );
    }
}