<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class COA extends Model
{
    use HasFactory;

    protected $table = 'coa';

    protected $primaryKey = 'COAID';

    public $timestamps = false;

    protected $fillable = [
        'JwbKasusID',
        'NoAkun',
        'NamaAkun',
        'MappingGroup',
        'MapKelompok',
        'MappingTop',
        'SubMappingTop',
        'Saldo',
        'PerBook',
        'AuditSebelum',
    ];

    protected $casts = [
        'JwbKasusID' => 'integer',
        'NoAkun' => 'integer',
        'PerBook' => 'integer',
        'AuditSebelum' => 'integer',
    ];

    /*
     * coa.JwbKasusID
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