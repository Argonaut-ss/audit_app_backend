<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\KelasController;

// Mendaftarkan seluruh route CRUD otomatis untuk setiap entitas
Route::apiResource('admin', AdminController::class);
Route::apiResource('dosen', DosenController::class);
Route::apiResource('mahasiswa', MahasiswaController::class);
Route::apiResource('kelas', KelasController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend is running',
    ]);
});