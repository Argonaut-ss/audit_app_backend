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
        'NamaLain',
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
        'PerBook' => 'decimal:2',
        'AuditSebelum' => 'decimal:2',
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