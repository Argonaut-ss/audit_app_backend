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
use App\Http\Controllers\Api\IdentifikasiController;

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

    // CRUD
    // Mendaftarkan seluruh route CRUD otomatis untuk setiap entitas
    // Route::apiResource('admin', AdminController::class);
    Route::apiResource('dosens', DosenController::class);
    Route::apiResource('mahasiswas', MahasiswaController::class);
    
    Route::apiResource('kasus', KasusController::class);
    Route::apiResource('data-client', DataClientController::class);
    Route::apiResource('jwb-kasus', JwbKasusController::class);
    
    Route::apiResource('kelas', KelasController::class)->parameters(['kelas' => 'kelas']);
    
    Route::get('/data-client/{id}/logo-kantor', [DataClientController::class, 'logoKantor']);
    Route::get('/data-client/{id}/logo-perusahaan', [DataClientController::class, 'logoPerusahaan']);
    // Route::apiResource('audits', AuditController::class)->only(['index', 'store', 'update']);

    Route::get('kasus/{id}/file', [KasusController::class, 'file']);
    Route::get('/perikatan/{id}', [PerikatanController::class, 'show']);
    Route::apiResource('detil-verifikasi', DetilVerifikasiController::class)->only(['index', 'show', 'update',]);

    Route::get('/identifikasi/{jwbKasusId}', [IdentifikasiController::class, 'show']);
    Route::put('/identifikasi/{jwbKasusId}', [IdentifikasiController::class, 'update']);

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