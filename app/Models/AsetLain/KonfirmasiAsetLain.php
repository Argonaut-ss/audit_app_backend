<?php

namespace App\Models\AsetLain;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KonfirmasiAsetLain extends Model
{
    use HasFactory;

    protected $table = 'KonfirmasiAsetLain';

    protected $primaryKey = 'KonfirmasiAsetLainID';

    protected $fillable = [
        'AsetLainID',
        'NamaCustomer',
        'KotaCustomer',
        'Jumlah',
        'File',
        'NamaFile',
        'TipeFile',
    ];

    protected $hidden = [
        'File',
    ];

    protected $casts = [
        'AsetLainID' => 'integer',
        'Jumlah' => 'integer',
    ];

    public function asetLain(): BelongsTo
    {
        return $this->belongsTo(
            AsetLain::class,
            'AsetLainID',
            'AsetLainID'
        );
    }
}