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
use App\Http\Controllers\AuditController;

use App\Http\Controllers\KelasCardController;

Route::get('/kelas-card', [KelasCardController::class, 'index']);

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

    // CRUD
    // Mendaftarkan seluruh route CRUD otomatis untuk setiap entitas
    // Route::apiResource('admin', AdminController::class);
    Route::apiResource('dosens', DosenController::class);
    Route::apiResource('mahasiswas', MahasiswaController::class);
    Route::apiResource('kelas', KelasController::class)->parameters(['kelas' => 'kelas']);

    Route::apiResource('kasus', KasusController::class);
    Route::apiResource('data-client', DataClientController::class);
    Route::apiResource('jwb-kasus', JwbKasusController::class);
    Route::apiResource('audits', AuditController::class)->only(['index', 'store', 'update']);

    Route::get('jwb-kasus/{id}/file',[JwbKasusController::class, 'file']);
    Route::get('kasus/{id}/file', [KasusController::class, 'file']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend is running',
    ]);
});