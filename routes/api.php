<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// PDKS Cihaz veri aktarım servisi (Cihaz Token - Sanctum korumalı)
Route::middleware('auth:sanctum')->post('/pdks/veri', [\App\Http\Controllers\Api\PdksVeriController::class, 'store']);

// ═══════════════════ MOBİL UYGULAMA API ═══════════════════

// Açık endpoint'ler (token gerektirmez)
Route::prefix('mobil')->group(function () {
    Route::post('/giris', [\App\Http\Controllers\Api\MobilAppController::class, 'login']);
    Route::get('/firma-logo/{kod}', function ($kod) {
        $firma = \App\Models\Firma::where('firma_kodu', strtoupper($kod))->first();
        if (!$firma || !$firma->logo_yolu) return response()->json(['logo' => null]);
        return response()->json(['logo' => request()->getSchemeAndHttpHost() . '/storage/' . $firma->logo_yolu, 'firma_adi' => $firma->firma_adi]);
    });
});

// Korumalı endpoint'ler (Sanctum token gerektirir)
Route::prefix('mobil')->middleware('auth:sanctum')->group(function () {
    Route::post('/hareket', [\App\Http\Controllers\Api\MobilAppController::class, 'girisYap']);
    Route::get('/bugun', [\App\Http\Controllers\Api\MobilAppController::class, 'bugunDurum']);
    Route::get('/gecmis', [\App\Http\Controllers\Api\MobilAppController::class, 'gecmis']);
    Route::get('/profil', [\App\Http\Controllers\Api\MobilAppController::class, 'profil']);
    Route::post('/sifre-degistir', [\App\Http\Controllers\Api\MobilAppController::class, 'sifreDegistir']);

    // Yeni modüller
    Route::get('/izinlerim', [\App\Http\Controllers\Api\MobilAppController::class, 'izinlerim']);
    Route::post('/izin-talebi', [\App\Http\Controllers\Api\MobilAppController::class, 'izinTalebi']);
    Route::get('/izin-turleri', [\App\Http\Controllers\Api\MobilAppController::class, 'izinTurleri']);
    Route::get('/puantaj-ozeti', [\App\Http\Controllers\Api\MobilAppController::class, 'puantajOzeti']);
    Route::get('/vardiya-takvimi', [\App\Http\Controllers\Api\MobilAppController::class, 'vardiyaTakvimi']);
    Route::get('/belgelerim', [\App\Http\Controllers\Api\MobilAppController::class, 'belgelerim']);
    Route::get('/bordro-ozeti', [\App\Http\Controllers\Api\MobilAppController::class, 'bordroOzeti']);
});
