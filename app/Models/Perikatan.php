<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perikatan extends Model
{
    use HasFactory;

    protected $table = 'perikatan';

    protected $primaryKey = 'PerikatanID';

    public $timestamps = false;

    protected $fillable = [
        'JwbKasusID',
        'FileProposal',
        'FileSPK',
        'FileSuratTugas',
        'FilePenugasan',
        'FileIndependensi',
    ];

    protected $hidden = [
        'FileProposal',
        'FileSPK',
        'FileSuratTugas',
        'FilePenugasan',
        'FileIndependensi',
    ];

    /*
     * perikatan.JwbKasusID
     *        ↓
     * jwb_kasus.JwbKasusID
     */

    public function jwbKasus()
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }
}