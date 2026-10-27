<?php

namespace Database\Seeders;

use App\Models\AsetLain\AsetLain;
use App\Models\AsetLain\JurnalKoreksiAsetLain;
use App\Models\AsetTetap\AsetTetap;
use App\Models\AsetTetap\JurnalKoreksiAsetTetap;
use App\Models\BebanUsaha\BebanUsaha;
use App\Models\BebanUsaha\JurnalKoreksiBebanUsaha;
use App\Models\COA;
use App\Models\JwbKasus;
use App\Models\Kas\JurnalKoreksiKas;
use App\Models\Kas\Kas;
use App\Models\PendapatanUsaha\JurnalKoreksiPendapatanUsaha;
use App\Models\PendapatanUsaha\PendapatanUsaha;
use App\Models\Persediaan\JurnalKoreksiPersediaan;
use App\Models\Persediaan\Persediaan;
use App\Models\Piutang\JurnalKoreksi as JurnalKoreksiPiutang;
use App\Models\Piutang\Piutang;
use App\Models\SetaraKas\JurnalKoreksiSetaraKas;
use App\Models\SetaraKas\SetaraKas;
use App\Models\UtangUsaha\JurnalKoreksiUtangUsaha;
use App\Models\UtangUsaha\UtangUsaha;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TopScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Ambil semua JwbKasus atau minimal JwbKasus pertama
            $cases = JwbKasus::all();
            if ($cases->isEmpty()) {
                $this->command?->warn('Tidak ada data JwbKasus ditemukan. Jalankan TestSeeder terlebih dahulu.');
                return;
            }

            $coaDataset = [
                ['NoAkun' => '1-0000', 'NamaAkun' => 'Kas di Bank', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Kas dan Setara Kas', 'SubMappingTop' => 'Kas dan Setara Kas', 'Saldo' => 'debit', 'PerBook' => 10003512720, 'AuditSebelum' => 8497338192],
                ['NoAkun' => '1-1111', 'NamaAkun' => 'Bank ABC', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Kas dan Setara Kas', 'SubMappingTop' => 'Rincian Setara Kas - Bank', 'Saldo' => 'debit', 'PerBook' => 2500000000, 'AuditSebelum' => 1950000000],
                ['NoAkun' => '1-1112', 'NamaAkun' => 'Bank BCD', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Kas dan Setara Kas', 'SubMappingTop' => 'Rincian Setara Kas - Bank', 'Saldo' => 'debit', 'PerBook' => 3334504240, 'AuditSebelum' => 2832446064],
                ['NoAkun' => '1-1113', 'NamaAkun' => 'Bank XYZ', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Kas dan Setara Kas', 'SubMappingTop' => 'Rincian Setara Kas - Bank', 'Saldo' => 'debit', 'PerBook' => 4169008480, 'AuditSebelum' => 3714892128],
                ['NoAkun' => '1-1120', 'NamaAkun' => 'Kas Kecil', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Kas dan Setara Kas', 'SubMappingTop' => 'Kas dan Setara Kas', 'Saldo' => 'debit', 'PerBook' => 500175636, 'AuditSebelum' => 424866910],
                ['NoAkun' => '1-1210', 'NamaAkun' => 'Piutang Dagang', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Piutang', 'SubMappingTop' => 'Piutang', 'Saldo' => 'debit', 'PerBook' => 16020000402, 'AuditSebelum' => 13494675891],
                ['NoAkun' => '1-1211', 'NamaAkun' => 'Piutang Karyawan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Piutang', 'SubMappingTop' => 'Piutang', 'Saldo' => 'debit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '1-1220', 'NamaAkun' => 'Cadangan Kerugian Piutang', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Piutang', 'SubMappingTop' => 'Piutang', 'Saldo' => 'kredit', 'PerBook' => -81278541, 'AuditSebelum' => -69040873],
                ['NoAkun' => '1-1300', 'NamaAkun' => 'Persediaan Barang Dagang', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Persediaan', 'SubMappingTop' => 'Persediaan', 'Saldo' => 'debit', 'PerBook' => 10003525000, 'AuditSebelum' => 8497338192],
                ['NoAkun' => '1-1410', 'NamaAkun' => 'Bahan Habis Pakai', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Persediaan', 'SubMappingTop' => 'Persediaan', 'Saldo' => 'debit', 'PerBook' => 162697591, 'AuditSebelum' => 177975273],
                ['NoAkun' => '1-1420', 'NamaAkun' => 'Sewa Dibayar Dimuka', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Aset Lain-Lain', 'SubMappingTop' => 'Aset Lain-Lain', 'Saldo' => 'debit', 'PerBook' => 1212785408, 'AuditSebelum' => 990408728],
                ['NoAkun' => '1-1500', 'NamaAkun' => 'PPN Masukan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Aset Lain-Lain', 'SubMappingTop' => 'Aset Lain-Lain', 'Saldo' => 'debit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '1-1600', 'NamaAkun' => 'Piutang PPN', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Aset Lain-Lain', 'SubMappingTop' => 'Aset Lain-Lain', 'Saldo' => 'debit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '1-1700', 'NamaAkun' => 'Angsuran Pajak Penghasilan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Lancar', 'MappingTop' => 'Aset Lain-Lain', 'SubMappingTop' => 'Aset Lain-Lain', 'Saldo' => 'debit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '1-2141', 'NamaAkun' => 'Peralatan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Aset Tetap', 'Saldo' => 'debit', 'PerBook' => 602747125, 'AuditSebelum' => 573747125],
                ['NoAkun' => '1-2142', 'NamaAkun' => 'Akumulasi Depresiasi Peralatan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Akumulasi Penyusutan Aset Tetap', 'Saldo' => 'kredit', 'PerBook' => 489053265, 'AuditSebelum' => 421318985],
                ['NoAkun' => '1-2143', 'NamaAkun' => 'Bangunan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Aset Tetap', 'Saldo' => 'debit', 'PerBook' => 2327100000, 'AuditSebelum' => 2327100000],
                ['NoAkun' => '1-2144', 'NamaAkun' => 'Akumulasi Depresiasi Bangunan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Akumulasi Penyusutan Aset Tetap', 'Saldo' => 'kredit', 'PerBook' => 659467849, 'AuditSebelum' => 450435000],
                ['NoAkun' => '1-2145', 'NamaAkun' => 'Kendaraan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Aset Tetap', 'Saldo' => 'debit', 'PerBook' => 491400000, 'AuditSebelum' => 491400000],
                ['NoAkun' => '1-2146', 'NamaAkun' => 'Akumulasi Depresiasi Kendaraan', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Akumulasi Penyusutan Aset Tetap', 'Saldo' => 'kredit', 'PerBook' => 264648493, 'AuditSebelum' => 172275000],
                ['NoAkun' => '1-2147', 'NamaAkun' => 'Mesin', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Aset Tetap', 'Saldo' => 'debit', 'PerBook' => 1317201200, 'AuditSebelum' => 1305201200],
                ['NoAkun' => '1-2148', 'NamaAkun' => 'Akumulasi Depresiasi Mesin', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Akumulasi Penyusutan Aset Tetap', 'Saldo' => 'kredit', 'PerBook' => 758788130, 'AuditSebelum' => 595514691],
                ['NoAkun' => '1-2149', 'NamaAkun' => 'Tanah', 'NamaLain' => '', 'MappingGroup' => 'Aset', 'MapKelompok' => 'Aset Tidak Lancar', 'MappingTop' => 'Aset Tetap', 'SubMappingTop' => 'Aset Tetap', 'Saldo' => 'debit', 'PerBook' => 4147852226, 'AuditSebelum' => 4147852226],
                ['NoAkun' => '2-1100', 'NamaAkun' => 'Utang Dagang', 'NamaLain' => '', 'MappingGroup' => 'Liabilitas', 'MapKelompok' => 'Liabilitas Jangka Pendek', 'MappingTop' => 'Utang Usaha', 'SubMappingTop' => 'Utang Usaha', 'Saldo' => 'kredit', 'PerBook' => 10162509500, 'AuditSebelum' => 9986838605],
                ['NoAkun' => '2-1200', 'NamaAkun' => 'Utang Beban', 'NamaLain' => '', 'MappingGroup' => 'Liabilitas', 'MapKelompok' => 'Liabilitas Jangka Pendek', 'MappingTop' => 'Utang Usaha', 'SubMappingTop' => 'Utang Usaha', 'Saldo' => 'kredit', 'PerBook' => 198000000, 'AuditSebelum' => 443000000],
                ['NoAkun' => '2-1300', 'NamaAkun' => 'Utang Pajak Penghasilan', 'NamaLain' => '', 'MappingGroup' => 'Liabilitas', 'MapKelompok' => 'Liabilitas Jangka Pendek', 'MappingTop' => 'Utang Pajak', 'SubMappingTop' => 'Utang Pajak', 'Saldo' => 'kredit', 'PerBook' => 36938908, 'AuditSebelum' => 5469976],
                ['NoAkun' => '2-1400', 'NamaAkun' => 'PPN Keluaran', 'NamaLain' => '', 'MappingGroup' => 'Liabilitas', 'MapKelompok' => 'Liabilitas Jangka Pendek', 'MappingTop' => 'Utang Pajak', 'SubMappingTop' => 'Utang Pajak', 'Saldo' => 'kredit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '2-1500', 'NamaAkun' => 'Utang PPN', 'NamaLain' => '', 'MappingGroup' => 'Liabilitas', 'MapKelompok' => 'Liabilitas Jangka Pendek', 'MappingTop' => 'Utang Pajak', 'SubMappingTop' => 'Utang Pajak', 'Saldo' => 'kredit', 'PerBook' => 859350000, 'AuditSebelum' => 1408000000],
                ['NoAkun' => '2-2100', 'NamaAkun' => 'Utang Bank', 'NamaLain' => '', 'MappingGroup' => 'Liabilitas', 'MapKelompok' => 'Liabilitas Jangka Panjang', 'MappingTop' => 'Utang Bank', 'SubMappingTop' => 'Utang Bank', 'Saldo' => 'kredit', 'PerBook' => 10807643856, 'AuditSebelum' => 10957714385],
                ['NoAkun' => '3-1100', 'NamaAkun' => 'Modal Saham', 'NamaLain' => '', 'MappingGroup' => 'Ekuitas', 'MapKelompok' => 'Ekuitas', 'MappingTop' => 'Ekuitas', 'SubMappingTop' => 'Ekuitas', 'Saldo' => 'kredit', 'PerBook' => 5600000000, 'AuditSebelum' => 5600000000],
                ['NoAkun' => '3-1200', 'NamaAkun' => 'Dividen', 'NamaLain' => '', 'MappingGroup' => 'Ekuitas', 'MapKelompok' => 'Ekuitas', 'MappingTop' => 'Ekuitas', 'SubMappingTop' => 'Ekuitas', 'Saldo' => 'debit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '3-2100', 'NamaAkun' => 'Saldo Laba', 'NamaLain' => '', 'MappingGroup' => 'Ekuitas', 'MapKelompok' => 'Ekuitas', 'MappingTop' => 'Ekuitas', 'SubMappingTop' => 'Ekuitas', 'Saldo' => 'kredit', 'PerBook' => 10818296222, 'AuditSebelum' => 5883630055],
                ['NoAkun' => '3-2200', 'NamaAkun' => 'Laba Tahun Berjalan', 'NamaLain' => '', 'MappingGroup' => 'Ekuitas', 'MapKelompok' => 'Ekuitas', 'MappingTop' => 'Ekuitas', 'SubMappingTop' => 'Ekuitas', 'Saldo' => 'kredit', 'PerBook' => 6053022544, 'AuditSebelum' => 4934666167],
                ['NoAkun' => '4-1100', 'NamaAkun' => 'Penjualan', 'NamaLain' => '', 'MappingGroup' => 'Pendapatan', 'MapKelompok' => 'Pendapatan', 'MappingTop' => 'Penjualan', 'SubMappingTop' => 'Penjualan', 'Saldo' => 'kredit', 'PerBook' => 62558966780, 'AuditSebelum' => 53108363700],
                ['NoAkun' => '4-1200', 'NamaAkun' => 'Diskon Penjualan', 'NamaLain' => '', 'MappingGroup' => 'Pendapatan', 'MapKelompok' => 'Pendapatan', 'MappingTop' => 'Penjualan', 'SubMappingTop' => 'Penjualan', 'Saldo' => 'kredit', 'PerBook' => -1875658635, 'AuditSebelum' => -2021806984],
                ['NoAkun' => '4-1300', 'NamaAkun' => 'Retur Penjualan', 'NamaLain' => '', 'MappingGroup' => 'Pendapatan', 'MapKelompok' => 'Pendapatan', 'MappingTop' => 'Penjualan', 'SubMappingTop' => 'Penjualan', 'Saldo' => 'kredit', 'PerBook' => -1312961045, 'AuditSebelum' => -1115275638],
                ['NoAkun' => '4-1400', 'NamaAkun' => 'Pendapatan Bunga', 'NamaLain' => '', 'MappingGroup' => 'Pendapatan', 'MapKelompok' => 'Pendapatan Lain-Lain', 'MappingTop' => 'Pendapatan (Beban) Lain-lain', 'SubMappingTop' => 'Pendapatan Lain-Lain', 'Saldo' => 'kredit', 'PerBook' => 53504550, 'AuditSebelum' => 44863630],
                ['NoAkun' => '4-1500', 'NamaAkun' => 'Pendapatan Lain-Lain', 'NamaLain' => '', 'MappingGroup' => 'Pendapatan', 'MapKelompok' => 'Pendapatan Lain-Lain', 'MappingTop' => 'Pendapatan (Beban) Lain-lain', 'SubMappingTop' => 'Pendapatan Lain-Lain', 'Saldo' => 'kredit', 'PerBook' => 0, 'AuditSebelum' => 0],
                ['NoAkun' => '5-1100', 'NamaAkun' => 'Harga Pokok Penjualan', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'HPP', 'MappingTop' => 'Beban Pokok Penjualan', 'SubMappingTop' => 'Beban Pokok Penjualan', 'Saldo' => 'debit', 'PerBook' => 50017563600, 'AuditSebelum' => 42486690960],
                ['NoAkun' => '5-2100', 'NamaAkun' => 'Beban Gaji', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 1319350354, 'AuditSebelum' => 1001479940],
                ['NoAkun' => '5-2200', 'NamaAkun' => 'Beban Telepon dan Listrik', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 169972692, 'AuditSebelum' => 139233056],
                ['NoAkun' => '5-2300', 'NamaAkun' => 'Beban Kerugian Piutang', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 41200000, 'AuditSebelum' => 0],
                ['NoAkun' => '5-2400', 'NamaAkun' => 'Beban Bahan Habis Pakai', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 339400000, 'AuditSebelum' => 180400000],
                ['NoAkun' => '5-2500', 'NamaAkun' => 'Beban Sewa', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 227200000, 'AuditSebelum' => 203200000],
                ['NoAkun' => '5-2600', 'NamaAkun' => 'Beban Depresiasi', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 413638950, 'AuditSebelum' => 406338950],
                ['NoAkun' => '5-2700', 'NamaAkun' => 'Beban Iklan', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Operasional', 'MappingTop' => 'Beban Usaha', 'SubMappingTop' => 'Beban Usaha', 'Saldo' => 'debit', 'PerBook' => 26364602, 'AuditSebelum' => 22665659],
                ['NoAkun' => '5-3100', 'NamaAkun' => 'Beban Bunga Sewa', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Lain-Lain', 'MappingTop' => 'Pendapatan (Beban) Lain-lain', 'SubMappingTop' => 'Beban Lain-Lain', 'Saldo' => 'debit', 'PerBook' => 7200000, 'AuditSebelum' => 7200000],
                ['NoAkun' => '5-3200', 'NamaAkun' => 'Pajak Penghasilan', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Pajak', 'MappingTop' => 'Beban Pajak Penghasilan', 'SubMappingTop' => 'Pajak Penghasilan', 'Saldo' => 'debit', 'PerBook' => 736938908, 'AuditSebelum' => 605469976],
                ['NoAkun' => '5-3300', 'NamaAkun' => 'Beban Lain-Lain', 'NamaLain' => '', 'MappingGroup' => 'Beban', 'MapKelompok' => 'Beban Lain-Lain', 'MappingTop' => 'Pendapatan (Beban) Lain-lain', 'SubMappingTop' => 'Beban Lain-Lain', 'Saldo' => 'debit', 'PerBook' => 72000000, 'AuditSebelum' => 28800000],
            ];

            foreach ($cases as $jwbKasus) {
                // 1. Seed seluruh COA
                $coaMap = [];
                foreach ($coaDataset as $row) {
                    $coa = COA::updateOrCreate(
                        [
                            'JwbKasusID' => $jwbKasus->JwbKasusID,
                            'NoAkun' => $row['NoAkun'],
                        ],
                        [
                            'NamaAkun' => $row['NamaAkun'],
                            'NamaLain' => $row['NamaLain'],
                            'MappingGroup' => $row['MappingGroup'],
                            'MapKelompok' => $row['MapKelompok'],
                            'MappingTop' => $row['MappingTop'],
                            'SubMappingTop' => $row['SubMappingTop'],
                            'Saldo' => $row['Saldo'],
                            'PerBook' => $row['PerBook'],
                            'AuditSebelum' => $row['AuditSebelum'],
                        ]
                    );
                    $coaMap[$row['NoAkun']] = $coa;
                }

                // 2. Pastikan entitas parent ke-9 modul substantif ada
                $asetTetap = AsetTetap::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $asetLain = AsetLain::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $piutang = Piutang::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $utangUsaha = UtangUsaha::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $bebanUsaha = BebanUsaha::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $kas = Kas::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $setaraKas = SetaraKas::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $persediaan = Persediaan::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);
                $pendapatanUsaha = PendapatanUsaha::firstOrCreate(['JwbKasusID' => $jwbKasus->JwbKasusID]);

                // 3. Bersihkan jurnal koreksi lama agar idempoten
                $asetTetap->jurnalKoreksi()->delete();
                $asetLain->jurnalKoreksi()->delete();
                $piutang->jurnalKoreksi()->delete();
                $utangUsaha->jurnalKoreksi()->delete();
                $bebanUsaha->jurnalKoreksi()->delete();
                $kas->jurnalKoreksi()->delete();
                $setaraKas->jurnalKoreksi()->delete();
                $persediaan->jurnalKoreksi()->delete();
                $pendapatanUsaha->jurnalKoreksi()->delete();

                // 4. SEEDING JURNAL KOREKSI SESUAI PERSIS DENGAN GAMBAR 3 & GAMBAR 1
                $bankAbc = $coaMap['1-1111'] ?? null;
                $bankBcd = $coaMap['1-1112'] ?? null;
                $bankXyz = $coaMap['1-1113'] ?? null;
                $kasKecil = $coaMap['1-1120'] ?? null;
                $peralatan = $coaMap['1-2141'] ?? null;
                $sewaDibayarDimuka = $coaMap['1-1420'] ?? null;
                $piutangDagang = $coaMap['1-1210'] ?? null;
                $utangDagang = $coaMap['2-1100'] ?? null;
                $bebanGaji = $coaMap['5-2100'] ?? null;
                $bebanLain = $coaMap['5-3300'] ?? null;

                if ($bankAbc) {
                    // Jurnal 1 (Aset Tetap): Kredit Bank ABC Rp 295.000.000
                    $jk1 = JurnalKoreksiAsetTetap::create([
                        'AsetTetapID' => $asetTetap->AsetTetapID,
                        'Keterangan' => 'Koreksi pembelian peralatan via Bank ABC',
                    ]);
                    $jk1->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankAbc->COAID, 'Debet' => 0, 'Kredit' => 295000000],
                        ['COAID' => $peralatan?->COAID ?? $bankAbc->COAID, 'Debet' => 295000000, 'Kredit' => 0],
                    ]);

                    // Jurnal 2 (Aset Lain-Lain): Debit Bank ABC Rp 1.230
                    $jk2 = JurnalKoreksiAsetLain::create([
                        'AsetLainID' => $asetLain->AsetLainID,
                        'Keterangan' => 'Koreksi selisih pencatatan sewa dibayar dimuka',
                    ]);
                    $jk2->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankAbc->COAID, 'Debet' => 1230, 'Kredit' => 0],
                        ['COAID' => $sewaDibayarDimuka?->COAID ?? $bankAbc->COAID, 'Debet' => 0, 'Kredit' => 1230],
                    ]);

                    // Jurnal 3 (Piutang): Debit Bank ABC Rp 95.068.500
                    $jk3 = JurnalKoreksiPiutang::create([
                        'PiutangID' => $piutang->PiutangID,
                        'Keterangan' => 'Koreksi transfer pelunasan piutang pelanggan',
                    ]);
                    $jk3->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankAbc->COAID, 'Debet' => 95068500, 'Kredit' => 0],
                        ['COAID' => $piutangDagang?->COAID ?? $bankAbc->COAID, 'Debet' => 0, 'Kredit' => 95068500],
                    ]);

                    // Jurnal 4 (Utang Usaha): Debit Bank ABC Rp 45.391.000
                    $jk4 = JurnalKoreksiUtangUsaha::create([
                        'UtangUsahaID' => $utangUsaha->UtangUsahaID,
                        'Keterangan' => 'Koreksi kelebihan transfer utang dagang',
                    ]);
                    $jk4->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankAbc->COAID, 'Debet' => 45391000, 'Kredit' => 0],
                        ['COAID' => $utangDagang?->COAID ?? $bankAbc->COAID, 'Debet' => 0, 'Kredit' => 45391000],
                    ]);

                    // Jurnal 5 & 6 (Beban Usaha): Debit Rp 53.170.000 & Kredit Rp 53.000.000
                    $jk5 = JurnalKoreksiBebanUsaha::create([
                        'BebanUsahaID' => $bebanUsaha->BebanUsahaID,
                        'Keterangan' => 'Koreksi penyesuaian transfer beban operasional',
                    ]);
                    $jk5->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankAbc->COAID, 'Debet' => 53170000, 'Kredit' => 0],
                        ['COAID' => $bebanGaji?->COAID ?? $bankAbc->COAID, 'Debet' => 0, 'Kredit' => 53170000],
                        ['COAID' => $bankAbc->COAID, 'Debet' => 0, 'Kredit' => 53000000],
                        ['COAID' => $bebanLain?->COAID ?? $bankAbc->COAID, 'Debet' => 53000000, 'Kredit' => 0],
                    ]);
                }

                // Jurnal Bank BCD (Setara Kas): Kredit Rp 51.500.000 (sesuai Gambar 1: AJE/RJE -51.500.000)
                if ($bankBcd) {
                    $jkBcd = JurnalKoreksiSetaraKas::create([
                        'SetaraKasID' => $setaraKas->SetaraKasID,
                        'Keterangan' => 'Koreksi cek beredar Bank BCD',
                    ]);
                    $jkBcd->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankBcd->COAID, 'Debet' => 0, 'Kredit' => 51500000],
                        ['COAID' => $utangDagang?->COAID ?? $bankBcd->COAID, 'Debet' => 51500000, 'Kredit' => 0],
                    ]);
                }

                // Jurnal Bank XYZ (Setara Kas): Kredit Rp 58.000.000 (sesuai Gambar 1: AJE/RJE -58.000.000)
                if ($bankXyz) {
                    $jkXyz = JurnalKoreksiSetaraKas::create([
                        'SetaraKasID' => $setaraKas->SetaraKasID,
                        'Keterangan' => 'Koreksi transfer kliring Bank XYZ',
                    ]);
                    $jkXyz->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $bankXyz->COAID, 'Debet' => 0, 'Kredit' => 58000000],
                        ['COAID' => $utangDagang?->COAID ?? $bankXyz->COAID, 'Debet' => 58000000, 'Kredit' => 0],
                    ]);
                }

                // Jurnal Kas Kecil (Kas): Kredit Rp 730.000 (sesuai Gambar 1: AJE/RJE -730.000)
                if ($kasKecil) {
                    $jkKas = JurnalKoreksiKas::create([
                        'KasID' => $kas->KasID,
                        'Keterangan' => 'Koreksi selisih fisik kas kecil',
                    ]);
                    $jkKas->pembayaranJurnalKoreksi()->createMany([
                        ['COAID' => $kasKecil->COAID, 'Debet' => 0, 'Kredit' => 730000],
                        ['COAID' => $bebanLain?->COAID ?? $kasKecil->COAID, 'Debet' => 730000, 'Kredit' => 0],
                    ]);
                }

                // Tandai checklist modul substantif terisi
                $setaraKas->updateQuietly(['JurnalCheck' => true]);
                $piutang->updateQuietly(['JurnalCheck' => true]);
                $utangUsaha->updateQuietly(['JurnalCheck' => true]);
                $asetTetap->updateQuietly(['JurnalCheck' => true]);
                $asetLain->updateQuietly(['JurnalCheck' => true]);
                $bebanUsaha->updateQuietly(['JurnalCheck' => true]);
                $kas->updateQuietly(['JurnalCheck' => true]);
            }
        });
    }
}

