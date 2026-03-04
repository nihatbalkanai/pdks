<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// PDKS Cihaz veri aktarım servisi (Cihaz Token - Sanctum korumalı)
Route::middleware('auth:sanctum')->post('/pdks/veri', [\App\Http\Controllers\Api\PdksVeriController::class, 'store']);

// Personel Mobil Uygulama API Rotaları
Route::post('/mobil-login', [\App\Http\Controllers\Api\MobilAppController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profil', [\App\Http\Controllers\Api\MobilAppController::class, 'profil']);
    Route::get('/hareketlerim', [\App\Http\Controllers\Api\MobilAppController::class, 'hareketlerim']);
});
