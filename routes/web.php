<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'abonelik'])->name('dashboard');

Route::middleware(['auth', 'abonelik'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // PDKS Routes
    Route::resource('personeller', \App\Http\Controllers\PersonelController::class);
    Route::resource('cihazlar', \App\Http\Controllers\PdksCihaziController::class);
    Route::resource('subeler', \App\Http\Controllers\SubeController::class)->middleware(\App\Http\Middleware\FeatureGate::class.':şube_yönetimi');
    Route::resource('servisler', \App\Http\Controllers\ServisController::class)->middleware(\App\Http\Middleware\FeatureGate::class.':servis_takibi');
    
    Route::get('/raporlar', [\App\Http\Controllers\RaporController::class, 'index'])->name('raporlar.index');
    Route::post('/raporlar/export', [\App\Http\Controllers\RaporController::class, 'export'])->name('raporlar.export');
});

// Super Admin Routes
Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('super-admin.index');
    Route::post('/firmalar/{id}/abonelik', [\App\Http\Controllers\SuperAdminController::class, 'updateAbonelik'])->name('super-admin.firmalar.abonelik');
    Route::post('/adminler/{id}/yetki', [\App\Http\Controllers\SuperAdminController::class, 'updateAdminYetki'])->name('super-admin.adminler.yetki');
});

require __DIR__.'/auth.php';
