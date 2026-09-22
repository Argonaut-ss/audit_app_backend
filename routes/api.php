<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// use App\Http\Controllers\AdminController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KasusController;
use App\Http\Controllers\DataClientController;
use App\Http\Controllers\JwbKasusController;
// use App\Http\Controllers\AuditController; // API audits sudah tidak digunakan.
use App\Http\Controllers\PerikatanController;
use App\Http\Controllers\DetilVerifikasiController;
use App\Http\Controllers\IdentifikasiController;
use App\Http\Controllers\PmpjController;
use App\Http\Controllers\COAController;
use App\Http\Controllers\PiutangController\PiutangController;
use App\Http\Controllers\PiutangController\KonfirmasiPiutangController;
use App\Http\Controllers\PiutangController\RekonsiliasiPiutangController;
use App\Http\Controllers\PiutangController\RekapBalasanController;
use App\Http\Controllers\PiutangController\ProsedurAlternatifController;
use App\Http\Controllers\PiutangController\AnalisisUmurPiutangController;
use App\Http\Controllers\PiutangController\JurnalKoreksiPiutangController;
use App\Http\Controllers\PiutangController\ProsedurController;
use App\Http\Controllers\PiutangController\DokumenController;

use App\Http\Controllers\UtangUsahaController\UtangUsahaController;
use App\Http\Controllers\UtangUsahaController\ProsedurUtangUsahaController;
use App\Http\Controllers\UtangUsahaController\RekonsiliasiUtangUsahaController;
use App\Http\Controllers\UtangUsahaController\JurnalKoreksiUtangUsahaController;
use App\Http\Controllers\UtangUsahaController\DokumenUtangUsahaController;
use App\Http\Controllers\UtangUsahaController\KonfirmasiUtangUsahaController;
use App\Http\Controllers\UtangUsahaController\RekapBalasanUtangUsahaController;
use App\Http\Controllers\UtangUsahaController\ProsedurAlternatifUtangUsahaController;

use App\Http\Controllers\PersediaanController\DokumenPersediaanController;
use App\Http\Controllers\PersediaanController\ProsedurPersediaanController;
use App\Http\Controllers\PersediaanController\JurnalKoreksiPersediaanController;

use App\Http\Controllers\KelasCardController;

