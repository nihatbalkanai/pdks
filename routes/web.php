<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Web Sayfaları
Route::get('/', [\App\Http\Controllers\WebController::class, 'anasayfa'])->name('web.anasayfa');
Route::get('/hakkimizda', [\App\Http\Controllers\WebController::class, 'hakkimizda'])->name('web.hakkimizda');
Route::get('/fiyatlar', [\App\Http\Controllers\WebController::class, 'fiyatlar'])->name('web.fiyatlar');
Route::get('/referanslar', [\App\Http\Controllers\WebController::class, 'referanslar'])->name('web.referanslar');
Route::get('/haberler', [\App\Http\Controllers\WebController::class, 'haberler'])->name('web.haberler');
Route::get('/iletisim', [\App\Http\Controllers\WebController::class, 'iletisim'])->name('web.iletisim');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'abonelik'])->name('dashboard');

Route::middleware(['auth', 'abonelik'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Personel İşlemleri
    Route::resource('personeller', \App\Http\Controllers\PersonelController::class)->parameters(['personeller' => 'personel'])->middleware('rol.yetki:personel_islemleri');
    Route::post('/personeller/{id}/resim', [\App\Http\Controllers\PersonelController::class, 'resimYukle'])->name('personeller.resim-yukle')->middleware('rol.yetki:personel_islemleri');

    // Hesap Raporları
    Route::get('/hesap-raporlari/puantaj-hesaplama', [\App\Http\Controllers\PuantajHesaplamaController::class, 'index'])->name('hesap-raporlari.puantaj-hesaplama')->middleware('rol.yetki:hesap_puantaj');
    Route::post('/hesap-raporlari/puantaj-hesapla', [\App\Http\Controllers\PuantajHesaplamaController::class, 'hesapla'])->name('hesap-raporlari.puantaj-hesapla')->middleware('rol.yetki:hesap_puantaj');
    Route::get('/hesap-raporlari/genel-maas-ekstresi', [\App\Http\Controllers\PuantajHesaplamaController::class, 'genelMaasEkstresi'])->name('hesap-raporlari.genel-maas-ekstresi')->middleware('rol.yetki:hesap_genel_maas');
    Route::get('/hesap-raporlari/kisi-bazinda-maas-ekstresi', [\App\Http\Controllers\PuantajHesaplamaController::class, 'kisiBazindaMaasEkstresi'])->name('hesap-raporlari.kisi-bazinda-maas-ekstresi')->middleware('rol.yetki:hesap_kisi_maas');
    Route::get('/hesap-raporlari/maas-pusulasi', [\App\Http\Controllers\PuantajHesaplamaController::class, 'maasPusulasi'])->name('hesap-raporlari.maas-pusulasi')->middleware('rol.yetki:hesap_maas_pusulasi');
    Route::get('/hesap-raporlari/grup-bazli-maas-ekstresi', [\App\Http\Controllers\PuantajHesaplamaController::class, 'grupBazliMaasEkstresi'])->name('hesap-raporlari.grup-bazli-maas-ekstresi')->middleware('rol.yetki:hesap_grup_maas');

    // Cihaz İşlemleri
    Route::resource('cihazlar', \App\Http\Controllers\PdksCihaziController::class)->middleware('rol.yetki:cihaz_islemleri');
    Route::get('/cihaz-transfer', [\App\Http\Controllers\CihazTransferController::class, 'index'])->name('cihaz-transfer.index')->middleware('rol.yetki:cihaz_transfer');
    Route::delete('/cihaz-transfer/hatali', [\App\Http\Controllers\CihazTransferController::class, 'deleteHatali'])->name('cihaz-transfer.hatali-sil')->middleware('rol.yetki:cihaz_transfer');

    // Şube & Servis
    Route::resource('subeler', \App\Http\Controllers\SubeController::class)->middleware([\App\Http\Middleware\FeatureGate::class.':şube_yönetimi', 'rol.yetki:subeler']);
    Route::resource('servisler', \App\Http\Controllers\ServisController::class)->middleware([\App\Http\Middleware\FeatureGate::class.':servis_takibi', 'rol.yetki:servisler']);

    // İK Yönetimi
    Route::prefix('ik')->name('ik.')->group(function () {
        Route::get('/izin-talepleri', [\App\Http\Controllers\IkYonetimController::class, 'izinTalepleri'])->name('izin-talepleri');
        Route::post('/izin-talepleri', [\App\Http\Controllers\IkYonetimController::class, 'izinTalebiOlustur'])->name('izin-talepleri.olustur');
        Route::put('/izin-talepleri/{id}', [\App\Http\Controllers\IkYonetimController::class, 'izinTalebiIslem'])->name('izin-talepleri.islem');
        Route::get('/performans', [\App\Http\Controllers\IkYonetimController::class, 'performans'])->name('performans');
        Route::post('/performans', [\App\Http\Controllers\IkYonetimController::class, 'performansKaydet'])->name('performans.kaydet');
        Route::get('/egitimler', [\App\Http\Controllers\IkYonetimController::class, 'egitimler'])->name('egitimler');
        Route::post('/egitimler', [\App\Http\Controllers\IkYonetimController::class, 'egitimKaydet'])->name('egitimler.kaydet');
        Route::get('/disiplin', [\App\Http\Controllers\IkYonetimController::class, 'disiplin'])->name('disiplin');
        Route::post('/disiplin', [\App\Http\Controllers\IkYonetimController::class, 'disiplinKaydet'])->name('disiplin.kaydet');
        Route::get('/kidem-hesaplayici', [\App\Http\Controllers\IkYonetimController::class, 'kidemHesaplayici'])->name('kidem-hesaplayici');
    });

    // Mobil Bağlantı Yönetimi
    Route::prefix('baglanti')->name('baglanti.')->group(function () {
        Route::get('/mobil', [\App\Http\Controllers\MobilBaglantiController::class, 'index'])->name('mobil');
        Route::post('/mobil/ayarlar', [\App\Http\Controllers\MobilBaglantiController::class, 'ayarlarKaydet'])->name('mobil.ayarlar');
        Route::put('/mobil/cihaz/{id}', [\App\Http\Controllers\MobilBaglantiController::class, 'cihazDurumDegistir'])->name('mobil.cihaz.durum');
        Route::delete('/mobil/cihaz/{id}', [\App\Http\Controllers\MobilBaglantiController::class, 'cihazSil'])->name('mobil.cihaz.sil');
        Route::post('/mobil/qr-kod', [\App\Http\Controllers\MobilBaglantiController::class, 'qrKodOlustur'])->name('mobil.qr-kod');
    });

    // Genel Raporlar (Özlük / Devam)
    Route::get('/raporlar', [\App\Http\Controllers\RaporController::class, 'index'])->name('raporlar.index')->middleware('rol.yetki:genel_raporlar');
    Route::post('/raporlar/export', [\App\Http\Controllers\RaporController::class, 'export'])->name('raporlar.export')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/01', [\App\Http\Controllers\RaporController::class, 'r01'])->name('raporlar.r01')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/02', [\App\Http\Controllers\RaporController::class, 'r02'])->name('raporlar.r02')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/03', [\App\Http\Controllers\RaporController::class, 'r03'])->name('raporlar.r03')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/04', [\App\Http\Controllers\RaporController::class, 'r04'])->name('raporlar.r04')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/05', [\App\Http\Controllers\RaporController::class, 'r05'])->name('raporlar.r05')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/06', [\App\Http\Controllers\RaporController::class, 'r06'])->name('raporlar.r06')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/07', [\App\Http\Controllers\RaporController::class, 'r07'])->name('raporlar.r07')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/08', [\App\Http\Controllers\RaporController::class, 'r08'])->name('raporlar.r08')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/09', [\App\Http\Controllers\RaporController::class, 'r09'])->name('raporlar.r09')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/10', [\App\Http\Controllers\RaporController::class, 'r10'])->name('raporlar.r10')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/11', [\App\Http\Controllers\RaporController::class, 'r11'])->name('raporlar.r11')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/12', [\App\Http\Controllers\RaporController::class, 'r12'])->name('raporlar.r12')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/13', [\App\Http\Controllers\RaporController::class, 'r13'])->name('raporlar.r13')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/14', [\App\Http\Controllers\RaporController::class, 'r14'])->name('raporlar.r14')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/15', [\App\Http\Controllers\RaporController::class, 'r15'])->name('raporlar.r15')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/16', [\App\Http\Controllers\RaporController::class, 'r16'])->name('raporlar.r16')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/17', [\App\Http\Controllers\RaporController::class, 'r17'])->name('raporlar.r17')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/18', [\App\Http\Controllers\RaporController::class, 'r18'])->name('raporlar.r18')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/19', [\App\Http\Controllers\RaporController::class, 'r19'])->name('raporlar.r19')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/20', [\App\Http\Controllers\RaporController::class, 'r20'])->name('raporlar.r20')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/21', [\App\Http\Controllers\RaporController::class, 'r21'])->name('raporlar.r21')->middleware('rol.yetki:genel_raporlar');
    Route::get('/raporlar/22', [\App\Http\Controllers\RaporController::class, 'r22'])->name('raporlar.r22')->middleware('rol.yetki:genel_raporlar');


    // Ek Kazançlar
    Route::get('/ek-kazanclar', [\App\Http\Controllers\EkKazancController::class, 'index'])->name('ek-kazanclar.index')->middleware('rol.yetki:ek_kazanclar');
    Route::post('/ek-kazanclar', [\App\Http\Controllers\EkKazancController::class, 'store'])->name('ek-kazanclar.store')->middleware('rol.yetki:ek_kazanclar');
    Route::delete('/ek-kazanclar/grup/{grupId}', [\App\Http\Controllers\EkKazancController::class, 'destroyGrup'])->name('ek-kazanclar.destroy-grup')->middleware('rol.yetki:ek_kazanclar');
    Route::delete('/ek-kazanclar/{id}', [\App\Http\Controllers\EkKazancController::class, 'destroy'])->name('ek-kazanclar.destroy')->middleware('rol.yetki:ek_kazanclar');
    Route::put('/ek-kazanclar/{id}', [\App\Http\Controllers\EkKazancController::class, 'update'])->name('ek-kazanclar.update')->middleware('rol.yetki:ek_kazanclar');

    // Avans ve Kesintiler
    Route::get('/avans-kesintiler', [\App\Http\Controllers\AvansKesintilerController::class, 'index'])->name('avans-kesintiler.index')->middleware('rol.yetki:avans_kesintiler');
    Route::post('/avans-kesintiler', [\App\Http\Controllers\AvansKesintilerController::class, 'store'])->name('avans-kesintiler.store')->middleware('rol.yetki:avans_kesintiler');
    Route::delete('/avans-kesintiler/grup/{grupId}', [\App\Http\Controllers\AvansKesintilerController::class, 'destroyGrup'])->name('avans-kesintiler.destroy-grup')->middleware('rol.yetki:avans_kesintiler');
    Route::delete('/avans-kesintiler/{id}', [\App\Http\Controllers\AvansKesintilerController::class, 'destroy'])->name('avans-kesintiler.destroy')->middleware('rol.yetki:avans_kesintiler');
    Route::put('/avans-kesintiler/{id}', [\App\Http\Controllers\AvansKesintilerController::class, 'update'])->name('avans-kesintiler.update')->middleware('rol.yetki:avans_kesintiler');

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
        Route::get('/yemek-atama', [\App\Http\Controllers\TopluIslemController::class, 'yemekAtama'])->name('yemek-atama')->middleware('rol.yetki:toplu_yemek');
        Route::post('/yemek-atama', [\App\Http\Controllers\TopluIslemController::class, 'yemekAtamaUygula'])->name('yemek-atama.uygula')->middleware('rol.yetki:toplu_yemek');
        Route::get('/servis-yol-atama', [\App\Http\Controllers\TopluIslemController::class, 'servisYolAtama'])->name('servis-yol-atama')->middleware('rol.yetki:toplu_servis_yol');
        Route::post('/servis-yol-atama', [\App\Http\Controllers\TopluIslemController::class, 'servisYolAtamaUygula'])->name('servis-yol-atama.uygula')->middleware('rol.yetki:toplu_servis_yol');
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

        // Vardiya Tanımları
        Route::get('/vardiyalar', [\App\Http\Controllers\VardiyaController::class, 'index'])->name('vardiyalar.index');
        Route::post('/vardiyalar', [\App\Http\Controllers\VardiyaController::class, 'store'])->name('vardiyalar.store');
        Route::put('/vardiyalar/{vardiya}', [\App\Http\Controllers\VardiyaController::class, 'update'])->name('vardiyalar.update');
        Route::delete('/vardiyalar/{vardiya}', [\App\Http\Controllers\VardiyaController::class, 'destroy'])->name('vardiyalar.destroy');

        // Çalışma Planı
        Route::get('/calisma-plani', [\App\Http\Controllers\CalismaPlaniController::class, 'index'])->name('calisma-plani.index');
        Route::get('/calisma-plani/{grupId}/plan', [\App\Http\Controllers\CalismaPlaniController::class, 'planGetir'])->name('calisma-plani.plan-getir');
        Route::post('/calisma-plani/{grupId}/satir', [\App\Http\Controllers\CalismaPlaniController::class, 'satirGuncelle'])->name('calisma-plani.satir');
        Route::post('/calisma-plani/{grupId}/toplu-ata', [\App\Http\Controllers\CalismaPlaniController::class, 'topluAta'])->name('calisma-plani.toplu-ata');
        Route::delete('/calisma-plani/{grupId}/temizle', [\App\Http\Controllers\CalismaPlaniController::class, 'planiTemizle'])->name('calisma-plani.temizle');
        Route::post('/calisma-plani/{grupId}/ai-plan', [\App\Http\Controllers\CalismaPlaniController::class, 'aiPlanOlustur'])->name('calisma-plani.ai-plan');
        Route::get('/puantaj-parametreleri', [\App\Http\Controllers\AylikPuantajParametresiController::class, 'index'])->name('puantaj-parametreleri.index');
        Route::post('/puantaj-parametreleri', [\App\Http\Controllers\AylikPuantajParametresiController::class, 'store'])->name('puantaj-parametreleri.store');
        Route::put('/puantaj-parametreleri/{id}', [\App\Http\Controllers\AylikPuantajParametresiController::class, 'update'])->name('puantaj-parametreleri.update');
        Route::delete('/puantaj-parametreleri/{id}', [\App\Http\Controllers\AylikPuantajParametresiController::class, 'destroy'])->name('puantaj-parametreleri.destroy');

        Route::get('/tatil-izin', [\App\Http\Controllers\TatilIzinController::class, 'index'])->name('tatil-izin.index');
        Route::post('/tatil-izin/izin-turu', [\App\Http\Controllers\TatilIzinController::class, 'izinTuruStore'])->name('tatil-izin.izin-turu-store');
        Route::put('/tatil-izin/izin-turu/{id}', [\App\Http\Controllers\TatilIzinController::class, 'izinTuruUpdate'])->name('tatil-izin.izin-turu-update');
        Route::delete('/tatil-izin/izin-turu/{id}', [\App\Http\Controllers\TatilIzinController::class, 'izinTuruDestroy'])->name('tatil-izin.izin-turu-destroy');
        Route::post('/tatil-izin/resmi-tatil', [\App\Http\Controllers\TatilIzinController::class, 'resmiTatilStore'])->name('tatil-izin.resmi-tatil-store');
        Route::delete('/tatil-izin/resmi-tatil/{id}', [\App\Http\Controllers\TatilIzinController::class, 'resmiTatilDestroy'])->name('tatil-izin.resmi-tatil-destroy');
        Route::post('/tatil-izin/ai-uret', [\App\Http\Controllers\TatilIzinController::class, 'aiTatilUret'])->name('tatil-izin.ai-uret');

        // Günlük Puantaj Parametreleri
        Route::get('/gunluk-puantaj', [\App\Http\Controllers\GunlukPuantajController::class, 'index'])->name('gunluk-puantaj.index');
        Route::post('/gunluk-puantaj', [\App\Http\Controllers\GunlukPuantajController::class, 'store'])->name('gunluk-puantaj.store');
        Route::put('/gunluk-puantaj/{id}', [\App\Http\Controllers\GunlukPuantajController::class, 'update'])->name('gunluk-puantaj.update');
        Route::delete('/gunluk-puantaj/{id}', [\App\Http\Controllers\GunlukPuantajController::class, 'destroy'])->name('gunluk-puantaj.destroy');
        Route::post('/gunluk-puantaj/{parametreId}/bordro', [\App\Http\Controllers\GunlukPuantajController::class, 'bordroStore'])->name('gunluk-puantaj.bordro-store');
        Route::put('/gunluk-puantaj/bordro/{bordroId}', [\App\Http\Controllers\GunlukPuantajController::class, 'bordroUpdate'])->name('gunluk-puantaj.bordro-update');
        Route::delete('/gunluk-puantaj/bordro/{bordroId}', [\App\Http\Controllers\GunlukPuantajController::class, 'bordroDestroy'])->name('gunluk-puantaj.bordro-destroy');

        // Bordro Alanı Tanımlamaları
        Route::get('/bordro-alanlari', [\App\Http\Controllers\BordroAlaniController::class, 'index'])->name('bordro-alanlari.index');
        Route::post('/bordro-alanlari', [\App\Http\Controllers\BordroAlaniController::class, 'store'])->name('bordro-alanlari.store');
        Route::put('/bordro-alanlari/{id}', [\App\Http\Controllers\BordroAlaniController::class, 'update'])->name('bordro-alanlari.update');
        Route::delete('/bordro-alanlari/{id}', [\App\Http\Controllers\BordroAlaniController::class, 'destroy'])->name('bordro-alanlari.destroy');

        // Personele Özel Çalışma Planları
        Route::get('/personel-calisma-plan', [\App\Http\Controllers\PersonelCalismaPlanController::class, 'index'])->name('personel-calisma-plan.index');
        Route::get('/personel-calisma-plan/{personelId}/plan', [\App\Http\Controllers\PersonelCalismaPlanController::class, 'planGetir'])->name('personel-calisma-plan.plan-getir');
        Route::post('/personel-calisma-plan/{personelId}/gun', [\App\Http\Controllers\PersonelCalismaPlanController::class, 'gunGuncelle'])->name('personel-calisma-plan.gun-guncelle');
        Route::post('/personel-calisma-plan/{personelId}/grup-kopyala', [\App\Http\Controllers\PersonelCalismaPlanController::class, 'grupPlanKopyala'])->name('personel-calisma-plan.grup-kopyala');
        Route::post('/personel-calisma-plan/{personelId}/toplu-ata', [\App\Http\Controllers\PersonelCalismaPlanController::class, 'topluAta'])->name('personel-calisma-plan.toplu-ata');
        Route::delete('/personel-calisma-plan/{personelId}/temizle', [\App\Http\Controllers\PersonelCalismaPlanController::class, 'temizle'])->name('personel-calisma-plan.temizle');

        // Personel İzin Yönetimi
        Route::get('/personel-izin', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'index'])->name('personel-izin.index');
        Route::get('/personel-izin/{personelId}/izinler', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'izinGetir'])->name('personel-izin.izin-getir');
        Route::post('/personel-izin', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'store'])->name('personel-izin.store');
        Route::put('/personel-izin/{id}', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'update'])->name('personel-izin.update');
        Route::delete('/personel-izin/{id}', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'destroy'])->name('personel-izin.destroy');
        Route::post('/personel-izin/{id}/durum', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'durumGuncelle'])->name('personel-izin.durum');
        Route::post('/personel-izin-hesapla', [\App\Http\Controllers\PersonelIzinYonetimController::class, 'hesaplaIzinTarihi'])->name('personel-izin.hesapla');

        // Personel Zimmet Yönetimi
        Route::post('/personel-zimmet', [\App\Http\Controllers\PersonelZimmetController::class, 'store'])->name('personel-zimmet.store');
        Route::put('/personel-zimmet/{id}', [\App\Http\Controllers\PersonelZimmetController::class, 'update'])->name('personel-zimmet.update');
        Route::delete('/personel-zimmet/{id}', [\App\Http\Controllers\PersonelZimmetController::class, 'destroy'])->name('personel-zimmet.destroy');
        Route::post('/personel-zimmet/{id}/iade', [\App\Http\Controllers\PersonelZimmetController::class, 'iade'])->name('personel-zimmet.iade');

        // Mesai Yönetimi
        Route::post('/mesai', [\App\Http\Controllers\MesaiController::class, 'store'])->name('mesai.store');
        Route::put('/mesai/{id}', [\App\Http\Controllers\MesaiController::class, 'update'])->name('mesai.update');
        Route::delete('/mesai/{id}', [\App\Http\Controllers\MesaiController::class, 'destroy'])->name('mesai.destroy');

        // Personel Dosya Yönetimi
        Route::post('/personel-dosya', [\App\Http\Controllers\PersonelDosyaController::class, 'store'])->name('personel-dosya.store');
        Route::delete('/personel-dosya/{id}', [\App\Http\Controllers\PersonelDosyaController::class, 'destroy'])->name('personel-dosya.destroy');
        Route::get('/personel-dosya/{id}/indir', [\App\Http\Controllers\PersonelDosyaController::class, 'download'])->name('personel-dosya.download');

        Route::post('/calisma-gruplari', [\App\Http\Controllers\CalismaPlaniController::class, 'grupStore'])->name('calisma-gruplari.store');
        Route::put('/calisma-gruplari/{id}', [\App\Http\Controllers\CalismaPlaniController::class, 'grupUpdate'])->name('calisma-gruplari.update');
        Route::delete('/calisma-gruplari/{id}', [\App\Http\Controllers\CalismaPlaniController::class, 'grupDestroy'])->name('calisma-gruplari.destroy');
    });
});

