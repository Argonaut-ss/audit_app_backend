<?php

use App\Http\Controllers\DosenController;
use App\Http\Controllers\MahasiswaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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