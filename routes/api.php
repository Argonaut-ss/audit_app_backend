<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KasusController;
use App\Http\Controllers\DataClientController;
use App\Http\Controllers\JwbKasusController;

// Mendaftarkan seluruh route CRUD otomatis untuk setiap entitas
Route::apiResource('admin', AdminController::class);
Route::apiResource('dosen', DosenController::class);
Route::apiResource('mahasiswa', MahasiswaController::class);
Route::apiResource('kelas', KelasController::class)->parameters([
    'kelas' => 'kelas'
]);
Route::apiResource('kasus', KasusController::class);
Route::apiResource('data-client', DataClientController::class);
Route::apiResource('jwb-kasus', JwbKasusController::class);

Route::get('jwb-kasus/{id}/file',[JwbKasusController::class, 'file']);
Route::get('/kasus/{id}/file', [KasusController::class, 'file']);
/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend is running',
    ]);
});

Route::post('mahasiswas/import', [MahasiswaController::class, 'import']);
Route::post('dosens/import', [DosenController::class, 'import']);

Route::apiResource('mahasiswas', MahasiswaController::class);
Route::apiResource('dosens', DosenController::class);