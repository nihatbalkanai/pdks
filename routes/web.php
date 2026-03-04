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

    // Personel İşlemleri
    Route::resource('personeller', \App\Http\Controllers\PersonelController::class)->middleware('rol.yetki:personel_islemleri');
    Route::post('/personeller/{id}/resim', [\App\Http\Controllers\PersonelController::class, 'resimYukle'])->name('personeller.resim-yukle')->middleware('rol.yetki:personel_kartlari');

    // Cihaz İşlemleri
    Route::resource('cihazlar', \App\Http\Controllers\PdksCihaziController::class)->middleware('rol.yetki:cihaz_islemleri');
    Route::get('/cihaz-transfer', [\App\Http\Controllers\CihazTransferController::class, 'index'])->name('cihaz-transfer.index')->middleware('rol.yetki:cihaz_transfer');
    Route::delete('/cihaz-transfer/hatali', [\App\Http\Controllers\CihazTransferController::class, 'deleteHatali'])->name('cihaz-transfer.hatali-sil')->middleware('rol.yetki:cihaz_transfer');

    // Şube & Servis
    Route::resource('subeler', \App\Http\Controllers\SubeController::class)->middleware([\App\Http\Middleware\FeatureGate::class.':şube_yönetimi', 'rol.yetki:subeler']);
    Route::resource('servisler', \App\Http\Controllers\ServisController::class)->middleware([\App\Http\Middleware\FeatureGate::class.':servis_takibi', 'rol.yetki:servisler']);

    // Raporlar
    Route::get('/raporlar', [\App\Http\Controllers\RaporController::class, 'index'])->name('raporlar.index')->middleware('rol.yetki:raporlar');
    Route::post('/raporlar/export', [\App\Http\Controllers\RaporController::class, 'export'])->name('raporlar.export')->middleware('rol.yetki:raporlar');

    // Ek Kazançlar
    Route::get('/ek-kazanclar', [\App\Http\Controllers\EkKazancController::class, 'index'])->name('ek-kazanclar.index')->middleware('rol.yetki:ek_kazanclar');
    Route::post('/ek-kazanclar', [\App\Http\Controllers\EkKazancController::class, 'store'])->name('ek-kazanclar.store')->middleware('rol.yetki:ek_kazanclar');
    Route::delete('/ek-kazanclar/{id}', [\App\Http\Controllers\EkKazancController::class, 'destroy'])->name('ek-kazanclar.destroy')->middleware('rol.yetki:ek_kazanclar');

    // Avans ve Kesintiler
    Route::get('/avans-kesintiler', [\App\Http\Controllers\AvansKesintilerController::class, 'index'])->name('avans-kesintiler.index')->middleware('rol.yetki:avans_kesintiler');
    Route::post('/avans-kesintiler', [\App\Http\Controllers\AvansKesintilerController::class, 'store'])->name('avans-kesintiler.store')->middleware('rol.yetki:avans_kesintiler');
    Route::delete('/avans-kesintiler/{id}', [\App\Http\Controllers\AvansKesintilerController::class, 'destroy'])->name('avans-kesintiler.destroy')->middleware('rol.yetki:avans_kesintiler');

    // Toplu İşlemler
    Route::prefix('toplu-islemler')->name('toplu-islemler.')->middleware('rol.yetki:toplu_islemler')->group(function () {
        Route::get('/maas-artirimi', [\App\Http\Controllers\TopluIslemController::class, 'maasArtirimi'])->name('maas-artirimi')->middleware('rol.yetki:toplu_maas');
        Route::post('/maas-artirimi', [\App\Http\Controllers\TopluIslemController::class, 'maasArtirimiUygula'])->name('maas-artirimi.uygula')->middleware('rol.yetki:toplu_maas');
        Route::get('/hareket', [\App\Http\Controllers\TopluIslemController::class, 'hareket'])->name('hareket')->middleware('rol.yetki:toplu_hareket');
        Route::post('/hareket', [\App\Http\Controllers\TopluIslemController::class, 'hareketUygula'])->name('hareket.uygula')->middleware('rol.yetki:toplu_hareket');
        Route::get('/izin', [\App\Http\Controllers\TopluIslemController::class, 'izin'])->name('izin')->middleware('rol.yetki:toplu_izin');
        Route::post('/izin', [\App\Http\Controllers\TopluIslemController::class, 'izinUygula'])->name('izin.uygula')->middleware('rol.yetki:toplu_izin');
        Route::get('/avans', [\App\Http\Controllers\TopluIslemController::class, 'avans'])->name('avans')->middleware('rol.yetki:toplu_avans');
        Route::post('/avans', [\App\Http\Controllers\TopluIslemController::class, 'avansUygula'])->name('avans.uygula')->middleware('rol.yetki:toplu_avans');
        Route::get('/prim', [\App\Http\Controllers\TopluIslemController::class, 'prim'])->name('prim')->middleware('rol.yetki:toplu_prim');
        Route::post('/prim', [\App\Http\Controllers\TopluIslemController::class, 'primUygula'])->name('prim.uygula')->middleware('rol.yetki:toplu_prim');
        Route::get('/agi-atama', [\App\Http\Controllers\TopluIslemController::class, 'agiAtama'])->name('agi-atama')->middleware('rol.yetki:toplu_agi');
        Route::post('/agi-atama', [\App\Http\Controllers\TopluIslemController::class, 'agiAtamaUygula'])->name('agi-atama.uygula')->middleware('rol.yetki:toplu_agi');
        Route::get('/giris-cikis-duzenleme', [\App\Http\Controllers\TopluIslemController::class, 'girisCikisDuzenleme'])->name('giris-cikis-duzenleme')->middleware('rol.yetki:toplu_giris_cikis');
        Route::post('/giris-cikis-duzenleme', [\App\Http\Controllers\TopluIslemController::class, 'girisCikisDuzenlemeKaydet'])->name('giris-cikis-duzenleme.kaydet')->middleware('rol.yetki:toplu_giris_cikis');
        Route::delete('/giris-cikis-duzenleme/tarih-sil', [\App\Http\Controllers\TopluIslemController::class, 'girisCikisTarihSil'])->name('giris-cikis-duzenleme.tarih-sil')->middleware('rol.yetki:toplu_giris_cikis');
        Route::get('/toplu-mesaj', [\App\Http\Controllers\TopluIslemController::class, 'topluMesaj'])->name('toplu-mesaj')->middleware('rol.yetki:toplu_mesaj');
        Route::post('/toplu-mesaj', [\App\Http\Controllers\TopluIslemController::class, 'topluMesajGonder'])->name('toplu-mesaj.gonder')->middleware('rol.yetki:toplu_mesaj');
        Route::get('/toplu-mail', [\App\Http\Controllers\TopluIslemController::class, 'topluMail'])->name('toplu-mail')->middleware('rol.yetki:toplu_mail');
        Route::post('/toplu-mail', [\App\Http\Controllers\TopluIslemController::class, 'topluMailGonder'])->name('toplu-mail.gonder')->middleware('rol.yetki:toplu_mail');
        Route::get('/zamanlanmis-bildirimler', [\App\Http\Controllers\ZamanlanmisBildirimController::class, 'index'])->name('zamanlanmis-bildirimler')->middleware('rol.yetki:zamanlanmis_bildirimler');
        Route::post('/zamanlanmis-bildirimler', [\App\Http\Controllers\ZamanlanmisBildirimController::class, 'store'])->name('zamanlanmis-bildirimler.store')->middleware('rol.yetki:zamanlanmis_bildirimler');
        Route::put('/zamanlanmis-bildirimler/{id}', [\App\Http\Controllers\ZamanlanmisBildirimController::class, 'update'])->name('zamanlanmis-bildirimler.update')->middleware('rol.yetki:zamanlanmis_bildirimler');
        Route::patch('/zamanlanmis-bildirimler/{id}/toggle', [\App\Http\Controllers\ZamanlanmisBildirimController::class, 'toggleAktif'])->name('zamanlanmis-bildirimler.toggle')->middleware('rol.yetki:zamanlanmis_bildirimler');
        Route::delete('/zamanlanmis-bildirimler/{id}', [\App\Http\Controllers\ZamanlanmisBildirimController::class, 'destroy'])->name('zamanlanmis-bildirimler.destroy')->middleware('rol.yetki:zamanlanmis_bildirimler');
    });

    // Tanım İşlemleri
    Route::prefix('tanim')->name('tanim.')->middleware('rol.yetki:tanim_islemleri')->group(function () {
        Route::get('/mail-ayarlari', [\App\Http\Controllers\MesajAyariController::class, 'mailAyarlari'])->name('mail-ayarlari');
        Route::post('/mail-ayarlari', [\App\Http\Controllers\MesajAyariController::class, 'mailAyarlariKaydet'])->name('mail-ayarlari.kaydet');
        Route::post('/mail-test', [\App\Http\Controllers\MesajAyariController::class, 'mailTest'])->name('mail-test');
        Route::get('/mesaj-ayarlari', [\App\Http\Controllers\MesajAyariController::class, 'mesajAyarlari'])->name('mesaj-ayarlari');
        Route::post('/mesaj-ayarlari', [\App\Http\Controllers\MesajAyariController::class, 'mesajAyarlariKaydet'])->name('mesaj-ayarlari.kaydet');
        Route::post('/mesaj-test', [\App\Http\Controllers\MesajAyariController::class, 'mesajTest'])->name('mesaj-test');
        Route::get('/kullanicilar', [\App\Http\Controllers\KullaniciYonetimController::class, 'index'])->name('kullanicilar');
        Route::post('/kullanicilar', [\App\Http\Controllers\KullaniciYonetimController::class, 'store'])->name('kullanicilar.store');
        Route::put('/kullanicilar/{id}', [\App\Http\Controllers\KullaniciYonetimController::class, 'update'])->name('kullanicilar.update');
        Route::delete('/kullanicilar/{id}', [\App\Http\Controllers\KullaniciYonetimController::class, 'destroy'])->name('kullanicilar.destroy');
        Route::get('/kodlar/{tip}', [\App\Http\Controllers\TanimKoduController::class, 'index'])->name('kodlar');
        Route::post('/kodlar/{tip}', [\App\Http\Controllers\TanimKoduController::class, 'store'])->name('kodlar.store');
        Route::put('/kodlar/{tip}/{id}', [\App\Http\Controllers\TanimKoduController::class, 'update'])->name('kodlar.update');
        Route::delete('/kodlar/{tip}/{id}', [\App\Http\Controllers\TanimKoduController::class, 'destroy'])->name('kodlar.destroy');
    });
});

// Super Admin Routes
Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('super-admin.index');
    Route::post('/firmalar/{id}/abonelik', [\App\Http\Controllers\SuperAdminController::class, 'updateAbonelik'])->name('super-admin.firmalar.abonelik');
    Route::post('/adminler/{id}/yetki', [\App\Http\Controllers\SuperAdminController::class, 'updateAdminYetki'])->name('super-admin.adminler.yetki');
});

require __DIR__.'/auth.php';
