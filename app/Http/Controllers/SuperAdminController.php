<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Firma;
use App\Models\Kullanici;
use App\Models\Personel;
use App\Models\PdksCihazi;
use App\Models\PaketTanimi;
use App\Services\AktiviteLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SuperAdminController extends Controller
{
    /**
     * Super Admin Dashboard - Zengin metrikler ve genel görünüm
     */
    public function index()
    {
        Gate::authorize('firmalari_gorme');

        // Temel Metrikler
        $aktifFirmaSayisi = Firma::where('durum', true)->count();
        $pasifFirmaSayisi = Firma::where('durum', false)->count();
        $toplamCihaz = PdksCihazi::count();
        $toplamPersonel = Personel::count();
        $toplamKullanici = Kullanici::withoutGlobalScopes()->count();
        $son24SaatKayit = DB::table('pdks_kayitlari')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Son 30 günde yeni eklenen firma sayısı
        $yeniFirmaSayisi = Firma::where('created_at', '>=', now()->subDays(30))->count();

        // Aboneliği yakında bitecek firmalar (30 gün içinde)
        $abonelikUyarisi = Firma::where('durum', true)
            ->whereNotNull('abonelik_bitis_tarihi')
            ->whereBetween('abonelik_bitis_tarihi', [now(), now()->addDays(30)])
            ->select('id', 'firma_adi', 'abonelik_bitis_tarihi', 'paket_tipi')
            ->orderBy('abonelik_bitis_tarihi')
            ->get();

        // Aboneliği geçmiş firmalar
        $abonelikGecmis = Firma::where('durum', true)
            ->whereNotNull('abonelik_bitis_tarihi')
            ->where('abonelik_bitis_tarihi', '<', now())
            ->select('id', 'firma_adi', 'abonelik_bitis_tarihi', 'paket_tipi')
            ->orderBy('abonelik_bitis_tarihi')
            ->get();

        // Firma listesi + istatistikler
        $firmalar = Firma::withCount(['kullanicilar', 'personeller', 'cihazlar'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Paket dağılımı
        $paketDagilimi = Firma::select('paket_tipi', DB::raw('count(*) as sayi'))
            ->groupBy('paket_tipi')
            ->pluck('sayi', 'paket_tipi')
            ->toArray();

        // Son 7 gün firma bazlı kayıt istatistikleri
        $gunlukKayitlar = DB::table('pdks_kayitlari')
            ->select(DB::raw('DATE(created_at) as tarih'), DB::raw('count(*) as sayi'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('tarih')
            ->orderBy('tarih')
            ->get();

        $grafikVerisi = [
            'labels' => $gunlukKayitlar->pluck('tarih')->map(fn($t) => \Carbon\Carbon::parse($t)->format('d M'))->toArray(),
            'data' => $gunlukKayitlar->pluck('sayi')->toArray(),
        ];

        // Grafik verisi boşsa son 7 günü göster
        if (empty($grafikVerisi['labels'])) {
            $grafikVerisi = [
                'labels' => collect(range(6, 0))->map(fn($d) => now()->subDays($d)->format('d M'))->toArray(),
                'data' => collect(range(6, 0))->map(fn() => 0)->toArray(),
            ];
        }

        // Son giriş yapan kullanıcılar
        $sonGirisler = Kullanici::withoutGlobalScopes()
            ->whereNotNull('updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->select('id', 'ad_soyad', 'eposta', 'rol', 'firma_id', 'updated_at')
            ->get();

        // Sistem Sağlığı
        $cpuLoad = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
        $ramKullanimi = memory_get_usage(true) / 1024 / 1024;

        // Yavaş sorgular
        $yavasSorgular = Gate::allows('teknik_loglar_gorme')
            ? DB::table('sistem_loglari')
                ->where('islem', 'yavas_sorgu')
                ->orderBy('tarih', 'desc')
                ->limit(10)
                ->get()
            : [];

        // Admin listesi
        $adminler = Gate::allows('admin_yonetimi')
            ? Kullanici::with('superAdminYetki')->where('rol', 'admin')->get()
            : [];

        return Inertia::render('SuperAdmin/Dashboard', [
            'metrikler' => [
                'aktifFirma' => $aktifFirmaSayisi,
                'pasifFirma' => $pasifFirmaSayisi,
                'toplamCihaz' => $toplamCihaz,
                'toplamPersonel' => $toplamPersonel,
                'toplamKullanici' => $toplamKullanici,
                'sonKayitSayisi' => $son24SaatKayit,
                'kuyrukBekleyen' => DB::table('jobs')->count(),
                'yeniFirma30gun' => $yeniFirmaSayisi,
            ],
            'sistem' => [
                'cpu' => round($cpuLoad * 10, 2),
                'ram' => round($ramKullanimi, 2),
                'grafik' => $grafikVerisi,
            ],
            'firmalar' => $firmalar,
            'paketDagilimi' => $paketDagilimi,
            'paketTanimlari' => PaketTanimi::where('aktif', true)->orderBy('sira')->get(),
            'abonelikUyarisi' => $abonelikUyarisi,
            'abonelikGecmis' => $abonelikGecmis,
            'sonGirisler' => $sonGirisler,
            'yavasSorgular' => $yavasSorgular,
            'adminler' => $adminler,
            'can' => [
                'odemeleri_yonet' => Gate::allows('odemeleri_yonet'),
                'teknik_loglar_gorme' => Gate::allows('teknik_loglar_gorme'),
                'admin_yonetimi' => Gate::allows('admin_yonetimi'),
            ]
        ]);
    }

    /**
     * Firma Detay Sayfası
     */
    public function firmaDetay($id)
    {
        Gate::authorize('firmalari_gorme');

        $firma = Firma::withCount(['kullanicilar', 'personeller', 'cihazlar'])
            ->with('paket')
            ->findOrFail($id);

        // Firma kullanıcıları
        $kullanicilar = Kullanici::withoutGlobalScopes()
            ->where('firma_id', $id)
            ->select('id', 'ad_soyad', 'eposta', 'rol', 'created_at', 'updated_at')
            ->orderBy('rol')
            ->get();

        // Son 30 gün PDKS kayıt sayısı (gün bazlı)
        $kayitIstatistik = DB::table('pdks_kayitlari')
            ->where('firma_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as tarih'), DB::raw('count(*) as sayi'))
            ->groupBy('tarih')
            ->orderBy('tarih')
            ->get();

        // Departman bazlı personel dağılımı
        $departmanDagilimi = Personel::where('firma_id', $id)
            ->select('bolum', DB::raw('count(*) as sayi'))
            ->groupBy('bolum')
            ->orderBy('sayi', 'desc')
            ->get();

        return Inertia::render('SuperAdmin/FirmaDetay', [
            'firma' => $firma,
            'kullanicilar' => $kullanicilar,
            'kayitIstatistik' => $kayitIstatistik,
            'departmanDagilimi' => $departmanDagilimi,
        ]);
    }

    /**
     * Firma bilgilerini güncelle
     */
    public function firmaGuncelle(Request $request, $id)
    {
        Gate::authorize('odemeleri_yonet');

        $firma = Firma::findOrFail($id);

        $validated = $request->validate([
            'firma_adi' => 'required|string|max:255',
            'vergi_no' => 'nullable|string|max:20',
            'vergi_dairesi' => 'nullable|string|max:100',
            'adres' => 'nullable|string|max:500',
            'abonelik_bitis_tarihi' => 'nullable|date',
            'paket_tipi' => 'required|in:Ücretsiz,Standart,Pro,Enterprise',
            'durum' => 'required|boolean',
        ]);

        $firma->update($validated);

        AktiviteLogService::logla('firma_guncellendi', 'firma', $firma->id, "'{$firma->firma_adi}' firması güncellendi.");

        return response()->json([
            'success' => true,
            'message' => 'Firma bilgileri başarıyla güncellendi.',
        ]);
    }

    /**
     * Firma sil (soft delete)
     */
    public function firmaSil($id)
    {
        Gate::authorize('odemeleri_yonet');

        $firma = Firma::findOrFail($id);
        $firma->update(['durum' => false]);
        $firma->delete();

        AktiviteLogService::logla('firma_silindi', 'firma', $firma->id, "'{$firma->firma_adi}' firması pasife alındı.");

        return response()->json([
            'success' => true,
            'message' => "'{$firma->firma_adi}' firması başarıyla pasife alındı.",
        ]);
    }

    /**
     * Abonelik güncelleme
     */
    public function updateAbonelik(Request $request, $id)
    {
        Gate::authorize('odemeleri_yonet');
        $request->validate([
            'abonelik_bitis_tarihi' => 'required|date',
            'paket_tipi' => 'required|in:Ücretsiz,Standart,Pro,Enterprise',
            'durum' => 'required|boolean'
        ]);

        $firma = Firma::findOrFail($id);
        $firma->update([
            'abonelik_bitis_tarihi' => $request->abonelik_bitis_tarihi,
            'paket_tipi' => $request->paket_tipi,
            'durum' => $request->durum
        ]);

        return response()->json(['success' => true, 'message' => 'Firma abonelik detayları güncellendi.']);
    }

    /**
     * Admin yetki güncelleme
     */
    public function updateAdminYetki(Request $request, $id)
    {
        Gate::authorize('admin_yonetimi');

        $request->validate([
            'yetkiler' => 'array'
        ]);

        $kullanici = Kullanici::where('rol', 'admin')->findOrFail($id);

        \App\Models\SuperAdminYetki::updateOrCreate(
            ['kullanici_id' => $kullanici->id],
            ['yetkiler' => $request->yetkiler ?? []]
        );

        return response()->json(['success' => true, 'message' => 'Admin yetkileri başarıyla güncellendi.']);
    }

    /**
     * Firma olarak giriş yap (Impersonate)
     */
    public function impersonate($firmaId)
    {
        Gate::authorize('firmalari_gorme');

        $firma = Firma::findOrFail($firmaId);

        // Firmanın admin kullanıcısını bul
        $firmaAdmin = Kullanici::withoutGlobalScopes()
            ->where('firma_id', $firmaId)
            ->where('rol', 'admin')
            ->first();

        if (!$firmaAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Bu firmanın admin kullanıcısı bulunamadı.'
            ], 404);
        }

        // Mevcut super admin'i session'a kaydet
        session()->put('impersonate_super_admin_id', Auth::id());
        session()->put('impersonate_firma_adi', $firma->firma_adi);

        // Firma admin olarak giriş yap
        Auth::login($firmaAdmin);

        return redirect()->route('dashboard');
    }

    /**
     * Impersonate'den çık, kendi hesabına dön
     */
    public function impersonateLeave()
    {
        $superAdminId = session()->get('impersonate_super_admin_id');

        if (!$superAdminId) {
            return redirect()->route('dashboard');
        }

        $superAdmin = Kullanici::withoutGlobalScopes()->findOrFail($superAdminId);

        session()->forget('impersonate_super_admin_id');
        session()->forget('impersonate_firma_adi');

        Auth::login($superAdmin);

        return redirect()->route('super-admin.index');
    }

    /**
     * Yeni Firma + Admin Kullanıcı Oluştur
     */
    public function firmaOlustur(Request $request)
    {
        Gate::authorize('odemeleri_yonet');

        $validated = $request->validate([
            'firma_adi' => 'required|string|max:255',
            'vergi_no' => 'nullable|string|max:20',
            'vergi_dairesi' => 'nullable|string|max:100',
            'adres' => 'nullable|string|max:500',
            'paket_tipi' => 'required|in:Ücretsiz,Standart,Pro,Enterprise',
            'abonelik_bitis_tarihi' => 'nullable|date',
            'admin_ad_soyad' => 'required|string|max:255',
            'admin_eposta' => 'required|email|max:255',
            'admin_sifre' => 'required|string|min:6|max:100',
        ]);

        // E-posta benzersizlik kontrolü
        $mevcutKullanici = Kullanici::withoutGlobalScopes()
            ->where('eposta', $validated['admin_eposta'])
            ->first();

        if ($mevcutKullanici) {
            return response()->json([
                'success' => false,
                'message' => 'Bu e-posta adresi zaten kullanımda: ' . $validated['admin_eposta']
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Firma oluştur
            $firma = Firma::create([
                'firma_adi' => $validated['firma_adi'],
                'vergi_no' => $validated['vergi_no'] ?? null,
                'vergi_dairesi' => $validated['vergi_dairesi'] ?? null,
                'adres' => $validated['adres'] ?? null,
                'paket_tipi' => $validated['paket_tipi'],
                'abonelik_bitis_tarihi' => $validated['abonelik_bitis_tarihi'] ?? null,
                'durum' => true,
            ]);

            // 2. Firma admin kullanıcısı oluştur
            Kullanici::withoutGlobalScopes()->create([
                'firma_id' => $firma->id,
                'ad_soyad' => $validated['admin_ad_soyad'],
                'eposta' => $validated['admin_eposta'],
                'sifre' => bcrypt($validated['admin_sifre']),
                'rol' => 'admin',
            ]);

            DB::commit();

            AktiviteLogService::logla('firma_olusturuldu', 'firma', $firma->id, "'{$firma->firma_adi}' firması oluşturuldu. Admin: {$validated['admin_eposta']}");

            return response()->json([
                'success' => true,
                'message' => "'{$firma->firma_adi}' firması ve admin kullanıcısı başarıyla oluşturuldu.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Firma oluşturulurken hata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duyuru Gönder (Tüm firmalara)
     */
    public function duyuruGonder(Request $request)
    {
        Gate::authorize('firmalari_gorme');

        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'icerik' => 'required|string|max:5000',
            'tip' => 'required|in:bilgi,uyari,bakim,guncelleme',
        ]);

        DB::table('platform_duyurulari')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'baslik' => $validated['baslik'],
            'icerik' => $validated['icerik'],
            'tip' => $validated['tip'],
            'gonderen_id' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Duyuru başarıyla yayınlandı.',
        ]);
    }

    /**
     * Duyuru Listesi
     */
    public function duyurular()
    {
        Gate::authorize('firmalari_gorme');

        $duyurular = DB::table('platform_duyurulari')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($duyurular);
    }

    /**
     * Duyuru Sil
     */
    public function duyuruSil($id)
    {
        Gate::authorize('firmalari_gorme');

        DB::table('platform_duyurulari')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Duyuru silindi.',
        ]);
    }

    /**
     * Paket Tanımları Listesi
     */
    public function paketler()
    {
        Gate::authorize('odemeleri_yonet');

        $paketler = PaketTanimi::orderBy('sira')->get();

        // Her paket için firma sayısını ekle
        $paketler->each(function ($paket) {
            $paket->firma_sayisi = Firma::where('paket_tipi', $paket->ad)->count();
        });

        return response()->json($paketler);
    }

    /**
     * Paket Güncelle
     */
    public function paketGuncelle(Request $request, $id)
    {
        Gate::authorize('odemeleri_yonet');

        $paket = PaketTanimi::findOrFail($id);

        $validated = $request->validate([
            'max_personel' => 'required|integer|min:0',
            'max_kullanici' => 'required|integer|min:0',
            'max_cihaz' => 'required|integer|min:0',
            'aylik_fiyat' => 'required|numeric|min:0',
            'yillik_fiyat' => 'required|numeric|min:0',
            'ozellikler' => 'nullable|array',
            'aciklama' => 'nullable|string|max:500',
            'aktif' => 'required|boolean',
        ]);

        $paket->update($validated);

        AktiviteLogService::logla('paket_guncellendi', 'paket', $paket->id, "'{$paket->ad}' paketi güncellendi.");

        return response()->json([
            'success' => true,
            'message' => "'{$paket->ad}' paketi güncellendi.",
        ]);
    }

    /**
     * Aktivite Logları
     */
    public function aktiviteLoglar(Request $request)
    {
        Gate::authorize('firmalari_gorme');

        $loglar = DB::table('platform_aktivite_loglari')
            ->leftJoin('kullanicilar', 'platform_aktivite_loglari.kullanici_id', '=', 'kullanicilar.id')
            ->select(
                'platform_aktivite_loglari.*',
                'kullanicilar.ad_soyad as kullanici_adi'
            )
            ->orderBy('platform_aktivite_loglari.created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($loglar);
    }
}