// Login & Logout & RBAC
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Imports
    Route::post('mahasiswas/import', [MahasiswaController::class, 'import']);
    Route::post('dosens/import', [DosenController::class, 'import']);
    Route::post('/perikatan/{id}', [PerikatanController::class, 'update']);

    // Basic Routes
    // Route::apiResource('admin', AdminController::class);
    Route::apiResource('dosens', DosenController::class);
    Route::apiResource('mahasiswas', MahasiswaController::class);
    Route::apiResource('kelas', KelasController::class)->parameters(['kelas' => 'kelas']);

    // Data Client Routes
    Route::apiResource('data-client', DataClientController::class);
    Route::get('/data-client/{id}/logo-kantor', [DataClientController::class, 'logoKantor']);
    Route::get('/data-client/{id}/logo-perusahaan', [DataClientController::class, 'logoPerusahaan']);

    // Tugas Routes
    Route::apiResource('kasus', KasusController::class);
    Route::apiResource('jwb-kasus', JwbKasusController::class);
    // Route::apiResource('audits', AuditController::class)->only(['index', 'store', 'update']);
    Route::get('kasus/{id}/file', [KasusController::class, 'file']);
    Route::get('/perikatan/{id}', [PerikatanController::class, 'show']);
    Route::delete('/perikatan/{id}/{file}', [PerikatanController::class, 'destroy']);
    Route::apiResource('detil-verifikasi', DetilVerifikasiController::class)->only(['index', 'show', 'update',]);

    // Identifikasi Routes
    Route::get('/identifikasi/{jwbKasusId}', [IdentifikasiController::class, 'show']);
    Route::put('/identifikasi/{jwbKasusId}', [IdentifikasiController::class, 'update']);

    // PMPJ Routes
    Route::get('/pmpj/risk-config', [PmpjController::class, 'riskConfig']);
    Route::get('/pmpj/{jwbKasusId}', [PmpjController::class, 'show']);
    Route::get('/pmpj/{jwbKasusId}/file-ktp', [PmpjController::class, 'fileKtp']);
    Route::put('/pmpj/{jwbKasusId}', [PmpjController::class, 'update']);

    // Piutang Routes
    Route::get('/piutang/{jwbKasusId}', [PiutangController::class, 'show']);
    Route::put('/piutang/{jwbKasusId}', [PiutangController::class, 'update']);

    // COA Routes
    Route::post('/coa/import', [COAController::class, 'import']);
    Route::apiResource('coa', COAController::class)->only(['index', 'store', 'update', 'destroy',]);
    Route::delete('/jwb-kasus/{JwbKasusID}/coa',[COAController::class, 'destroyAll']);
    
    // Rekonsiliasi Piutang Routes
    Route::apiResource('rekonsiliasi-piutang', RekonsiliasiPiutangController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['rekonsiliasi-piutang' => 'rekonsiliasiPiutang']);

    // Analisis Umur Piutang Routes
    Route::apiResource('analisis-umur-piutang', AnalisisUmurPiutangController::class)
        ->only(['index', 'store']);

    // Jurnal Koreksi Piutang Routes
    Route::apiResource('jurnal-koreksi-piutang', JurnalKoreksiPiutangController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['jurnal-koreksi-piutang' => 'jurnalKoreksi']);

    Route::get('/konfirmasi-piutang/{id}/file', [KonfirmasiPiutangController::class, 'file']);
    Route::apiResource('konfirmasi-piutang', KonfirmasiPiutangController::class);

    Route::post('rekap-balasan/bulk-save', [RekapBalasanController::class, 'bulkSave']);
    Route::get('/rekap-balasan/{id}/file', [RekapBalasanController::class, 'file']);
    Route::apiResource('rekap-balasan', RekapBalasanController::class);

    Route::get('/prosedur-alternatif/{id}/file', [ProsedurAlternatifController::class, 'file']);
    Route::post('/prosedur-alternatif/bulk-save', [ProsedurAlternatifController::class, 'bulkSave']);
    Route::apiResource('prosedur-alternatif', ProsedurAlternatifController::class);

    // Prosedur Piutang Routes
    Route::get('/piutangs/{piutang}/prosedur',[ProsedurController::class, 'index']);
    Route::post('/prosedur',[ProsedurController::class, 'store']);
    Route::put('/prosedur/{prosedur}',[ProsedurController::class, 'update']);
    Route::delete('/prosedur/{prosedur}',[ProsedurController::class, 'destroy']);

    // Dokumen Piutang Routes
    Route::get('dokumen/piutang/{piutangId}',[DokumenController::class, 'index']);
    Route::post('dokumen/piutang/{piutangId}',[DokumenController::class, 'store']);
    Route::get('dokumen/{dokumenId}',[DokumenController::class, 'show']);
    Route::put('dokumen/{dokumenId}',[DokumenController::class, 'update']);
    Route::delete('dokumen/{dokumenId}',[DokumenController::class, 'destroy']);

    // UtangUsaha Routes
    Route::get('/utang-usaha/{jwbKasusId}', [UtangUsahaController::class, 'show']);
    Route::put('/utang-usaha/{jwbKasusId}', [UtangUsahaController::class, 'update']);

    // Prosedur Utang Usaha Routes
    Route::get('/utang-usahas/{utangUsaha}/prosedur', [ProsedurUtangUsahaController::class, 'index']);
    Route::post('/prosedur-utang-usaha', [ProsedurUtangUsahaController::class, 'store']);
    Route::put('/prosedur-utang-usaha/{prosedurUtangUsaha}', [ProsedurUtangUsahaController::class, 'update']);
    Route::delete('/prosedur-utang-usaha/{prosedurUtangUsaha}', [ProsedurUtangUsahaController::class, 'destroy']);

    // Rekonsiliasi Utang Usaha Routes
    Route::apiResource('rekonsiliasi-utang-usaha', RekonsiliasiUtangUsahaController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['rekonsiliasi-utang-usaha' => 'rekonsiliasiUtangUsaha']);

    // Jurnal Koreksi Utang Usaha Routes
    Route::apiResource('jurnal-koreksi-utang-usaha', JurnalKoreksiUtangUsahaController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['jurnal-koreksi-utang-usaha' => 'jurnalKoreksiUtangUsaha']);

    // Dokumen Utang Usaha Routes
    Route::get('dokumen/UtangUsaha/{utangUsahaId}',[DokumenUtangUsahaController::class, 'index']);
    Route::post('dokumen/UtangUsaha/{utangUsahaId}',[DokumenUtangUsahaController::class, 'store']);
    Route::get('dokumen-utang-usaha/{dokumenId}',[DokumenUtangUsahaController::class, 'show']);
    Route::put('dokumen-utang-usaha/{dokumenId}',[DokumenUtangUsahaController::class, 'update']);
    Route::delete('dokumen-utang-usaha/{dokumenId}',[DokumenUtangUsahaController::class, 'destroy']);

    // Konfirmasi Utang Usaha Routes
    Route::get('/konfirmasi-utang-usaha/{id}/file', [KonfirmasiUtangUsahaController::class, 'file']);
    Route::apiResource('konfirmasi-utang-usaha', KonfirmasiUtangUsahaController::class);

    // Rekap Utang Usaha Routes
    Route::post('rekap-balasan-utang-usaha/bulk-save', [RekapBalasanUtangUsahaController::class, 'bulkSave']);
    Route::get('/rekap-balasan-utang-usaha/{id}/file', [RekapBalasanUtangUsahaController::class, 'file']);
    Route::apiResource('rekap-balasan-utang-usaha', RekapBalasanUtangUsahaController::class);

    // Prosedur Alternatif Utang Usaha Routes
    Route::get('/prosedur-alternatif-utang-usaha/{id}/file', [ProsedurAlternatifUtangUsahaController::class, 'file']);
    Route::post('/prosedur-alternatif-utang-usaha/bulk-save', [ProsedurAlternatifUtangUsahaController::class, 'bulkSave']);
    Route::apiResource('prosedur-alternatif-utang-usaha', ProsedurAlternatifUtangUsahaController::class);

    // Prosedur Persediaan Routes
    Route::get('/persediaans/{persediaan}/prosedur', [ProsedurPersediaanController::class, 'index']);
    Route::post('/prosedur-persediaan', [ProsedurPersediaanController::class, 'store']);
    Route::put('/prosedur-persediaan/{prosedurPersediaan}', [ProsedurPersediaanController::class, 'update']);
    Route::delete('/prosedur-persediaan/{prosedurPersediaan}', [ProsedurPersediaanController::class, 'destroy']);

    // Dokumen Persediaan Routes
    Route::get('/persediaan/{persediaanId}/dokumen', [DokumenPersediaanController::class, 'index']);
    Route::post('/persediaan/{persediaanId}/dokumen', [DokumenPersediaanController::class, 'store']);
    Route::get('/dokumen-persediaan/{dokumenId}', [DokumenPersediaanController::class, 'show']);
    Route::put('/dokumen-persediaan/{dokumenId}', [DokumenPersediaanController::class, 'update']);
    Route::delete('/dokumen-persediaan/{dokumenId}', [DokumenPersediaanController::class, 'destroy']);

    // Jurnal Koreksi Persediaan Routes
        Route::apiResource('jurnal-koreksi-persediaan', JurnalKoreksiPersediaanController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['jurnal-koreksi-persediaan' => 'jurnalKoreksiPersediaan']);

    // Helper Functions
    Route::get('/kelas-card', [KelasCardController::class, 'index']);    
});
    
// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend is running',
    ]);
});