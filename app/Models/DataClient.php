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
    ];


    /**
     * Satu client dapat mempunyai
     * banyak kasus/tugas.
     */
    public function kasus()
    {
        return $this->hasMany(
            Kasus::class,
            'ClientID',
            'ClientID'
        );
    }
}