// Super Admin Routes
Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('index');
    Route::get('/firmalar/{id}', [\App\Http\Controllers\SuperAdminController::class, 'firmaDetay'])->name('firmalar.detay');
    Route::put('/firmalar/{id}', [\App\Http\Controllers\SuperAdminController::class, 'firmaGuncelle'])->name('firmalar.guncelle');
    Route::delete('/firmalar/{id}', [\App\Http\Controllers\SuperAdminController::class, 'firmaSil'])->name('firmalar.sil');
    Route::post('/firmalar/{id}/abonelik', [\App\Http\Controllers\SuperAdminController::class, 'updateAbonelik'])->name('firmalar.abonelik');
    Route::post('/firmalar/{id}/impersonate', [\App\Http\Controllers\SuperAdminController::class, 'impersonate'])->name('firmalar.impersonate');
    Route::post('/firmalar/olustur', [\App\Http\Controllers\SuperAdminController::class, 'firmaOlustur'])->name('firmalar.olustur');
    Route::post('/impersonate-leave', [\App\Http\Controllers\SuperAdminController::class, 'impersonateLeave'])->name('impersonate-leave');
    Route::post('/adminler/{id}/yetki', [\App\Http\Controllers\SuperAdminController::class, 'updateAdminYetki'])->name('adminler.yetki');
    // Duyurular
    Route::get('/duyurular', [\App\Http\Controllers\SuperAdminController::class, 'duyurular'])->name('duyurular.index');
    Route::post('/duyurular', [\App\Http\Controllers\SuperAdminController::class, 'duyuruGonder'])->name('duyurular.gonder');
    Route::delete('/duyurular/{id}', [\App\Http\Controllers\SuperAdminController::class, 'duyuruSil'])->name('duyurular.sil');
    // Paket Yönetimi
    Route::get('/paketler', [\App\Http\Controllers\SuperAdminController::class, 'paketler'])->name('paketler.index');
    Route::put('/paketler/{id}', [\App\Http\Controllers\SuperAdminController::class, 'paketGuncelle'])->name('paketler.guncelle');
    // Aktivite Logları
    Route::get('/aktivite-loglar', [\App\Http\Controllers\SuperAdminController::class, 'aktiviteLoglar'])->name('aktivite-loglar');
    // Destek Biletleri (Super Admin tarafı)
    Route::get('/destek-biletleri', [\App\Http\Controllers\DestekBiletController::class, 'tumBiletler'])->name('destek.index');
    Route::get('/destek-biletleri/{id}', [\App\Http\Controllers\DestekBiletController::class, 'detay'])->name('destek.detay');
    Route::post('/destek-biletleri/{id}/mesaj', [\App\Http\Controllers\DestekBiletController::class, 'mesajGonder'])->name('destek.mesaj');
    Route::put('/destek-biletleri/{id}/durum', [\App\Http\Controllers\DestekBiletController::class, 'durumGuncelle'])->name('destek.durum');
});

// Destek Biletleri (Firma kullanıcı tarafı)
Route::middleware(['auth'])->prefix('destek')->name('destek.firma.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DestekBiletController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\DestekBiletController::class, 'olustur'])->name('olustur');
    Route::get('/{id}', [\App\Http\Controllers\DestekBiletController::class, 'detay'])->name('detay');
    Route::post('/{id}/mesaj', [\App\Http\Controllers\DestekBiletController::class, 'mesajGonder'])->name('mesaj');
});

require __DIR__.'/auth.php';
