<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetilVerifikasi extends Model
{
    use HasFactory;
    protected $table = 'detil_verifikasi';

    protected $primaryKey = 'ID';

    public $timestamps = false;
    
    protected $fillable = [
        'JwbKasusID',
        'DetailPermintaan',
        'RincianPersediaan',
        'SPK',
        'HargaPersediaan',
        'SuratTugasKAP',
        'StokOpname',
        'PenugasanKlien',
        'MutasiPersediaan',
        'PernyataanLKA',
        'MutasiAset',
        'PernyataanPMPJ',
        'ObservasiAsset',
        'RepresentationLetter',
        'KepemilikanAset',
        'AktaPendirian',
        'PenjualanAset',
        'SKKPendirian',
        'AsetLain',
        'AktaPerubahan',
        'DokumenSewa',
        'SKKPerubahan',
        'PolisAssurance',
        'SIUP',
        'UtangUsaha',
        'NIB',
        'AKiUtang',
        'NPWPPerusahaan',
        'KonfirmasiUtang',
        'NPWPDirektur',
        'PelunasanUtang',
        'StrukturOrganisasi',
        'RekeningUtangBank',
        'RUPS',
        'KonfirmasiBank',
        'AuditSebelumnya',
        'PembayaranPajak',
        'LaporanKeuangan',
        'UtangLain',
        'BukuBesar',
        'PenambahanModal',
        'CashCount',
        'PenarikanModal',
        'MutasiKas',
        'PembagianModal',
        'RekeningKoranBank',
        'PembagianDeviden',
        'AKBank',
        'SamplingPendapatan',
        'RincianPiutang',
        'SamplingBeban',
        'AKPiutang',
        'SPTBadanSebelumnya',
        'KonfirmasiPiutang',
        'PerhitunganPajakBadan',
        'PelunasanPiutang',
        'SPTDanSPP',
    ];

    protected $casts = [
        'DetailPermintaan' => 'boolean',
        'RincianPersediaan' => 'boolean',
        'SPK' => 'boolean',
        'HargaPersediaan' => 'boolean',
        'SuratTugasKAP' => 'boolean',
        'StokOpname' => 'boolean',
        'PenugasanKlien' => 'boolean',
        'MutasiPersediaan' => 'boolean',
        'PernyataanLKA' => 'boolean',
        'MutasiAset' => 'boolean',
        'PernyataanPMPJ' => 'boolean',
        'ObservasiAsset' => 'boolean',
        'RepresentationLetter' => 'boolean',
        'KepemilikanAset' => 'boolean',
        'AktaPendirian' => 'boolean',
        'PenjualanAset' => 'boolean',
        'SKKPendirian' => 'boolean',
        'AsetLain' => 'boolean',
        'AktaPerubahan' => 'boolean',
        'DokumenSewa' => 'boolean',
        'SKKPerubahan' => 'boolean',
        'PolisAssurance' => 'boolean',
        'SIUP' => 'boolean',
        'UtangUsaha' => 'boolean',
        'NIB' => 'boolean',
        'AKiUtang' => 'boolean',
        'NPWPPerusahaan' => 'boolean',
        'KonfirmasiUtang' => 'boolean',
        'NPWPDirektur' => 'boolean',
        'PelunasanUtang' => 'boolean',
        'StrukturOrganisasi' => 'boolean',
        'RekeningUtangBank' => 'boolean',
        'RUPS' => 'boolean',
        'KonfirmasiBank' => 'boolean',
        'AuditSebelumnya' => 'boolean',
        'PembayaranPajak' => 'boolean',
        'LaporanKeuangan' => 'boolean',
        'UtangLain' => 'boolean',
        'BukuBesar' => 'boolean',
        'PenambahanModal' => 'boolean',
        'CashCount' => 'boolean',
        'PenarikanModal' => 'boolean',
        'MutasiKas' => 'boolean',
        'PembagianModal' => 'boolean',
        'RekeningKoranBank' => 'boolean',
        'PembagianDeviden' => 'boolean',
        'AKBank' => 'boolean',
        'SamplingPendapatan' => 'boolean',
        'RincianPiutang' => 'boolean',
        'SamplingBeban' => 'boolean',
        'AKPiutang' => 'boolean',
        'SPTBadanSebelumnya' => 'boolean',
        'KonfirmasiPiutang' => 'boolean',
        'PerhitunganPajakBadan' => 'boolean',
        'PelunasanPiutang' => 'boolean',
        'SPTDanSPP' => 'boolean',
    ];

    public function jwbKasus()
    {
        return $this->belongsTo(
            JwbKasus::class,
            'JwbKasusID',
            'JwbKasusID'
        );
    }
}